<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Generoi\Robo\Common\ThemeTrait;

trait InstallCommand
{
    use ThemeTrait;

    /**
     * Install production packages.
     */
    public function installProduction()
    {
        $this->taskComposerInstall()
            ->dir($this->getThemePath())
            ->noDev()
            ->noInteraction()
            ->optimizeAutoloader()
            ->option('quiet')
            ->run();
    }

    /**
     * Install development packages.
     */
    public function installDevelopment()
    {
        $this->taskExec('bundle')->run();

        $this->taskComposerInstall()
            ->dir($this->getThemePath())
            ->run();

        $this->taskNpmInstall()
            ->dir($this->getThemePath())
            ->run();

        if (!file_exists('.env')) {
            copy('.env.example', '.env');
        }
    }
}
