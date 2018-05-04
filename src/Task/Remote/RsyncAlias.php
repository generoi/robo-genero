<?php

namespace Generoi\Robo\Task\Remote;

use Robo\Robo;
use Robo\Task\Remote\Rsync as BaseRsync;
use Robo\Result;
use Generoi\Robo\Common\AliasTrait;

/**
 * Rsync using environment aliases
 *
 * ```php
 * <?php
 * $this->taskRsyncAlias()
 *     ->from('production:%files')
 *     ->to('self:%files')
 *     ->recursive()
 *     ->run();
 * ?>
 * ```
 */
class RsyncAlias extends BaseRsync
{
    use AliasTrait;

    /**
     * @var \Robo\Config
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = Robo::config();
    }

    /**
     * Source target from where to rsync.
     *
     * @param  string  $source
     * @return $this
     */
    public function from(string $source)
    {
        $source = $this->parseAlias($source);
        if (!empty($source['host'])) {
            $this->fromHost($source['host']);

            if (!empty($source['user'])) {
                $this->fromUser($source['user']);
            }
            if (!empty($source['ssh'])) {
                $this->remoteShell($source['ssh']);
            }
        }
        if (!empty($source['path'])) {
            $this->fromPath($source['path']);
        }
        return $this;
    }

    /**
     * Destination target from where to rsync.
     *
     * @param  string  $destination
     * @return $this
     */
    public function to(string $destination)
    {
        $destination = $this->parseAlias($destination);
        if (!empty($destination['host'])) {
            $this->toHost($destination['host']);

            if (!empty($destination['user'])) {
                $this->toUser($destination['user']);
            }

            if (!empty($destination['ssh'])) {
                $this->remoteShell($destination['ssh']);
            }
        }
        if (!empty($destination['path'])) {
            $this->toPath($destination['path']);
        }
        return $this;
    }
}
