<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Robo\Result;
use Generoi\Robo\Common\ThemeTrait;
use Robo\Contract\TaskInterface;

trait TestCommand
{
    use ThemeTrait;

    /**
     * Run test suite.
     *
     * @param  array  $options  Options
     * @option $composer (bool)
     * @option $sniff (bool)
     * @option $theme (bool)
     */
    public function test(
        array $options = ['composer' => true, 'sniff' => true, 'theme' => true]
    ): TaskInterface
    {
        /** @var \Robo\Collection\CollectionBuilder $tasks */
        $tasks = $this->collectionBuilder();
        if ($options['composer']) {
            $tasks->addTask($this->testComposer());
        }
        if ($options['sniff']) {
            $tasks->addCode(function () {
                return $this->testSniff();
            });
        }
        if ($options['theme']) {
            $tasks->addTask($this->testTheme());
        }
        return $tasks;
    }

    /**
     * Run PHP CodeSniffer.
     *
     * @param  string  $file  The path to sniff
     * @param  array  $options  Options
     * @option $autofix (bool) Automatically fix all problems
     *
     * @todo return task interface instead.
     */
    public function testSniff(string $file = '', array $options = [
        'autofix' => false,
    ]): Result
    {
        $result = $this->taskPhpCodeSniffer($file)->run();
        if (!$result->wasSuccessful() && !$options['autofix']) {
            $options['autofix'] = $this->confirm('Would you like to run phpcbf to fix the reported errors?');
        }
        if ($options['autofix']) {
            $subTask = $this->taskPhpCodeBeautifier($file);
            $result = $subTask->run();

            // PHPCBF used 1 as successful (sigh).
            if ($result->getExitCode() === Result::EXITCODE_ERROR) {
                return Result::success($subTask, $result->getOutputData());
            }
            return $result;
        }
        return $result;
    }

    /**
     * Run Theme tests
     *
     * @param  array  $options  Options
     * @option $command (string) Npm command to run
     */
    public function testTheme(array $options = ['command' => 'test']): TaskInterface
    {
        return $this->taskNpmRun()
            ->script($options['command'])
            ->dir($this->getThemePath());
    }

    /**
     * Run Composer validation
     *
     * @param  array  $options  Options
     * @option $noCheckAll (bool) Skip some checks
     */
    public function testComposer(array $options = ['noCheckAll' => true]): TaskInterface
    {
        $task = $this->taskComposerValidate();
        if ($options['noCheckAll']) {
            $task->noCheckAll();
        }
        return $task;
    }
}
