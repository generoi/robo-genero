<?php

namespace Generoi\Robo\Task;

use Robo;

/**
 * Npm Run script
 *
 * ```php
 * <?php
 * $this->taskNpmRun('build')
 *     ->noProgress()
 *     ->run();
 * ```
 */
class NpmRun extends Robo\Task\Npm\Base
{
    /**
     * @var string
     */
    protected $action = 'run-script';

    /**
     * Script to run.
     *
     * @param  string  $script
     * @return $this
     */
    public function script($script)
    {
        $this->arg($script);
        return $this;
    }

    /**
     * Set no progress.
     *
     * @return $this
     */
    public function noProgress()
    {
        $this->option('no-progress');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->printTaskInfo('Run Npm script: {arguments}', ['arguments' => $this->arguments]);
        return $this->executeCommand($this->getCommand());
    }
}
