<?php

namespace Generoi\Robo\Command;

use Robo\ResultData;
use Robo\Robo;

trait ConfigCommand
{
    /**
     * Read configuration values from robo.yml.
     *
     * @param  string  $option  Option key eg. `env.@production.host`
     */
    public function config(string $option): ResultData
    {
        $config = Robo::config();
        if ($config->has($option)) {
            $this->writeln($config->get($option));
            return new ResultData(ResultData::EXITCODE_OK);
        }

        return new ResultData(ResultData::EXITCODE_ERROR);
    }
}
