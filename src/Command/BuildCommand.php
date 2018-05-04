<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Generoi\Robo\Common\ThemeTrait;

trait BuildCommand
{
    use ThemeTrait;

    /**
     * Build production artefacts.
     */
    public function buildProduction($options = ['npm-script' => 'build:production'])
    {
        $this->taskNpmRun()
            ->script($options['npm-script'])
            ->dir($this->getThemePath())
            ->noProgress()
            ->run();
    }

    /**
     * Build development artefacts.
     */
    public function buildDevelopment($options = ['npm-script' => 'build'])
    {
        $this->taskNpmRun()
            ->script($options['npm-script'])
            ->dir($this->getThemePath())
            ->noProgress()
            ->run();
    }
}
