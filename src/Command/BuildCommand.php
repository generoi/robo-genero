<?php

namespace Generoi\Robo\Command;

use Generoi\Robo\Common\PackageManagerTrait;
use Generoi\Robo\Common\ThemeTrait;
use Robo\Contract\TaskInterface;

trait BuildCommand
{
    use PackageManagerTrait;
    use ThemeTrait;

    /**
     * Build production artifacts.
     */
    public function buildProduction(array $options = [
        'npm-script' => 'build:production',
    ]): TaskInterface
    {
        return $this->taskNpmRun($this->getPackageManager($this->getThemePath()))
            ->rawScript($options['npm-script'])
            ->dir($this->getThemePath());
    }

    /**
     * Build development artifacts.
     */
    public function buildDevelopment(array $options = [
        'npm-script' => 'build',
    ]): TaskInterface
    {
        return $this->taskNpmRun($this->getPackageManager($this->getThemePath()))
            ->rawScript($options['npm-script'])
            ->dir($this->getThemePath());
    }
}
