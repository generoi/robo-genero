<?php

namespace Generoi\Robo\Command;

use Robo\Robo;

trait SetupCommand
{
    /**
     * Reload configuration in case it's been modified.
     */
    protected function reloadConfig()
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
     */
    public function setup($machineName = null, $options = [
        'remote' => null,
        'theme-repository' => null,
        'theme-branch' => null,
        'no-commit' => false,
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
        if (empty($options['theme-branch'])) {
            $options['theme-branch'] = $this->askDefault('Git repository branch', $config->get('command.setup.theme.options.theme-branch'));
        }

        $this->writeln('');
        $this->writeln(sprintf('  Machine name: <info>%s</info>', $machineName));
        $this->writeln(sprintf('  Git remote: <info>%s</info>', $options['remote']));
        $this->writeln(sprintf('  Theme repository: <info>%s</info>', $options['theme-repository']));
        $this->writeln('');

        // Modify robo.yml
        if (!empty($machineName)) {
            $this->writeln(sprintf('Running <info>%s</info>', 'setup:yaml'));
            $this->setupYaml($machineName)->stopOnFail();
            $config = Robo::config();
        }

        // Clone theme
        if (!empty($options['theme-repository'])) {
            $this->writeln(sprintf('Running <info>%s</info>', 'setup:theme'));
            $this->setupTheme($config->get('theme_path'), $options)->stopOnFail();
        }

        // Set git remote
        if (!empty($options['remote'])) {
            $this->writeln(sprintf('Running <info>%s</info>', 'setup:remote'));
            $this->setupRemote($options['remote'])->stopOnFail();
        }

        // Search and replace all placeholders
        $this->writeln(sprintf('Running <info>%s</info>', 'search:replace'));
        $this->searchReplace(null, null, $config->get('command.search.replace.options'))->stopOnFail();

        // Commit search and replace changes
        if (!empty($options['no-commit'])) {
            $this->taskGitStack()
                ->add('.')
                ->commit('initial project setup', '--no-verify')
                ->run()
                ->stopOnFail();
        }

        // Install development packages
        $this->writeln(sprintf('Running <info>%s</info>', 'install:development'));
        $this->installDevelopment();

        // Build development artefacts
        $this->writeln(sprintf('Running <info>%s</info>', 'build:development'));
        $this->buildDevelopment()->stopOnFail();

        // Show outdated packages
        $result = $this->taskExec('composer')->printOutput(false)->arg('outdated')->run();
        if ($message = $result->getMessage()) {
            $this->writeln('');
            $this->writeln('<info>Consider updating the following packages:</info>');
            $this->writeln($message);
        }

        $this->writeln('');

        $this->yell('Done! You can now build the VM with: vagrant up');
    }

    /**
     * Setup robo.yml
     *
     * @param  string  $machineName
     * @return \Robo\Result
     */
    public function setupYaml($machineName)
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
     * @option $theme-branch  The git repository branch for the theme
     * @return \Robo\Result
     */
    public function setupTheme($path, $options = [
      'theme-repository' => null,
      'theme-branch' => null,
    ]) {
        return $this->taskGitClone($options['theme-repository'])
            ->path($path)
            ->branch($options['theme-branch'])
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
    public function setupRemote($remote)
    {
        return $this->taskGitRemote($remote)
            ->open()
            ->run();
    }
}
