<?php

namespace Generoi\Robo\Task;

use Robo\Robo;
use Robo\Result;

trait loadCommands
{
    /**
     * Reload configuration in case it's been modified.
     */
    protected function reloadConfig()
    {
        Robo::loadConfiguration([getcwd() . '/robo.yml']);
    }

    /**
     * Get the path of the theme
     *
     * @return string
     */
    protected function getThemePath()
    {
        return Robo::config()->get('theme_path');
    }

    /**
     * Run test suite.
     */
    protected function test()
    {
        $this->taskComposerValidate()->noCheckAll()->run();
        $this->sniff();
        $this->taskNpmRun('test')->dir($this->getThemePath())->run();
    }

    /**
     * Run PHP CodeSniffer.
     *
     * @param  string  $file  The path to sniff
     * @param  array  $options  Options
     * @option $autofix (bool) Automatically fix all problems
     */
    protected function sniff($file = '', $options = [
        'autofix' => false,
    ])
    {
        $result = $this->taskPhpCodeSniffer($file)->run();
        if (!$result->wasSuccessful() && !$options['autofix']) {
            $options['autofix'] = $this->confirm('Would you like to run phpcbf to fix the reported errors?');
        }
        if ($options['autofix']) {
            $task = $this->taskPhpCodeBeautifier($file);
            $result = $task->run();

            // PHPCBF used 1 as successful (sigh).
            if ($result->getExitCode() === Result::EXITCODE_ERROR) {
                return Result::success($task, $result->getOutputData());
            }
            return $result;
        }
        return $result;
    }

    /**
     * Build production artefacts.
     */
    protected function buildProduction($options = ['npm-script' => 'build:production'])
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
    protected function buildDevelopment($options = ['npm-script' => 'build'])
    {
        $this->taskNpmRun()
            ->script($options['npm-script'])
            ->dir($this->getThemePath())
            ->noProgress()
            ->run();
    }

    /**
     * Install production packages.
     */
    protected function installProduction()
    {
        $this->taskComposerInstall()
            ->noDev()
            ->noInteraction()
            ->optimizeAutoloader()
            ->option('quiet')
            ->run();

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
    protected function installDevelopment()
    {
        $this->taskComposerInstall()->run();
        $this->taskExec('vendor/bin/cghooks')->arg('update')->run();
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

    /**
     * Recursively search and replace placeholders in the project.
     *
     * @param  string  $from  The search string
     * @param  string  $to  The replacement value
     * @param  array  $options
     * @option $force (bool) Do not prompt for replacement
     */
    protected function searchReplace($from = null, $to = null, $options = [
        'force' => false,
    ])
    {
        if (empty($from)) {
            $from = $this->askDefault('Search placeholder to replace', '<example-project>');
        }
        if (empty($to)) {
            $to = $this->askDefault('Replace with', Robo::config()->get('machine_name'));
        }

        $result = $this->taskPlaceholderFind($from)
            ->io($this->io())
            ->run();

        $files = $result['files'];
        if (count($files) > 0) {
            if (!$options['force']) {
                $options['force'] = $this->io()->confirm(sprintf('Do you want to replace all instances of "%s" with "%s" in these files?', $from, $to));
            }

            if ($options['force']) {
                $this->taskPlaceholderReplace($from)
                    ->with($to)
                    ->in($files)
                    ->run();
            }
        }
    }

    /**
     * Setup a new project from scratch.
     *
     * @param  string  $machineName  Machine name
     * @param  array  $options
     * @option $remote  The remote git repository
     * @option $theme-repository  The git repository when creating the theme
     */
    protected function setup($machineName = null, $options = [
        'remote' => null,
        'theme-repository' => null,
    ])
    {
        $config = Robo::config();

        if (empty($machineName)) {
            $machineName = $this->askDefault('Project name (machine name)', $config->get('machine_name'));
        }
        if (empty($options['remote'])) {
            $options['remote'] = $this->askDefault('Remote Git repository', "git@github.com:{$config->get('organization')}/{$machineName}.git");
        }
        if (empty($options['theme-repository'])) {
            $options['theme-repository'] = $this->askDefault('Git repository to clone theme from', $config->get('command.setup.theme.options.theme-repository'));
        }

        // Modify robo.yml
        if (!empty($machineName)) {
            $this->setupYaml($machineName);
            $config = Robo::config();
        }

        // Clone theme
        if (!empty($options['theme-repository'])) {
            $this->setupTheme($config->get('theme_path'), $options);
        }

        // Set git remote
        if (!empty($options['remote'])) {
            $this->setupRemote($options['remote']);
        }

        // Search and replace all placeholders
        $this->searchReplace();

        // Install development packages
        $this->installDevelopment();
    }

    /**
     * Setup robo.yml
     *
     * @param  string  $machineName
     * @return \Robo\Result
     */
    protected function setupYaml($machineName)
    {
        $result = $this->taskYaml('robo.yml')
            ->set('machine_name', $machineName)
            ->run();

        if ($result['changes'] > 0) {
            $this->reloadConfig();
        }

        return $result;
    }

    /**
     * Setup a new theme.
     *
     * @param  string  $path
     * @param  array  $options
     * @option $theme-repository Remote repository to use when cloning a theme
     * @return \Robo\Result
     */
    protected function setupTheme($path, $options = ['theme-repository' => null])
    {
        return $this->taskGitClone($options['theme-repository'])
            ->path($path)
            ->depth(1)
            ->deleteGit()
            ->run();
    }

    /**
     * Setup the git remote.
     *
     * @param  string  $remote  Remote git URL
     * @return \Robo\Result
     */
    protected function setupRemote($remote)
    {
        return $this->taskGitRemote($remote)
            ->openGithub()
            ->run();
    }
}
