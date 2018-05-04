<?php

namespace Generoi\Robo\Task\Git;

use Robo;
use Robo\Result;

/**
 * Setup Bedrock project.
 *
 * ``` php
 * <?php
 * $this->taskGitRemote('git@github.com:generoi/foobar.git')
 *     ->open()
 *     ->run();
 * ?>
 * ```
 */
class GitRemote extends Base
{
    /**
     * @var string
     */
    protected $action = 'remote set-url';

    /**
     * @var string
     */
    protected $remote;

    /**
     * @var string
     */
    protected $name = 'origin';

    /**
     * @var bool
     */
    protected $open = false;

    /**
     * @param  string  $remote
     */
    public function __construct(string $remote)
    {
        $this->remote = $remote;
        $this->setGitCommand();
    }

    /**
     * Open Github create repoistory page in the browser.
     *
     * @param  bool  $open
     * @return $this
     */
    public function open($open = true)
    {
        $this->open = $open;
        return $this;
    }

    /**
     * Get the Git user/organization of the remote repository.
     *
     * @return string
     */
    protected function getGitUser()
    {
        list(, $path) = explode(':', $this->remote);
        list($user, $repo) = explode('/', $path);
        return $user;
    }

    /**
     * Get the currently configured remote repository.
     *
     * @return string
     */
    protected function getCurrentRemote()
    {
        $result = $this->printOutput(false)
            ->executeCommand("$this->command remote get-url $this->name");

        $this->printOutput(true);
        return trim($result->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->arg($this->name);
        $this->arg($this->remote);

        if ($this->remote !== $this->getCurrentRemote()) {
            $result = $this->executeCommand($this->getCommand());

            if (!$result->wasSuccessful()) {
                return $result;
            }
            if ($this->open) {
                $this->executeCommand("open https://github.com/organizations/{$this->getGitUser()}/repositories/new");
            }
            $this->printTaskSuccess('Changed git remote');
        } else {
            $this->printTaskInfo('Git remote unchanged');
        }

        return Result::success($this, '');
    }
}
