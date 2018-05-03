<?php

namespace Generoi\Robo\Task\Placeholder;

use Robo;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Replace placeholder values.
 *
 * ```php
 * <?php
 * $result = $this->taskPlaceholderFind('<example-project>')->run();
 *
 * $this->taskPlaceholderReplace($result['files'])
 *     ->from('<example-project>')
 *     ->to('foobar')
 *     ->run();
 * ```
 */
class Replace extends BaseTask
{
    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @var array
     */
    protected $files;

    /**
     * @param  string  $from  String to search for
     */
    public function __construct(string $from)
    {
        $this->from = $from;
    }

    /**
     * @param  string[]  $files  Files to search and replace in
     * @return $this
     */
    public function in(array $files)
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Value to replace all matches with.
     *
     * @param  string  $to  Replacement value
     * @return $this
     */
    public function with($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (empty($this->to)) {
            return Result::error($this, 'You must specify a replacement string with the with() method.');
        }
        if (empty($this->files)) {
            return Result::error($this, 'You must specify the files to search in with the in() method.');
        }

        foreach ($this->files as $file) {
            $text = file_get_contents($file);
            $text = str_replace($this->from, $this->to, $text, $count);

            if ($count > 0) {
                $res = file_put_contents($file, $text);
                if ($res === false) {
                    return Result::error($this, "Error writing to file {filename}.", ['filename' => $file]);
                }
                $this->printTaskSuccess("{filename} updated. {count} items replaced", ['filename' => $file, 'count' => $count]);
            } else {
                $this->printTaskInfo("{filename} unchanged. {count} items replaced", ['filename' => $file, 'count' => $count]);
            }
        }
        return Result::success($this, '', ['replaced' => $count]);
    }
}
