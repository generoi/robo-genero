<?php

namespace Generoi\Robo\Command;

use Robo\Contract\TaskInterface;
use Robo\Exception\TaskException;
use Robo\Robo;

trait DeployCommand
{
    /**
     * Trigger a GitHub Deploy Action
     *
     * @param  string  $workflow  Workflow file
     *
     * @option $uri (string) GitHub repository URI
     * @option $branch (string) Branch to deploy
     * @option $log_level (string) Log Level
     * @option $ref (string) Optional tag to checkout
     */
    public function deploy(?string $workflow = null, array $options = [
        'uri' => null,
        'branch' => 'master',
        'log_level' => '-vv',
        'ref' => '',
    ]): TaskInterface
    {
        $githubToken = $this->taskComposerConfig()
            ->arg('github-oauth.github.com')
            ->useGlobal()
            ->printOutput(false)
            ->run();

        if (! $githubToken->wasSuccessful()) {
            throw new TaskException($this, 'You need to set up a personal GitHub Access Token with composer to deploy');
        }

        return $this->taskGitHubWorkflowDispatch($workflow)
            ->uri($options['uri'] ?? Robo::config()->get('github'))
            ->reference($options['branch'] ?? 'master')
            ->input('log_level', $options['log_level'] ?? '-vv')
            ->input('ref', $options['ref'] ?? '')
            ->accessToken($githubToken->getMessage());
    }

    /**
     * Trigger a GitHub Deploy Action to production
     *
     * @option $workflow (string) Workflow file
     * @option $uri (string) GitHub repository URI
     * @option $branch (string) Branch to deploy
     * @option $log_level (string) Log Level
     * @option $ref (string) Optional tag to checkout
     */
    public function deployProduction(array $options = [
        'workflow' => null,
        'uri' => null,
        'branch' => null,
        'log_level' => null,
        'ref' => null,
    ]): TaskInterface
    {
        return $this->deploy($options['workflow'], $options);
    }

    /**
     * Trigger a GitHub Deploy Action to staging
     *
     * @option $workflow (string) Workflow file
     * @option $uri (string) GitHub repository URI
     * @option $branch (string) Branch to deploy
     * @option $log_level (string) Log Level
     * @option $ref (string) Optional tag to checkout
     */
    public function deployStaging(array $options = [
        'workflow' => null,
        'uri' => null,
        'branch' => null,
        'log_level' => null,
        'ref' => null,
    ]): TaskInterface
    {
        return $this->deploy($options['workflow'], $options);
    }
}
