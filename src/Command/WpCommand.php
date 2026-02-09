<?php

namespace Generoi\Robo\Command;

use Robo\Contract\TaskInterface;
use Robo\Exception\AbortTasksException;
use Robo\Exception\TaskException;
use Robo\Robo;
use RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

trait WpCommand
{
    /**
     * Pull the database from remote source to target set in robo.yml
     *
     * @param  string  $source  Site alias of the source site
     * @param  array  $options
     *
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $target  (string) Site alias of the target site
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     */
    public function dbPull($source = null, $options = [
        'exclude_tables' => null,
        'target' => '@dev',
        'debug' => false,
        'multisite' => null,
    ]): TaskInterface
    {
        return $this->dbSync($source, $options['target'], $options);
    }

    /**
     * Push the database from source site defined in robo.yml to target site.
     *
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     *
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $target  (string) Site alias of the source site
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     */
    public function dbPush($target = null, $options = [
        'exclude_tables' => null,
        'source' => '@dev',
        'multisite' => null,
        'debug' => false,
    ]): TaskInterface
    {
        return $this->dbSync($options['source'], $target, $options);
    }

    /**
     * Synchronize the database between two sites including search replacements
     * of URLs.
     *
     * @param  string  $source  Site alias of the source site
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     *
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     */
    public function dbSync($source = null, $target = null, $options = [
        'exclude_tables' => null,
        'multisite' => null,
        'debug' => false,
    ]): TaskInterface
    {
        $tasks = $this->collectionBuilder();
        $wpcli = $this->taskWpCliStack()
            ->quiet();

        if (is_null($options['multisite'])) {
            $options['multisite'] = Robo::config()->get('multisite');
        }

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        if (empty($source)) {
            $source = $this->ask('Source alias');
        }
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (strpos($target, 'prod') !== false && ! $this->confirm(sprintf('This will replace the "%s" datebase, are you sure you want to continue?', $target))) {
            throw new AbortTasksException('Cancelled');
        }

        $config = Robo::config();
        $sourceUrl = $config->get("env.$source.url");
        $targetUrl = $config->get("env.$target.url");

        $sourceExecutable = $config->get("env.$source.wpcli");
        $targetExecutable = $config->get("env.$target.wpcli");

        if (empty($sourceUrl)) {
            throw new TaskException($wpcli, sprintf('Alias "%s" does not exist or has no url value', $source));
        }
        if (empty($targetUrl)) {
            throw new TaskException($wpcli, sprintf('Alias "%s" does not exist or has no url value', $target));
        }

        if (! is_array($sourceUrl)) {
            $sourceUrl = [$sourceUrl];
        }
        if (! is_array($targetUrl)) {
            $targetUrl = [$targetUrl];
        }

        if (count($sourceUrl) !== count($targetUrl)) {
            throw new RuntimeException(sprintf('Alias "%s" has a different URL count than "%s".', $target, $source));
        }

        if (! empty($sourceExecutable)) {
            $wpcli->setAliasExecutable($source, $sourceExecutable);
        }

        if (! empty($targetExecutable)) {
            $wpcli->setAliasExecutable($target, $targetExecutable);
        }

        $wpcli->siteAlias($target);

        if (! empty($options['exclude_tables'])) {
            $wpcli
                ->structureOnly()
                ->dbSync($source, $target)
                ->excludeTables($this->expandTableWildcards($options['exclude_tables'], $source));
        }

        // Run database sync
        $tasks->addTask(
            $wpcli->dbSync($source, $target)
        );

        $tasks->addTask(
            $this->dbSearchReplaceUrls($source, $target, $options)
        );

        $wpcli = $this->taskWpCliStack()
            ->siteAlias($target)
            ->quiet();

        if (! empty($targetExecutable)) {
            $wpcli->executable($targetExecutable);
        }

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        // Flush the cache
        $tasks->addTask(
            $wpcli->cache('flush')
        );

        return $tasks;
    }

