<?php

namespace Generoi\Robo\Task\Placeholder;

trait loadTasks
{
    /**
     * @param  null|string  $placeholder
     * @return \Generoi\Robo\Task\Placeholder\Find
     */
    protected function taskPlaceholderFind($placeholder = null)
    {
        return $this->task(Find::class, $placeholder);
    }

    /**
     * @return \Generoi\Robo\Task\Placeholder\Replace
     */
    protected function taskPlaceholderReplace(string $from)
    {
        return $this->task(Replace::class, $from);
    }
}
