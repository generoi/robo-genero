<?php

namespace Generoi\Robo\Task\Wp;

trait loadTasks
{
    /**
     * @param  string  $wpCliPath
     * @return \Generoi\Robo\Task\Wp\WpCliStack
     */
    protected function taskWpCliStack($wpCliPath = 'wp')
    {
        return $this->task(WpCliStack::class, $wpCliPath);
    }
}
