<?php

namespace Generoi\Robo\Task\PhpCodeSniffer;

trait loadTasks
{
    /**
     * @param  string  $file
     * @return \Generoi\Robo\Task\PhpCodeSniffer\PhpCodeSniffer
     */
    protected function taskPhpCodeSniffer($file)
    {
        return $this->task(PhpCodeSniffer::class, $file);
    }

    /**
     * @param  string  $file
     * @return \Generoi\Robo\Task\PhpCodeSniffer\PhpCodeBeautifier
     */
    protected function taskPhpCodeBeautifier($file)
    {
        return $this->task(PhpCodeBeautifier::class, $file);
    }
}
