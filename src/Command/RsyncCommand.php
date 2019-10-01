<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Generoi\Robo\Common\AliasTrait;

trait RsyncCommand
{
    use AliasTrait {
        parseAlias as private;
        replacePlaceholders as private;
    }

    /**
     * Rsync files between two targets.
     *
     * @param  string  $source  Source target. Can be an aliased string
     *     `@production:%files`
     * @param  string  $destination  Destination target. Can
     *     be an aliased string `@production:%files`
     * @param  array  $options
     * @option $dry-run (bool) Run a dry-run before
     * @option $exclude (array) Exclude patterns
     * @option $options (array) Map of extra options passed straight to rsync
     * @return \Robo\Result
     */
    public function rsync(string $source, string $destination, $options = ['dry-run' => false, 'exclude' => null, 'options' => null])
    {
        $rsync = $this->taskRsyncAlias()
            ->from($source)
            ->to($destination)
            ->recursive()
            ->archive()
            ->compress()
            ->excludeVcs()
            ->checksum();

        if (!empty($options['exclude'])) {
            $rsync->exclude($options['exclude']);
        }

        if (!empty($options['options'])) {
            $rsync->options($options['options']);
        }

        if (strpos($destination, 'prod') !== false && !$this->confirm(sprintf('This will replace files on "%s", are you sure you want to continue?', $destination))) {
            return Result::error($rsync, 'Cancelled');
        }

        // @todo file bug report
        // if ($exclude = Robo::Config()->get('task.Remote.RsyncAlias.settings.exclude')) {
        //     if (is_array($exclude)) {
        //         array_shift($exclude);
        //         $rsync->exclude($exclude);
        //     }
        // }

        if (!empty($options['dry-run'])) {
            if ($this->confirm('Dry run does currently not work, do you wish to continue with the real command?')) {
                return $rsync->run();
            }
            // @see https://github.com/consolidation/Robo/issues/583
            // $dryRun = clone $rsync;
            // $result = $dryRun->dryRun()->run();

            // if ($this->confirm('Do you want to run')) {
            //     return $rsync->run();
            // } else {
            //     return $result;
            // }
        } else {
            return $rsync->run();
        }
    }

    /**
     * Pull files from remote to self. For example: `rsync:pull @staging:%files`
     *
     * @param  string  $source  Source target. Can be an aliased string
     *     `@production:%files`
     * @param  array  $options
     * @option $dry-run (bool) Run a dry-run before
     * @return \Robo\Result
     */
    public function rsyncPull(string $source, $options = ['dry-run' => true])
    {
        $config = $this->parseAlias($source);
        $destination = 'self';
        if (!empty($config['relativePath'])) {
            $destination = "self:{$config['relativePath']}";
        }

        return $this->rsync($source, $destination, $options);
    }

    /**
     * Push files from self to remote. For example: `rsync:push @staging:%files`
     *
     * @param  string  $destination  Destination target. Can be an aliased
     *     string `@production:%files`
     * @param  array  $options
     * @option $dry-run (bool) Run a dry-run before
     * @return \Robo\Result
     */
    public function rsyncPush(string $destination, $options = ['dry-run' => true])
    {
        $config = $this->parseAlias($destination);
        $source = 'self';
        if (!empty($config['relativePath'])) {
            $source = "self:{$config['relativePath']}";
        }

        return $this->rsync($source, $destination, $options);
    }
}
