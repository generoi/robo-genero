<?php

namespace Generoi\Robo\Command;

use Robo\Contract\TaskInterface;
use Robo\Robo;

trait SearchReplaceCommand
{
    /**
     * Recursively search and replace placeholders in the project.
     *
     * @param  string  $from  The search string
     * @param  string  $to  The replacement value
     * @param  array  $options
     *
     * @option $dirs (string|array) Directories to search in
     * @option $exclude (string|array) Paths to exclude from search
     */
    public function searchReplace($from = null, $to = null, $options = [
        'force' => false,
        'dirs' => [],
        'exclude' => [],
    ]): TaskInterface
    {
        if (empty($from)) {
            $from = $this->askDefault('Search placeholder to replace', '<example-project>');
        }
        if (empty($to)) {
            $to = $this->askDefault('Replace with', Robo::config()->get('machine_name'));
        }

        $tasks = $this->collectionBuilder();

        $result = $this->taskPlaceholderFind($from, $options)
            ->directories($options['dirs'])
            ->exclude($options['exclude'])
            ->io($this->io())
            ->run()
            ->stopOnFail();

        $files = $result['files'];
        if (count($files) > 0) {
            if (empty($options['force'])) {
                $options['force'] = $this->io()->confirm(sprintf('Do you want to replace all instances of "%s" with "%s" in these files?', $from, $to));
            }

            if (! empty($options['force'])) {
                $tasks->addTask(
                    $this->taskPlaceholderReplace($from)
                        ->with($to)
                        ->in($files)
                );
            }
        }

        return $tasks;
    }
}
