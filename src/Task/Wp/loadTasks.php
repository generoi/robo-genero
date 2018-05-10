<?php

namespace Generoi\Robo\Task\Wp;

trait loadTasks
{
    /**
     * @param string $wpCliPath
     * @return \Generoi\Robo\Task\Wp\WpCliStack
     */
    protected function taskWpCliStack($wpCliPath = null)
    {
        return $this->task(WpCliStack::class, $wpCliPath);
    }
}
