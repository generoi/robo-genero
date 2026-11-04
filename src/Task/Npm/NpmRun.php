<?php

namespace Generoi\Robo\Task\Npm;

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
     * @param  string|null  $pathToNpm  npm, or `pnpm` / `yarn`.
     */
    public function __construct($pathToNpm = null)
    {
        parent::__construct($pathToNpm);

        if ($pathToNpm && basename($pathToNpm) !== 'npm') {
            $this->action = 'run';
        }
    }

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
     * Script to run supporting passthru options.
     *
     * @param  string  $script
     * @return $this
     */
    public function rawScript($script)
    {
        $this->rawArg($script);

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
