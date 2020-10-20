<?php

namespace Generoi\Robo\Task\GitHub;

use Robo\Result;
use Robo\Task\Development\GitHub;

/**
 * Npm Run script
 *
 * ```php
 * <?php
 * $this->taskGitHubWorkflowDispatch('build_deploy_production.yml')
 *     ->reference($reference)
 *     ->input('log_level', '-vv')
 *     ->input('ref', '')
 *     ->accessToken($token)
 *     ->post(['ref' => ''])
 *     ->run();
 * ```
 */
class GitHubWorkflowDispatch extends GitHub
{
    /**
     * @var string
     */
    protected $workflow;

    /**
     * @var string
     */
    protected $reference;

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * @param string $workflow
     */
    public function __construct(string $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * @param string $reference
     */
    public function reference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function input(string $key, string $value): self
    {
        $this->inputs[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->printTaskInfo('Dispatching workflow {workflow}', ['workflow' => $this->workflow]);
        $this->startTimer();
        list($code, $data) = $this->sendRequest(
            sprintf('actions/workflows/%s/dispatches', $this->workflow),
            [
                'ref' => $this->reference,
                'inputs' => $this->inputs,
            ]
        );
        $this->stopTimer();

        return new Result(
            $this,
            $code === 204 ? 0 : 1,
            isset($data->message) ? $data->message : '',
            ['response' => $data, 'time' => $this->getExecutionTime()]
        );
    }
}
