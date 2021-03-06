<?php

namespace Generoi\Robo\Task\Placeholder;

use Robo;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Finder\Finder;

/**
 * Replace placeholder values.
 *
 * ```php
 * <?php
 * $this->taskPlaceholderFind('<example-project>')
 *     ->checkRootFiles(false)
 *     ->directories(['web/app'])
 *     ->exclude(['vendor'])
 *     ->run();
 * ```
 */
class Find extends BaseTask
{
    /**
     * @var \Robo\Common\IO;
     */
    protected $io;

    /**
     * @var string
     */
    protected $placeholder;

    /**
     * @var bool
     */
    protected $checkRootFiles = true;

    /**
     * @var array
     */
    protected $exclude = [];

    /**
     * Binary path matches that are always excluded.
     */
    protected $forceExclude = [
        '.map',
        '.lock',
        '.jpg',
        '.jpeg',
        '.gif',
        '.png',
        '.eot',
        '.ttf',
        '.svg',
        '.woff',
        '.woff2',
        'node_modules',
        'vendor',
        '.cache-loader',
        '.sass-cache',
    ];

    /**
     * @var array
     */
    protected $dirs = [
        '.'
    ];

    /**
     * @param  null|string  $placeholder Placeholder to search for
     */
    public function __construct($placeholder = null)
    {
        $this->placeholder($placeholder);
    }

    /**
     * @param  string  $placeholder Placeholder to search for
     * @return $this
     */
    public function placeholder($string)
    {
        $this->placeholder = $string;
        return $this;
    }

    /**
     * Whether to check root files or not.
     *
     * @param  bool  $check
     * @return $this
     */
    public function checkRootFiles($check = true)
    {
        $this->printTaskInfo('Checking root files');
        $this->checkRootFiles = $check;
        return $this;
    }

    /**
     * Directories to check.
     *
     * @param  string|array  $directories
     * @return $this
     */
    public function directories($directories)
    {
        if (!$directories) {
            return $this;
        }
        $this->dirs = is_string($directories) ? explode(',', $directories) : (array) $directories;
        return $this;
    }

    /**
     * Paths to exclude
     *
     * @param  string|array  $exclude
     * @return $this
     */
    public function exclude($exclude)
    {
        if (!$exclude) {
            return $this;
        }
        $this->exclude = is_string($exclude) ? explode(',', $exclude) : (array) $exclude;
        return $this;
    }

    /**
     * Set the IO instance for printing a list of files found.
     *
     * @param  \Robo\Common\IO  $io
     * @return $this
     */
    public function io($io)
    {
        $this->io = $io;
        return $this;
    }

    /**
     * Search through all files where a placeholder text might exist and return
     * the files with matches.
     *
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findFiles()
    {
        $rootFinder = new Finder();
        $rootFinder->files()
            ->in('.')
            ->depth('== 0')
            ->ignoreDotFiles(false)
            ->contains("/{$this->placeholder}/");

        $finder = new Finder();
        $finder->files()
            ->in($this->dirs)
            ->append($rootFinder)
            ->ignoreDotFiles(false)
            ->contains("/{$this->placeholder}/");

        $excludes = array_merge($this->forceExclude, $this->exclude);
        $this->printTaskInfo('Checking directories: <info>{directories}</info>', ['directories' => implode(', ', $this->dirs)]);
        $this->printTaskInfo('Excluding: <info>{excludes}</info>', ['excludes' => implode(', ', $excludes)]);

        foreach ($excludes as $exclude) {
            $rootFinder->notPath($exclude);
            $finder->notPath($exclude);
        }

        return $finder;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (empty($this->placeholder)) {
            return Result::error($this, 'You must specify a string to search for with the placeholder() method.');
        }

        $files = array_map(function ($file) {
            return ltrim(str_replace(getcwd(), '', $file->getPathname()), '/');
        }, iterator_to_array($this->findFiles()));

        $this->printTaskInfo('Found <info>' . count($files) . '</info> files with matches');

        if ($this->io) {
            $this->io->listing($files);
        }

        return Result::success($this, '', ['files' => $files]);
    }
}
