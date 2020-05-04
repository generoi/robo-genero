<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Robo\Result;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Question\ChoiceQuestion;

trait WpCommand
{
    /**
     * Pull the database from remote source to target set in robo.yml
     *
     * @param  string  $source  Site alias of the source site
     * @param  array  $options
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $target  (string) Site alias of the target site
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbPull($source = null, $options = [
        'exclude_tables' => null,
        'target' => '@dev',
        'debug' => false,
        'multisite' => null,
    ])
    {
        return $this->dbSync($source, $options['target'], $options);
    }

    /**
     * Push the database from source site defined in robo.yml to target site.
     *
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $target  (string) Site alias of the source site
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbPush($target = null, $options = [
        'exclude_tables' => null,
        'source' => '@dev',
        'multisite' => null,
        'debug' => false,
    ])
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
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @option $multisite  (bool) Multisite
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbSync($source = null, $target = null, $options = [
        'exclude_tables' => null,
        'multisite' => null,
        'debug' => false,
    ])
    {
        $wpcli = $this->taskWpCliStack()
            ->quiet();

        if (is_null($options['multisite'])) {
          $options['multisite'] = Robo::config()->get('multisite');
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        if (empty($source)) {
            $source = $this->ask('Source alias');
        }
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (strpos($target, 'prod') !== false && !$this->confirm(sprintf('This will replace the "%s" datebase, are you sure you want to continue?', $target))) {
            return Result::error($wpcli, 'Cancelled');
        }

        $config = Robo::config();
        $sourceUrl = $config->get("env.$source.url");
        $targetUrl = $config->get("env.$target.url");

        $sourceExecutable = $config->get("env.$source.wpcli");
        $targetExecutable = $config->get("env.$target.wpcli");

        if (empty($sourceUrl)) {
            return Result::error($wpcli, sprintf('Alias "%s" does not exist or has no url value', $source));
        }
        if (empty($targetUrl)) {
            return Result::error($wpcli, sprintf('Alias "%s" does not exist or has no url value', $target));
        }

        if (!is_array($sourceUrl)) {
            $sourceUrl = [$sourceUrl];
        }
        if (!is_array($targetUrl)) {
            $targetUrl = [$targetUrl];
        }

        if (count($sourceUrl) !== count($targetUrl)) {
            return Result::error($wpcli, sprintf('Alias "%s" has a different URL count than "%s".', $target, $source));
        }

        if (!empty($sourceExecutable)) {
            $wpcli->setAliasExecutable($source, $sourceExecutable);
        }

        if (!empty($targetExecutable)) {
            $wpcli->setAliasExecutable($target, $targetExecutable);
        }

        $wpcli->siteAlias($target);

        if (!empty($options['exclude_tables'])) {
            $wpcli
                ->structureOnly()
                ->dbSync($source, $target)
                ->excludeTables($options['exclude_tables']);
        }

        // Run database sync
        $wpcli->dbSync($source, $target)->run()->stopOnFail();

        // If there are multiple blogs, ensure the wp_site and wp_blogs tables
        // are up to date otherwise --network will not run on all tables.
        if (!empty($options['multisite'])) {
            foreach ($sourceUrl as $idx => $url) {
                $this->dbRenameSite($target, $url, $targetUrl[$idx])->stopOnFail();
            }
        }

        // Search replace each URL mapped by index.
        foreach ($sourceUrl as $idx => $url) {
            $this->dbSearchReplace($target, $url, $targetUrl[$idx], [
                'flush' => false,
                'debug' => $options['debug'],
                'hostnames' => true,
            ])->stopOnFail();
        }

        $wpcli = $this->taskWpCliStack()
            ->siteAlias($target)
            ->quiet();

        if (!empty($targetExecutable)) {
            $wpcli->executable($targetExecutable);
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        // Flush the cache
        return $wpcli->cache('flush')->run();
    }

    /**
     * Search replace strings in the database.
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $search  String to search for
     * @param  string  $replace  Replacement string
     * @param  array  $options
     * @option $flush  (bool) Flush the cache
     * @option $debug  (bool) Debug mode
     * @option $hostnames  (bool) Parse the host out of a URL and rename that as well.
     * @option $tables (array) Tables to act on
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbSearchReplace($target = null, $search = null, $replace = null, $options = [
        'flush' => true,
        'debug' => false,
        'hostnames' => true,
        'tables' => [],
    ])
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
        if (!isset($options['tables'])) {
            $options['tables'] = [];
        }

        if ($search === $replace) {
            $this->writeln(sprintf('Replacement value "%s" is identical to search value "%s". Skipping…', $replace, $search));
            return;
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet();

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        $wpcli
            ->siteAlias($target)
            ->network()
            ->skipColumns('guid')
            ->searchReplace($search, $replace, $options['tables']);

        $searchHost = parse_url($search, PHP_URL_HOST);
        $replaceHost = parse_url($replace, PHP_URL_HOST);

        if (!empty($options['hostnames']) && $searchHost && $replaceHost && $searchHost !== $replaceHost) {
            $wpcli
                ->network()
                ->skipColumns('guid')
                ->searchReplace($searchHost, $replaceHost, $options['tables']);
        }

        if (!empty($options['flush'])) {
            $wpcli->cache('flush');
        }

        return $wpcli->run();
    }

    /**
     * Dump database
     *
     * @param  string  $target  Site alias of the target site
     * @param  array  $options
     * @option $gzip  (bool) Archive
     * @option $debug  (bool) Debug mode
     * @option $exclude_tables  (array|string) Comman separated list of tables
     *     to exclude during dump.
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbExport($target = null, $path = null, $options = [
        'gzip' => false,
        'debug' => false,
        'exclude_tables' => null
    ])
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (empty($path)) {
            $path = 'database.' . $target . '.' . date('Y-m-d-His') . '.sql';
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }
        if (!empty($options['exclude_tables'])) {
            $wpcli->excludeTables($options['exclude_tables']);
        }

        $wpcli->dbExportLocally($path)->run()->stopOnFail();

        if (!empty($options['gzip'])) {
            $this->taskExec('gzip')->arg($path)->run()->stopOnFail();
        }
    }

    /**
     * Import database
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $path  Path to database dump
     * @param  array  $options
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbImport($target = null, $path = null, $options = [
        'debug' => false,
    ])
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

        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if (strpos($target, 'prod') !== false && !$this->confirm(sprintf('This will make changes on the "%s" datebase, are you sure you want to continue?', $target))) {
            return Result::error($wpcli, 'Cancelled');
        }

        if ($executable = Robo::config()->get("env.$target.wpcli")) {
            $wpcli->executable($executable);
        }

        if (!file_exists($path)) {
            return Result::error($wpcli, sprintf('File does not exist: %s', $path));
        }

        // Decompress gzipped files automatically.
        if (preg_match('/(.*)\.gz$/', $path, $matches) === 1) {
            $this->taskExec('gunzip')
                ->option('force')
                ->option('keep')
                ->arg($path)
                ->run()
                ->stopOnFail();

            $path = $matches[1];
        }

        if (!is_readable($path)) {
            return Result::error($wpcli, sprintf('File is not readable: %s', $path));
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        return $wpcli
            ->dbImportLocally($path)
            ->cache('flush')
            ->run();
    }

    /**
     * Rename a multisite domain
     *
     * @param  string  $target  Site alias of the target site
     * @param  string  $search  String to search for
     * @param  string  $replace  Replacement string
     * @param  array  $options
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbRenameSite($target = null, $search = null, $replace = null, $options = [
        'flush' => false,
        'debug' => false,
    ])
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
    ])
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

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        return $wpcli
            ->languageInstallCore($languages)
            ->languageInstallPlugin('--all', $languages)
            ->run();
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
    ])
    {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        $wpcli = $this->taskWpCliStack()
            ->quiet()
            ->siteAlias($target);

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        return $wpcli
            ->languageUpdateCore()
            ->languageUpdatePlugin('--all')
            ->run();
    }
}
