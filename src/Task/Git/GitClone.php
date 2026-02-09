<?php

namespace Generoi\Robo\Task\Git;

use Robo\Common\ResourceExistenceChecker;
use Robo\Result;
use Symfony\Component\Filesystem\Filesystem as sfFilesystem;

/**
 * Setup Bedrock project.
 *
 * ``` php
 * <?php
 * $this->taskGitClone('git@github.com/generoi/sage.git')
 *     ->path('web/app/themes/foobar')
 *     ->depth(1)
 *     ->deleteGit()
 *     ->run();
 * ?>
 * ```
 */
class GitClone extends Base
{
    use ResourceExistenceChecker;

    /**
     * @var string
     */
    protected $action = 'clone';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var bool
     */
    protected $deleteGit;

    public function __construct(string $remote)
    {
        $this->arg($remote);
        $this->fs = new sfFilesystem;
    }

    /**
     * Set the clone depth.
     *
     * @return $this
     */
    public function depth(int $depth)
    {
        $this->option('depth', $depth);

        return $this;
    }

    /**
     * Set the clone branch.
     *
     * @return $this
     */
    public function branch(string $branch)
    {
        $this->option('branch', $branch);

        return $this;
    }

    /**
     * Delete .git/ folder
     *
     * @param  bool  $delete
     * @return $this
     */
    public function deleteGit($delete = true)
    {
        $this->deleteGit = $delete;

        return $this;
    }

    /**
     * Set the relative theme path where to clone to
     *
     * @param  string  $path
     * @return $this
     */
    public function path($path)
    {
        $this->arg($this->path = $path);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (empty($this->path)) {
            return Result::error($this, 'You must specify a destination path with the path() method.');
        }
        if (file_exists($this->path)) {
            $this->printTaskError('Destination path {dir} already exists', ['dir' => $this->path]);

            return Result::success($this);
        }

        $result = $this->executeCommand($this->getCommand());
        if (! $result->wasSuccessful()) {
            return $result;
        }

        if ($this->deleteGit) {
            $gitDir = "{$this->path}/.git";
            if (! $this->checkResources($gitDir, 'dir')) {
                return Result::error($this, 'Git directories are missing!');
            }
            $this->fs->remove($gitDir);
            $this->printTaskInfo('Deleted {dir}...', ['dir' => $gitDir]);
        }

        return Result::success($this);
    }
}
