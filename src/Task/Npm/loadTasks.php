<?php

namespace Generoi\Robo\Task\Npm;

trait loadTasks
{
    /**
     * @param string $npmPath
     * @return \Generoi\Robo\Task\Npm\NpmRun
     */
    protected function taskNpmRun($npmPath = null)
    {
        return $this->task(NpmRun::class, $npmPath);
    }
}
