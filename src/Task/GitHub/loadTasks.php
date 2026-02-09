<?php

namespace Generoi\Robo\Task\GitHub;

trait loadTasks
{
    protected function taskGitHubWorkflowDispatch(string $workflow)
    {
        return $this->task(GitHubWorkflowDispatch::class, $workflow);
    }
}
