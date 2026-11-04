<?php

namespace Generoi\Robo\Common;

use Robo\Robo;

trait PackageManagerTrait
{
    /**
     * Get the package manager to run in a directory.
     *
     * Uses `package_manager` from robo.yml if set. Otherwise walks up from
     * $dir to the project root and takes the first directory with a
     * package.json `packageManager` field or a lockfile, so a workspace
     * member without its own lockfile follows the root.
     *
     * @param  string|null  $dir
     * @return string|null `pnpm` or `yarn`, null for npm so Robo resolves
     *                     the npm executable as before.
     */
    protected function getPackageManager($dir)
    {
        $manager = Robo::config()->get('package_manager') ?: $this->detectPackageManager($dir);

        return $manager === 'npm' ? null : $manager;
    }

    /**
     * @param  string|null  $dir
     * @return string
     */
    private function detectPackageManager($dir)
    {
        $root = realpath(getcwd());
        $dir = realpath($dir ?: '.') ?: $root;

        while (true) {
            $package = is_file("$dir/package.json") ? json_decode(file_get_contents("$dir/package.json"), true) : null;
            if (! empty($package['packageManager'])) {
                return strtok($package['packageManager'], '@');
            }
            if (file_exists("$dir/pnpm-lock.yaml")) {
                return 'pnpm';
            }
            if (file_exists("$dir/yarn.lock")) {
                return 'yarn';
            }
            if (file_exists("$dir/package-lock.json")) {
                return 'npm';
            }
            if ($dir === $root || strpos($dir, $root.DIRECTORY_SEPARATOR) !== 0) {
                return 'npm';
            }
            $dir = dirname($dir);
        }
    }
}
