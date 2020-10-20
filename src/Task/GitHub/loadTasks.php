<?php

namespace Generoi\Robo\Task\GitHub;

trait loadTasks
{
    /**
     * @param string  $workflow
     */
    protected function taskGitHubWorkflowDispatch(string $workflow)
    {
        return $this->task(GitHubWorkflowDispatch::class, $workflow);
    }
}
