<?php

namespace Generoi\Robo\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ComposerScript
{
    /**
     * @var \Composer\Script\Event
     */
    public $event;

    /**
     * @param  \Composer\Script\Event
     */
    public function __construct(?Event $event = null)
    {
        $this->event = $event;
    }

    /**
     * Run the robo setup command.
     *
     * @param  \Composer\Script\Event
     * @return $this
     */
    public static function postCreateProject(Event $event)
    {
        return (new static($event))->validate()->robo('setup');
    }

    /**
     * Delegate all calls directly to robo.
     */
    public static function __callStatic($name, $arguments)
    {
        [$event] = $arguments;
        $command = preg_replace('/[A-Z]/', ':$1', $name);
        $result = (new static($event))->robo($command);
    }

    /**
     * Execute a robo command.
     *
     * @param  string  $command
     * @return $this
     */
    public function robo($command)
    {
        $robo = getcwd().'/vendor/bin/robo';
        $commands = explode(' ', $command);
        array_unshift($commands, 'php', $robo);

        return $this->run(new Process($commands));
    }

    /**
     * Verify that interactive mode is available.
     *
     * @return $this
     */
    public function validate()
    {
        if (! $this->isInteractive()) {
            throw new RuntimeException('Interactive mode is required');
        }

        return $this;
    }

    /**
     * Check if this is interactive mode.
     *
     * @return bool
     */
    protected function isInteractive()
    {
        return $this->event->getIO()->isInteractive();
    }

    /**
     * Run a process.
     *
     * @return $this
     */
    public function run(Process $process)
    {
        try {
            $process->setTty($this->isInteractive());
        } catch (RuntimeException $e) {
            // do nothing.
        }

        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Error Output: '.$process->getErrorOutput(), $process->getExitCode());
        }

        return $process;
    }
}
