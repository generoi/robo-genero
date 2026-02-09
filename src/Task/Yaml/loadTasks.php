<?php

namespace Generoi\Robo\Task\Yaml;

trait loadTasks
{
    /**
     * @param  string  $file
     * @return \Generoi\Robo\Task\Yaml\Yaml
     */
    protected function taskYaml($file)
    {
        return $this->task(Yaml::class, $file);
    }
}
