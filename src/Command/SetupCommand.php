<?php

namespace Generoi\Robo\Command;

use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Robo;

trait SetupCommand
{
    /**
     * Reload configuration in case it's been modified.
     */
    protected function reloadConfig(): void
    {
        Robo::loadConfiguration([getcwd() . '/robo.yml']);
    }

    /**
     * Setup a new project from scratch.
     *
     * @param  string  $machineName  Machine name
     * @param  array  $options
     * @option $remote  The remote git repository
     * @option $theme-repository  The git repository when creating the theme
     * @option $theme-branch  The git repository branch for the theme
     * @option $no-commit  Skip initial project commit
     *
     * @todo return task interface
     */
    public function setup(string $machineName = null, array $options = [
        'remote' => null,
        'theme-repository' => null,
        'theme-branch' => null,
        'no-commit' => false,
    ]): TaskInterface
    {
        $config = Robo::config();
        /** @var \Robo\Collection\CollectionBuilder $tasks */
        $tasks = $this->collectionBuilder();

        if (empty($machineName)) {
            $machineName = $this->askDefault('Project name (machine name)', $config->get('machine_name'));
        }
        if (!isset($options['remote'])) {
            $options['remote'] = $this->askDefault('Remote Git repository', "git@github.com:{$config->get('organization')}/{$machineName}.git");
        }
        if (!isset($options['theme-repository'])) {
            $options['theme-repository'] = $this->askDefault('Git repository to clone theme from', $config->get('command.setup.theme.options.theme-repository'));
        }
        if (!isset($options['theme-branch'])) {
            $options['theme-branch'] = $this->askDefault('Git repository branch', $config->get('command.setup.theme.options.theme-branch'));
        }

        $this->writeln('');
        $this->writeln(sprintf('  Machine name: <info>%s</info>', $machineName));
        $this->writeln(sprintf('  Git remote: <info>%s</info>', $options['remote']));
        $this->writeln(sprintf('  Theme repository: <info>%s</info>', $options['theme-repository']));
        $this->writeln('');

        // Modify robo.yml
        if (!empty($machineName)) {
            $tasks->addTask(
                $this->setupYaml($machineName)
            );
        }

        // Clone theme
        if (!empty($options['theme-repository'])) {
            $tasks->addTask(
                $this->setupTheme(null, $options)
            );
        }

        // Set git remote
        if (!empty($options['remote'])) {
            $tasks->addTask(
                $this->setupRemote($options['remote'])
            );
        }

        // Search and replace all placeholders
        $tasks->addTask(
            $this->searchReplace(null, null, $config->get('command.search.replace.options'))
        );

        // Commit search and replace changes
        if (empty($options['no-commit'])) {
            $tasks->addTask(
                $this->taskGitStack()
                    ->add('.')
                    ->commit('initial project setup', '--no-verify')
            );
        }

        // Install development packages
        $tasks->addTask(
            $this->installDevelopment()
        );

        // Build development artefacts
        $tasks->addTask(
            $this->buildDevelopment()
        );

        // Show outdated packages
        $tasks->addCode(function () {
            $result = $this->taskExec('composer')->printOutput(false)->arg('outdated')->run();

            if ($message = $result->getMessage()) {
                $this->writeln('');
                $this->writeln('<info>Consider updating the following packages:</info>');
                $this->writeln($message);
            }

            $this->writeln('');
            $this->yell('Done! You can now build the VM with: vagrant up');
        });

        return $tasks;
    }

    /**
     * Setup robo.yml
     *
     * @param  string  $machineName
     */
    public function setupYaml(string $machineName): TaskInterface
    {
        $tasks = $this->collectionBuilder();
        $tasks->addTask(
            $this->taskYaml('robo.yml')
                ->set('machine_name', $machineName)
        );

        $tasks->addCode(function () {
            $this->reloadConfig();
        });

        return $tasks;
    }

    /**
     * Setup a new theme.
     *
     * @param  string  $path
     * @param  array  $options
     * @option $theme-repository Remote repository to use when cloning a theme
     * @option $theme-branch  The git repository branch for the theme
     */
    public function setupTheme(string $path = null, $options = [
      'theme-repository' => null,
      'theme-branch' => null,
    ]): TaskInterface {
        if (empty($path)) {
            $path = Robo::config()->get('theme_path');
        }

        return $this->taskGitClone($options['theme-repository'])
            ->path($path)
            ->branch($options['theme-branch'])
            ->depth(1)
            ->deleteGit();
    }

    /**
     * Setup the git remote.
     *
     * @param  string  $remote  Remote git URL
     */
    public function setupRemote(string $remote): TaskInterface
    {
        return $this->taskGitRemote($remote)
            ->open();
    }
}
