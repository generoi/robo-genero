<?php

namespace Generoi\Robo\Task;

use Robo;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Yaml\Yaml as YamlLib;
use Dflydev\DotAccessData\Data;

/**
 * Setup Bedrock project.
 *
 * ``` php
 * <?php
 * $this->taskYaml('robo.yml')
 *     ->set('machine_name', 'foobar')
 *     ->run();
 * ?>
 * ```
 */
class Yaml extends BaseTask
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var \Dflydev\DotAccessData\Data
     */
    protected $data;
    /**
     * @var array
     */
    protected $set = [];

    /**
     * @var array
     */
    protected $delete = [];

    /**
     * @var int
     */
    protected $changes = 0;

    /**
     * @var int
     */
    protected $expandLevel = 8;

    /**
     * @var int
     */
    protected $indentation = 2;

    /**
     * @param  string  $file  Yaml file to modify
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        $this->data = new Data(YamlLib::parse(file_get_contents($this->file)));
    }

    /**
     * Set a value
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set(string $key, $value)
    {
        $this->set[$key] = $value;
        return $this;
    }

    /**
     * Set a value
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function delete(string $key)
    {
        $this->delete[] = $key;
        return $this;
    }

    /**
     * Set values.
     * @return $this
     */
    protected function processSet()
    {
        foreach ($this->set as $key => $value) {
            $currentValue = $this->data->get($key);
            if ($currentValue !== $value) {
                $this->changes++;

                $this->data->set($key, $value);
                $this->printTaskSuccess("{key} updated", ['key' => $key]);
            } else {
                $this->printTaskInfo("{key} unchanged", ['key' => $key]);
            }
        }

        return $this;
    }

    /**
     * Delete values.
     *
     * @return $this
     */
    protected function processDelete()
    {
        foreach ($this->delete as $key) {
            $hasValue = $this->data->has($key);
            if ($hasValue) {
                $this->changes++;

                $this->data->delete($key);
                $this->printTaskSuccess("{key} deleted", ['key' => $key]);
            } else {
                $this->printTaskInfo("{key} unchanged", ['key' => $key]);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->processSet();
        $this->processDelete();

        if ($this->changes > 0) {
            $yaml = YamlLib::dump($this->data->export(), $this->expandLevel, $this->indentation);
            $res = file_put_contents($this->file, $yaml);
            if ($res === false) {
                return Result::error($this, "Error writing to file {filename}.", ['filename' => $this->file]);
            }
        }
        return Result::success($this, '', ['changes' => $this->changes]);
    }
}
