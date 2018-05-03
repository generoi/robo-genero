<?php

namespace Generoi\Robo\Task;

use Robo;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Run PHP CodeBeautifier
 *
 * ```php
 * <?php
 * $this->taskPhpCodeBeautifier('src')->run();
 * ?>
 * ```
 */
class PhpCodeBeautifier extends BaseTask
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
        if (!file_exists('./vendor/bin/phpcbf')) {
            return Result::errorMissingPackage($this, 'phpcs', 'squizlabs/php_codesniffer');
        }
        return $this->executeCommand("./vendor/bin/phpcbf {$this->file}");
    }
}