    /**
     * Search replace the URLs in the database.
     *
     * @param  string  $source  Site alias of the source site to search for
     * @param  string  $target  Site alias of the target site to be used when replaced
     * @param  array  $options
     *
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     */
    public function dbSearchReplaceUrls($source = null, $target = null, $options = [
        'multisite' => null,
        'debug' => false,
    ]): TaskInterface
    {
        if (empty($source)) {
            $source = $this->ask('Source alias');
        }
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }
        if (strpos($target, 'prod') !== false && ! $this->confirm(sprintf('This will replace the "%s" datebase, are you sure you want to continue?', $target))) {
            throw new AbortTasksException('Cancelled');
        }
        if (is_null($options['multisite'])) {
            $options['multisite'] = Robo::config()->get('multisite');
        }

        $tasks = $this->collectionBuilder();
        $config = Robo::config();
        $sourceUrl = $config->get("env.$source.url");
        $targetUrl = $config->get("env.$target.url");

        if (! is_array($sourceUrl)) {
            $sourceUrl = [$sourceUrl];
        }
        if (! is_array($targetUrl)) {
            $targetUrl = [$targetUrl];
        }

        if (count($sourceUrl) !== count($targetUrl)) {
            throw new RuntimeException(sprintf('Alias "%s" has a different URL count than "%s".', $target, $source));
        }

        // If there are multiple blogs, ensure the wp_site and wp_blogs tables
        // are up to date otherwise --network will not run on all tables.
        if (! empty($options['multisite'])) {
            foreach ($sourceUrl as $idx => $url) {
                $tasks->addTask(
                    $this->dbRenameSite($target, $url, $targetUrl[$idx])
                );
            }
        }

        // Search replace each URL mapped by index.
        foreach ($sourceUrl as $idx => $url) {
            $tasks->addTask(
                $this->dbSearchReplace($target, $url, $targetUrl[$idx], [
                    'flush' => false,
                    'debug' => $options['debug'],
                    'hostnames' => true,
                ])
            );
        }

