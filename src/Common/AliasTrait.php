<?php

namespace Generoi\Robo\Common;

use Robo\Robo;

trait AliasTrait
{
    /**
     * Parse an aliased source/destination string.
     *
     * @param  string  $string A remote in the format `alias:%files`
     * @return array
     */
    protected function parseAlias($string)
    {
        $config = Robo::config();
        if (strpos($string, ':') !== false) {
            // Has both remote and path
            list($env, $path) = explode(':', $string);
        } elseif ($config->has("env.$string")) {
            // Has only remote
            list($env, $path) = [$string, null];
        } elseif ($string === 'self') {
            // Has only local
            list($env, $path) = ['self', null];
        } else {
            // Has only path
            list($env, $path) = ['self', $string];
        }

        if ($env === 'self') {
            // Specifically set to local
            $result['path'] = getcwd();
        } elseif ($config->has("env.$env")) {
            // Has an environment alias defined
            $result = $config->get("env.$env");
        } else {
            // Native rsync remote
            if (strpos($env, '@') !== false) {
                // Has a username and a hostname
                list($user, $host) = explode('@', $env);
                $result['user'] = $user;
                $result['host'] = $host;
            } else {
                // SSH alias or invalid
                $result['host'] = $env;
            }
        }

        // Provide placeholders defined in the config.
        if ($path) {
            $path = $this->replacePlaceholders($path);

            if ($path[0] === '/' || $path[0] === '~') {
                // Absolute path
                $result['path'] = $path;
            } else {
                // Relative path
                $result['path'] = rtrim($result['path'], '/') . '/' . ltrim($path, '/');
                $result['relativePath'] = $path;
            }
        }

        return $result;
    }

    /**
     * Replace all placeholders with their appropriate values defined in
     * `robo.yml`.
     *
     * @param  string  $string
     * @return string
     */
    protected function replacePlaceholders(string $string)
    {
        if ($placeholders = Robo::config()->get('placeholders')) {
            return str_replace(array_keys($placeholders), array_values($placeholders), $string);
        }
        return $string;
    }
}
