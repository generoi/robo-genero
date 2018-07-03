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
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbPull($source = null, $options = [
        'exclude_tables' => null,
        'target' => '@dev',
        'debug' => false,
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
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbPush($target = null, $options = [
        'exclude_tables' => null,
        'source' => '@dev',
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
     * @option $debug  (bool) Debug mode
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbSync($source = null, $target = null, $options = [
        'exclude_tables' => null,
        'debug' => false,
    ])
    {
        $wpcli = $this->taskWpCliStack()
            ->stopOnFail();

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }


        if (empty($source)) {
            $source = $this->ask('Source alias');
        }
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        $config = Robo::config();
        $sourceUrl = $config->get("env.$source.url");
        $targetUrl = $config->get("env.$target.url");

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

        $wpcli->siteAlias($target);

        if (!empty($options['exclude_tables'])) {
            $wpcli
                ->structureOnly()
                ->dbSync($source, $target)
                ->excludeTables($options['exclude_tables']);
        }

        // Run database sync
        $wpcli->dbSync($source, $target)->run();

        // If there are multiple blogs, ensure the wp_site and wp_blogs tables
        // are up to date otherwise --network will not run on all tables.
        if (count($sourceUrl) > 1) {
            foreach ($sourceUrl as $idx => $url) {
                $this->dbRenameSite($target, $url, $targetUrl[$idx]);
            }
        }

        // Search replace each URL mapped by index.
        foreach ($sourceUrl as $idx => $url) {
            $this->dbSearchReplace($target, $url, $targetUrl[$idx], [
                'flush' => false,
                'debug' => $options['debug'],
                'hostnames' => true,
            ]);
        }

        $wpcli = $this->taskWpCliStack()
            ->stopOnFail();

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        // Flush the cache
        $wpcli->cache('flush')->run();
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
    ]) {
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
            ->stopOnFail();

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

        $wpcli->run();
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
    ]) {
        if (empty($target)) {
            $target = $this->ask('Target alias');
        }

        if (empty($path)) {
            $path = 'database.' . $target . '.' . date('Y-m-d-His') . '.sql';
        }

        $wpcli = $this->taskWpCliStack()
            ->stopOnFail()
            ->siteAlias($target);

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }
        if (!empty($options['exclude_tables'])) {
            $wpcli->excludeTables($options['exclude_tables']);
        }

        $wpcli->dbExportLocally($path)->run();

        if (!empty($options['gzip'])) {
            $this->taskExec('gzip')->arg($path)->run();
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
    ]) {
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
            ->stopOnFail()
            ->siteAlias($target);

        if (!file_exists($path)) {
            return Result::error($wpcli, sprintf('File does not exist: %s', $path));
        }

        // Decompress gzipped files automatically.
        if (preg_match('/(.*)\.gz$/', $path, $matches) === 1) {
            $this->taskExec('gunzip')
                ->option('force')
                ->option('keep')
                ->arg($path)
                ->run();

            $path = $matches[1];
        }

        if (!is_readable($path)) {
            return Result::error($wpcli, sprintf('File is not readable: %s', $path));
        }

        if (!empty($options['debug'])) {
            $wpcli->debug();
        }

        $wpcli
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
}
