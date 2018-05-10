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
            ->siteAlias($target)
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

        if (!empty($options['exclude_tables'])) {
            $wpcli
                ->structureOnly()
                ->dbSync($source, $target)
                ->excludeTables($options['exclude_tables']);
        }

        $wpcli
            ->dbSync($source, $target)
            ->network()
            ->skipColumns('guid')
            ->searchReplace($sourceUrl, $targetUrl)
            ->searchReplace(parse_url($sourceUrl, PHP_URL_HOST), parse_url($targetUrl, PHP_URL_HOST))
            ->cache('flush')
            ->run();
    }
}
