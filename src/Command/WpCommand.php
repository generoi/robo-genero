<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Robo\Result;

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
     * @return \Generoi\Robo\Command\Wp\WpCliStack
     */
    public function dbSearchReplace($target = null, $search = null, $replace = null, $options = [
        'flush' => true,
        'debug' => false,
        'hostnames' => true,
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
            ->searchReplace($search, $replace);

        $searchHost = parse_url($search, PHP_URL_HOST);
        $replaceHost = parse_url($replace, PHP_URL_HOST);

        if (!empty($options['hostnames']) && $searchHost && $replaceHost && $searchHost !== $replaceHost) {
            $wpcli
                ->network()
                ->skipColumns('guid')
                ->searchReplace($searchHost, $replaceHost);
        }

        if (!empty($options['flush'])) {
            $wpcli->cache('flush');
        }

        $wpcli->run();
    }
}
