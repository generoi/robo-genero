<?php
namespace Generoi\Robo\Task\Git;

use Robo\Task\BaseTask;
use Robo\Exception\TaskException;

abstract class Base extends BaseTask
{
    use \Robo\Common\ExecOneCommand;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var string[]
     */
    protected $opts = [];

    /**
     * @var string
     */
    protected $action = '';

    /**
     * @param null|string $pathToGit
     *
     * @throws \Robo\Exception\TaskException
     */
    public function setGitCommand($pathToGit = null)
    {
        $this->command = $pathToGit;

        if (!$this->command) {
            $this->command = $this->findExecutable('git');
        }
        if (!$this->command) {
            throw new TaskException(__CLASS__, "Git executable not found.");
        }
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        if (!$this->command) {
            $this->setGitCommand();
        }
        return "{$this->command} {$this->action}{$this->arguments}";
    }
}
