<?php

namespace Generoi\Robo\Installer;

use Composer\Script\Event;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ComposerScript
{
    /**
     * @var \Composer\Script\Event
     */
    public $event;

    public function __construct(Event $event = null)
    {
        $this->event = $event;
    }

    public static function postCreateProject(Event $event)
    {
        self::robo('setup', $event);
    }

    public static function test(Event $event)
    {
        self::robo('test', $event);
    }

    public static function robo($command, Event $event)
    {
        $robo = getcwd() . '/vendor/bin/robo';
        return (new static($event))
            ->validate()
            ->run(new Process(sprintf('php %s %s', $robo, $command)));
    }

    public function validate()
    {
        if (!$this->isInteractive()) {
            $this->write('Interactive mode disabled. Skipping parts of post-create-project routine.');
        }
        return $this;
    }

    protected function isInteractive()
    {
        return $this->event->getIO()->isInteractive();
    }

    protected function write($message)
    {
        $this->event->getIO()->write($message);
    }

    public function run(Process $process)
    {
        try {
            $process->setTty($this->isInteractive());
        } catch (RuntimeException $e) {
            // do nothing.
        }

        $process->run();
        return $this;
    }
}
