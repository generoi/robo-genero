<?php

namespace Generoi\Robo\Command;

use Generoi\Robo\Common\ThemeTrait;
use Robo\Contract\TaskInterface;

trait InstallCommand
{
    use ThemeTrait;

    /**
     * Install production packages.
     */
    public function installProduction(): TaskInterface
    {
        return $this->taskComposerInstall()
            ->dir($this->getThemePath())
            ->noDev()
            ->noInteraction()
            ->optimizeAutoloader()
            ->option('quiet');
    }

    /**
     * Install development packages.
     */
    public function installDevelopment(): TaskInterface
    {
        $tasks = $this->collectionBuilder();
        if (file_exists('Gemfile')) {
            $tasks->taskExec('bundle');
        }

        $tasks->taskComposerInstall()
            ->dir($this->getThemePath());

        $tasks->taskNpmInstall()
            ->dir($this->getThemePath());

        if (!file_exists('.env')) {
            $tasks->taskFilesystemStack()
                ->copy('.env.example', '.env');
        }

        return $tasks;
    }
}
