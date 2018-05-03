<?php

namespace Generoi\Robo\Task;

trait loadTasks
{
    // Sub tasks
    use Git\loadTasks;

    /**
     * @param null|string  $placeholder
     * @return \Generoi\Robo\Task\Placeholder\Find
     */
    protected function taskPlaceholderFind($placeholder = null)
    {
        return $this->task(Placeholder\Find::class, $placeholder);
    }

    /**
     * @param string  $from
     * @return \Generoi\Robo\Task\Placeholder\Replace
     */
    protected function taskPlaceholderReplace(string $from)
    {
        return $this->task(Placeholder\Replace::class, $from);
    }

    /**
     * @param  string  $file
     * @return \Generoi\Robo\Task\PhpCodeSniffer
     */
    protected function taskPhpCodeSniffer($file)
    {
        return $this->task(PhpCodeSniffer::class, $file);
    }

    /**
     * @param  string  $file
     * @return \Generoi\Robo\Task\PhpCodesniffer
     */
    protected function taskPhpCodeBeautifier($file)
    {
        return $this->task(PhpCodeBeautifier::class, $file);
    }

    /**
     * @param string $npmPath
     * @return \Generoi\Robo\Task\NpmRun
     */
    protected function taskNpmRun($npmPath = null)
    {
        return $this->task(NpmRun::class, $npmPath);
    }

    /**
     * @param string $file
     * @return \Generoi\Robo\Task\Yaml
     */
    protected function taskYaml($file)
    {
        return $this->task(Yaml::class, $file);
    }
}
