<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Robo\Result;
use Generoi\Robo\Common\ThemeTrait;

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
    public function test($options = ['composer' => true, 'sniff' => true, 'theme' => true])
    {
        if ($options['composer']) {
            $this->testComposer()->stopOnFail();
        }
        if ($options['sniff']) {
            $this->testSniff()->stopOnFail();
        }
        if ($options['theme']) {
            $this->testTheme()->stopOnFail();
        }
    }

    /**
     * Run PHP CodeSniffer.
     *
     * @param  string  $file  The path to sniff
     * @param  array  $options  Options
     * @option $autofix (bool) Automatically fix all problems
     */
    public function testSniff($file = '', $options = [
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
     * Run Theme tests
     *
     * @param  array  $options  Options
     * @option $command (string) Npm command to run
     */
    public function testTheme($options = ['command' => 'test'])
    {
        return $this->taskNpmRun()->script($options['command'])->dir($this->getThemePath())->run();
    }

    /**
     * Run Composer validation
     *
     * @param  array  $options  Options
     * @option $noCheckAll (bool) Skip some checks
     */
    public function testComposer($options = ['noCheckAll' => true])
    {
        $task = $this->taskComposerValidate();
        if ($options['noCheckAll']) {
            $task->noCheckAll();
        }
        return $task->run();
    }
}
