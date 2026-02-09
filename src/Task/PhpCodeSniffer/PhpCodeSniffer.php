<?php

namespace Generoi\Robo\Task\PhpCodeSniffer;

use Robo;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Run PHP CodeSniffer
 *
 * ```php
 * <?php
 * $this->taskPhpCodeSniffer('src')->run();
 * ?>
 * ```
 */
class PhpCodeSniffer extends BaseTask
{
    use Robo\Common\ExecOneCommand;

    /**
     * @var string
     */
    protected $file;

    /**
     * @param  string  $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (! file_exists('./vendor/bin/phpcs')) {
            return Result::errorMissingPackage($this, 'phpcs', 'squizlabs/php_codesniffer');
        }

        return $this->executeCommand("./vendor/bin/phpcs {$this->file}");
    }
}
