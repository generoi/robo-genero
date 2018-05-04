<?php

namespace Generoi\Robo\Task\Git;

trait loadTasks
{
    /**
     * @param string  $remote
     * @return \Generoi\Robo\Task\Git\Remote
     */
    protected function taskGitRemote($remote)
    {
        return $this->task(GitRemote::class, $remote);
    }

    /**
     * @param string  $remote
     * @return \Generoi\Robo\Task\Git\Clone
     */
    protected function taskGitClone($remote)
    {
        return $this->task(GitClone::class, $remote);
    }
}