        return $tasks;
    }

    /**
     * Search replace strings in the database.
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $search  String to search for
     * @param  string  $replace  Replacement string
     * @param  array  $options
     *
     * @option $flush  (bool) Flush the cache
     * @option $debug  (bool) Debug mode
     * @option $hostnames  (bool) Parse the host out of a URL and rename that as well.
     * @option $tables (array) Tables to act on
     */
    public function dbSearchReplace($target = null, $search = null, $replace = null, $options = [
        'flush' => true,
        'debug' => false,
        'hostnames' => true,
        'tables' => [],
    ]): TaskInterface
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }
        if (empty($search)) {
            $search = $this->ask('Search string');
        }
        if (empty($replace)) {
            $replace = $this->ask('Replace with');
        }
        if (! isset($options['tables'])) {
            $options['tables'] = [];
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet();

        if ($search === $replace) {
            $this->writeln(sprintf('Replacement value "%s" is identical to search value "%s". Skipping…', $replace, $search));

            return $this->collectionBuilder();
        }

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        $wpcli
            ->siteAlias($target)
            ->network()
            ->skipColumns('guid')
            ->searchReplace($search, $replace, $options['tables']);

        $searchHost = parse_url($search, PHP_URL_HOST);
        $replaceHost = parse_url($replace, PHP_URL_HOST);

        if (! empty($options['hostnames']) && $searchHost && $replaceHost && $searchHost !== $replaceHost) {
            $wpcli
                ->network()
                ->skipColumns('guid')
                ->searchReplace($searchHost, $replaceHost, $options['tables']);
        }

        if (! empty($options['flush'])) {
            $wpcli->cache('flush');
        }

        return $wpcli;
    }

    /**
     * Dump database
     *
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     *
     * @option $gzip  (bool) Archive
     * @option $debug  (bool) Debug mode
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     *
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbExport($target = null, $path = null, $options = [
        'gzip' => false,
        'debug' => false,
        'exclude_tables' => null,
    ]): TaskInterface
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (empty($path)) {
            $path = 'database.'.$target.'.'.date('Y-m-d-His').'.sql';
        }

        $tasks = $this->collectionBuilder();
        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }
        if (! empty($options['exclude_tables'])) {
            $wpcli->excludeTables($this->expandTableWildcards($options['exclude_tables'], $target));
        }

        $tasks->addTask(
            $wpcli->dbExportLocally($path)
        );

        if (! empty($options['gzip'])) {
            $tasks->addTask(
                $this->taskExec('gzip')->arg($path)
            );
        }

        return $tasks;
    }

    /**
     * Expand wildcard in table names into a wpcli db table subcommand.
     *
     * @param  array|string  $input
     * @param  string  $target
     */
    protected function expandTableWildcards($input, $target): string|array
    {
        $tables = is_string($input) ? explode(',', $input) : $input;
        $tables = implode(' ', $tables);

        if (! str_contains($tables, '*')) {
            return $input;
        }

        /** @var \Generoi\Robo\Command\Wp\WpCliStack $wpcli */
        $wpcli = $this->taskWpCliStack()
            ->quiet();

        $executable = Robo::config()->get("env.$target.wpcli");
        if (! empty($executable)) {
            $wpcli->setAliasExecutable($target, $executable);
        }
        $wpcli->siteAlias($target);

        $subTask = $wpcli->wp("db tables $tables --format=csv --all-tables");

        return sprintf('$(%s)', $subTask->getCommand());
    }

    /**
     * Import database
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $path  Path to database dump
     * @param  array  $options
     *
     * @option $debug  (bool) Debug mode
     *
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbImport($target = null, $path = null, $options = [
        'debug' => false,
    ]): TaskInterface
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        // If no path is provided, list the best options but also allow custom
        // paths.
        if (empty($path)) {
            $dumpFiles = Finder::create()->ignoreVCS(true)
                ->in('.')
                ->depth('== 0')
                ->name("database.$target.*.sql*")
                ->sortByModifiedTime();

            $dumpFiles = array_values(iterator_to_array($dumpFiles));

            $question = new ChoiceQuestion('Path to database dump', $dumpFiles, count($dumpFiles) - 1);
            $defaultValidator = $question->getValidator();
            $question->setValidator(function ($selected) use ($defaultValidator) {
                if (file_exists($selected)) {
                    return $selected;
                }

                return $defaultValidator($selected);
            });

            $path = $this->doAsk($question);
        }

        $tasks = $this->collectionBuilder();
        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if (strpos($target, 'prod') !== false && ! $this->confirm(sprintf('This will make changes on the "%s" datebase, are you sure you want to continue?', $target))) {
            throw new AbortTasksException('Cancelled');
        }

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (! file_exists($path)) {
            throw new RuntimeException(sprintf('File does not exist: %s', $path));
        }

        // Decompress gzipped files automatically.
        if (preg_match('/(.*)\.gz$/', $path, $matches) === 1) {
            $tasks->addTask(
                $this->taskExec('gunzip')
                    ->option('force')
                    ->option('keep')
                    ->arg($path)
            );

            $path = $matches[1];
        }

        if (! is_readable($path)) {
            throw new RuntimeException(sprintf('File is not readable: %s', $path));
        }

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        $tasks->addTask(
            $wpcli
                ->dbImportLocally($path)
                ->cache('flush')
        );

        return $tasks;
    }

    /**
     * Rename a multisite domain
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $search  String to search for
     * @param  string  $replace  Replacement string
     * @param  array  $options
     */
    public function dbRenameSite($target = null, $search = null, $replace = null, $options = [
        'flush' => true,
        'debug' => false,
    ]): TaskInterface
    {
        $search = parse_url($search, PHP_URL_HOST) ?? $search;
        $replace = parse_url($replace, PHP_URL_HOST) ?? $replace;

        return $this->dbSearchReplace($target, $search, $replace, array_merge($options, [
            'tables' => ['wp_site', 'wp_blogs'],
        ]));
    }

    /**
     * Install one or many language packs.
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $languages  Language to install
     * @param  array  $options
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function languageInstall($target = null, $languages = null, $options = [
        'debug' => false,
        'languages' => null,
    ]): TaskInterface
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (empty($languages)) {
            $languages = $options['languages'];
        }
        if (empty($languages)) {
            $languages = $this->ask('Comma separated list of languages');
        }
        if (is_string($languages)) {
            $languages = array_map('trim', explode(',', $languages));
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        return $wpcli
            ->languageInstallCore($languages)
            ->languageInstallPlugin('--all', $languages);
    }

    /**
     * Update all language packs.
     *
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function languageUpdate($target = null, $options = [
        'debug' => false,
    ]): TaskInterface
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if (! empty($options['debug'])) {
            $wpcli->debug();
        }

        return $wpcli
            ->languageUpdateCore()
            ->languageUpdatePlugin('--all');
    }
}
