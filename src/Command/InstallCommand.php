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
        return $this->taskComposerInstall()
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
        if (file_exists('Gemfile')) {
            $this->taskExec('bundle')->run()->stopOnFail();
        }

        $this->taskComposerInstall()
            ->dir($this->getThemePath())
            ->run()
            ->stopOnFail();

        $this->taskNpmInstall()
            ->dir($this->getThemePath())
            ->run()
            ->stopOnFail();

        if (!file_exists('.env')) {
            $this->taskFilesystemStack()
                ->stopOnFail()
                ->copy('.env.example', '.env')
                ->run();
        }
    }
}
