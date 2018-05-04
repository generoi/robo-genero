<?php

namespace Generoi\Robo\Task\Remote;

trait loadTasks
{
    /**
     * @return \Generoi\Robo\Task\Remote\RsyncAlias
     */
    protected function taskRsyncAlias()
    {
        return $this->task(RsyncAlias::class);
    }
}
