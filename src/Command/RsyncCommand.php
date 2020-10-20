<?php

namespace Generoi\Robo\Command;

use Robo\Result;
use Generoi\Robo\Common\AliasTrait;
use Robo\Contract\TaskInterface;
use Robo\Exception\AbortTasksException;

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
     */
    public function rsync(
        string $source,
        string $destination,
        array $options = ['dry-run' => false, 'exclude' => null, 'options' => null]
    ): TaskInterface {
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
            throw new AbortTasksException('Cancelled');
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
                return $rsync;
            }
            return $this->collectionBuilder();
            // return Result::success($rsync, 'Skipping dry run');
            // @see https://github.com/consolidation/Robo/issues/583
            // $dryRun = clone $rsync;
            // $result = $dryRun->dryRun()->run();

            // if ($this->confirm('Do you want to run')) {
            //     return $rsync->run();
            // } else {
            //     return $result;
            // }
        }

        return $rsync;
    }

    /**
     * Pull files from remote to self. For example: `rsync:pull @staging:%files`
     *
     * @param  string  $source  Source target. Can be an aliased string
     *     `@production:%files`
     * @param  array  $options
     * @option $dry-run (bool) Run a dry-run before
     */
    public function rsyncPull(string $source, array $options = ['dry-run' => true]): TaskInterface
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
     */
    public function rsyncPush(string $destination, array $options = ['dry-run' => true]): TaskInterface
    {
        $config = $this->parseAlias($destination);
        $source = 'self';
        if (!empty($config['relativePath'])) {
            $source = "self:{$config['relativePath']}";
        }

        return $this->rsync($source, $destination, $options);
    }
}
