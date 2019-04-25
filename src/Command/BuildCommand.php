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
        return $this->taskNpmRun()
            ->rawScript($options['npm-script'])
            ->dir($this->getThemePath())
            ->run();
    }

    /**
     * Build development artefacts.
     */
    public function buildDevelopment($options = ['npm-script' => 'build'])
    {
        return $this->taskNpmRun()
            ->rawScript($options['npm-script'])
            ->dir($this->getThemePath())
            ->run();
    }
}
