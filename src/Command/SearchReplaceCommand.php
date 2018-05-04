<?php

namespace Generoi\Robo\Command;

use Robo\Robo;

trait SearchReplaceCommand
{
    /**
     * Recursively search and replace placeholders in the project.
     *
     * @param  string  $from  The search string
     * @param  string  $to  The replacement value
     * @param  array  $options
     * @option $force (bool) Do not prompt for replacement
     */
    public function searchReplace($from = null, $to = null, $options = [
        'force' => false,
    ])
    {
        if (empty($from)) {
            $from = $this->askDefault('Search placeholder to replace', '<example-project>');
        }
        if (empty($to)) {
            $to = $this->askDefault('Replace with', Robo::config()->get('machine_name'));
        }

        $result = $this->taskPlaceholderFind($from)
            ->io($this->io())
            ->run();

        $files = $result['files'];
        if (count($files) > 0) {
            if (!$options['force']) {
                $options['force'] = $this->io()->confirm(sprintf('Do you want to replace all instances of "%s" with "%s" in these files?', $from, $to));
            }

            if ($options['force']) {
                $this->taskPlaceholderReplace($from)
                    ->with($to)
                    ->in($files)
                    ->run();
            }
        }
    }
}
