<?php

namespace Generoi\Robo\Task\Wp;

use Robo\Common\CommandArguments;
use Robo\Task\CommandStack;

/**
 * Runs Drush commands in stack. You can use `stopOnFail()` to point that stack should be terminated on first fail.
 * You can define global options for all commands (like Drupal root and uri).
 * The option -y is always set, as it makes sense in a task runner.
 *
 * ``` php
 * $this->taskWpCliStack()
 *     ->drupalRootDirectory('/var/www/html/some-site')
 *     ->uri('sub.example.com')
 *     ->maintenanceOn()
 *     ->updateDb()
 *     ->revertAllFeatures()
 *     ->maintenanceOff()
 *     ->run();
 * ```
 */
class WpCliStack extends CommandStack
{
    use CommandArguments;

    protected $argumentsForNextCommand;

    /**
     * Site alias.
     * We need to save this, since it needs to be the first argument.
     *
     * @var string
     */
    protected $siteAlias;

    /**
     * Alias specific wp cli executable paths
     *
     * @var arary
     */
    protected $aliasExecutable = [];
    
    protected $defaultExecutable;

    public function __construct($wpCliPath = 'wp')
    {
        $this->executable($wpCliPath);
    }

    /**
     * {@inheritdoc}
     */
    public function executable($executable)
    {
        $this->executable = $executable;
        $this->defaultExecutable = $executable;
        return $this;
    }

    /**
     * Set which alias to use for the command.
     *
     * @param  string  $alias
     * @param  string  $executable
     * @return $this
     */
    public function siteAlias($alias, $executable = null)
    {
        if ($alias === 'self') {
            $alias = null;
        }
        $this->siteAlias = $alias;
        $this->executable = $executable ?: $this->aliasExecutable[$alias] ?? $this->defaultExecutable;
        return $this;
    }

    public function setAliasExecutable($alias, $executable = null)
    {
        $this->aliasExecutable[$alias] = $executable ?? $this->defaultExecutable;
    }

    /**
     * Path to the WordPress files.
     * Global option.
     *
     * @param  string  $wpRootDirectory
     * @return $this
     */
    public function wpRootDirectory($wpRootDirectory)
    {
        $this->printTaskInfo('WP root: <info>' . $wpRootDirectory . '</info>');
        $this->option('path', $wpRootDirectory);
        return $this;
    }

    /**
     * Pretend request came from given URL. In multisite, this argument is how
     * the target site is specified.
     * Global option.
     *
     * @param  string  $url
     * @return $this
     */
    public function url($url)
    {
        $this->printTaskInfo('URL: <info>' . $url . '</info>');
        $this->option('url', $url);
        return $this;
    }

    /**
     * Perform operation against a remote server over SSH.
     * Global option.
     *
     * @param  string  $ssh
     * @return $this
     */
    public function ssh($ssh)
    {
        $this->argForNextCommand('--ssh=' . escapeshellarg($ssh));
        return $this;
    }

    /**
     * Perform operation against a remote WordPress install over HTTP.
     * Global option.
     *
     * @param  string  $http
     * @return $this
     */
    public function http($http)
    {
        $this->argForNextCommand('--http=' . escapeshellarg($http));
        return $this;
    }

    /**
     * Set the WordPress user.
     * Global option.
     *
     * @param  string  $user  Id, login or email
     * @return $this
     */
    public function user($user)
    {
        $this->argForNextCommand('--user=' . $user);
        return $this;
    }

    /**
     * Skip loading all or some plugins. Note: mu-plugins are still loaded.
     * Global option.
     *
     * @param  string[]  $plugins
     * @return $this
     */
    public function skipPlugins($plugins = [])
    {
        $this->argForNextCommand('--skip-plugins' . $this->toCommaList($plugins, true));
        return $this;
    }

    /**
     * Skip loading all or some themes.
     * Global option.
     *
     * @param  string[]  $themes
     * @return $this
     */
    public function skipThemes($themes = [])
    {
        if (!is_array($themes)) {
            $themes = [$themes];
        }
        $this->argForNextCommand('--skip-themes' . $this->toCommaList($themes, true));
        return $this;
    }

    /**
     * Skip loading all installed packages.
     * Global option.
     *
     * @return $this
     */
    public function skipPackages()
    {
        $this->argForNextCommand('--skip-packages');
        return $this;
    }

    /**
     * Load PHP file before running the command (may be used more than once).
     * Global option.
     *
     * @param  string  $path
     * @return $this
     */
    public function require($path)
    {
        $this->argForNextCommand('--require=' . escapeshellarg($path));
        return $this;
    }

    /**
     * Show all PHP errors; add verbosity to WP-CLI bootstrap.
     * Global option.
     *
     * @return $this
     */
    public function debug()
    {
        $this->option(__FUNCTION__);
        return $this;
    }

    /**
     * Suppress informational messages.
     * Global option.
     *
     * @return $this
     */
    public function quiet()
    {
        $this->option(__FUNCTION__);
        return $this;
    }

    /**
     * Exclude tables from export.
     * `wp db export` option.
     *
     * @param  string|array  $tables
     * @return $this
     */
    public function excludeTables($tables)
    {
        $this->argForNextCommand('--exclude_tables=' . $this->toCommaList($tables));
        return $this;
    }

    /**
     * Specific tables to export.
     * `wp db export` option.
     *
     * @param  string|array  $tables
     * @return $this
     */
    public function tables($tables)
    {
        $this->argForNextCommand('--tables=' . $this->toCommaList($tables));
        return $this;
    }

    /**
     * Only export the structure and not the raw data of the database.
     * `wp db export`
     *
     * @return $this
     */
    public function structureOnly()
    {
        $this->argForNextCommand('--no-data=true');
        return $this;
    }

    /**
     * Username to pass to mysql.
     * `wp db` option.
     *
     * @param  string  $dbUser
     * @return $this
     */
    public function dbUser($dbUser)
    {
        $this->argForNextCommand('--dbuser=' . $dbUser);
        return $this;
    }

    /**
     * User password to pass to mysql.
     * `wp db` option.
     *
     * @param  string  $dbPass
     * @return $this
     */
    public function dbPass($dbPass)
    {
        $this->argForNextCommand('--dbpass=' . $dbPass);
        return $this;
    }

    /**
     * Use a specific database.
     * `wp db` option.
     *
     * @param  string  $database
     * @return $this
     */
    public function database($database)
    {
        $this->argForNextCommand('--database=' . $database);
        return $this;
    }

    /**
     * Run the entire search/replace operation and show report, but don't save
     * changes to the database.
     * `wp search-replace` option.
     *
     * @return $this
     */
    public function dryRun()
    {
        $this->argForNextCommand('--dry-run');
        return $this;
    }

    /**
     * Search/replace through all the tables registered to $wpdb in a multisite
     * install.
     * `wp search-replace` option.
     *
     * @return $this
     */
    public function network()
    {
        $this->argForNextCommand('--network');
        return $this;
    }

    /**
     * Do not perform the replacement on specific columns.
     * `wp search-replace` option.
     *
     * @param  string|array  $columns
     * @return $this
     */
    public function skipColumns($columns)
    {
        $this->argForNextCommand('--skip-columns=' . $this->toCommaList($columns));
        return $this;
    }

    /**
     * Executes `wp db export` and saves dump remotely
     *
     * @param  string  $path  Remote file location where export is saved
     * @return $this
     */
    public function dbExport($path)
    {
        if ($path) {
            $this->argForNextCommand($path);
        }
        return $this->wp("db export --quick --single-transaction");
    }

    /**
     * Executes `wp db export` and saves dump locally
     *
     * @param  string  $path  Local file location where export is saved
     * @return $this
     */
    public function dbExportLocally($path)
    {
        if ($path) {
            $this->argForNextCommand("- >| $path");
        } else {
            $this->argForNextCommand("-");
        }
        return $this->dbExport('');
    }

    /**
     * Executes `wp db import` with dump on remote machine.
     *
     * @param  string  $path  Remote file location where dump is read.
     * @return $this
     */
    public function dbImport($path)
    {
        if ($path) {
            $this->argForNextCommand($path);
        }
        return $this->wp("db import");
    }

    /**
     * Executes `wp db import` with dump on local machine.
     *
     * @param  string  $path  Local file location where dump is read.
     * @return $this
     */
    public function dbImportLocally($path)
    {
        if (!$path) {
            $this->argForNextCommand('-');
            return $this->dbImport('');
        }
        $stack = new \Robo\Task\Base\ExecStack();
        $stack->exec("cat $path | {$this->executable} " . $this->injectArguments('db import') . ' -');
        return $this->exec($stack);
    }

    /**
     * Synchronizes the database between two site aliases.
     *
     * @param  string  $source
     * @param  string  $destination
     * @return $this
     */
    public function dbSync($source, $destination)
    {
        $originalAlias = $this->siteAlias;

        if ($originalAlias !== $source) {
            $this->siteAlias($source);
        }

        $this->dbExportLocally('wp-sync.sql');
        $this->siteAlias($destination);
        $this->dbImportLocally('wp-sync.sql');

        if ($originalAlias !== $destination) {
            $this->siteAlias($originalAlias);
        }
    }

    /**
     * Executes `wp search-replace`
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string[]  $tables
     * @return $this
     */
    public function searchReplace($search, $replace, $tables = [])
    {
        $this->argForNextCommand($search);
        $this->argForNextCommand($replace);
        foreach ($tables as $table) {
            $this->argForNextCommand($table);
        }
        return $this->wp('search-replace');
    }

    /**
     * Executes `wp cache`
     *
     * @param  string  $argument  Cache command to run
     * @return $this
     */
    public function cache($argument)
    {
        $this->argForNextCommand($argument);
        return $this->wp('cache');
    }

    /**
     * Executes `wp language core install`
     *
     * @param  string  $languages  Space separated list of languages to install
     * @return $this
     */
    public function languageInstallCore($languages)
    {
        $this->argsForNextCommand($languages);
        return $this->wp('language core install');
    }

    /**
     * Executes `wp language plugin install`
     *
     * @param  string  $plugin  Plugin name (--all for all)
     * @param  string  $languages  Space separated list of languages to install
     * @return $this
     */
    public function languageInstallPlugin($plugin, $languages)
    {
        $this->argForNextCommand($plugin);
        $this->argsForNextCommand($languages);
        return $this->wp('language plugin install');
    }

    /**
     * Executes `wp language core update`
     *
     * @return $this
     */
    public function languageUpdateCore()
    {
        return $this->wp('language core update');
    }

    /**
     * Executes `wp language plugin update`
     *
     * @param  string  $plugin  Plugin name (--all for all)
     * @return $this
     */
    public function languageUpdatePlugin($plugin)
    {
        $this->argForNextCommand($plugin);
        return $this->wp('language plugin update');
    }

    /**
     * Runs the given wp cli command.
     *
     * @param string $command
     *
     * @return $this
     */
    public function wp($command)
    {
        if (is_array($command)) {
            $command = implode(' ', array_filter($command));
        }

        return $this->exec($this->injectArguments($command));
    }

    /**
     * Add an argument used in the next invocation of drush.
     *
     * @param string $arg
     *
     * @return $this
     */
    protected function argForNextCommand($arg)
    {
        return $this->argsForNextCommand($arg);
    }

    /**
     * Add multiple arguments used in the next invocation of wp cli.
     *
     * @param array|string $args can also be multiple parameters
     *
     * @return $this
     */
    protected function argsForNextCommand($args)
    {
        if (!is_array($args)) {
            $args = func_get_args();
        }
        $this->argumentsForNextCommand .= ' ' . implode(' ', $args);

        return $this;
    }

    /**
     * Prepends site-alias and appends arguments to the command.
     *
     * @param  string  $command
     * @return string the modified command string
     */
    protected function injectArguments($command)
    {
        $cmd =
            $this->siteAlias . ' '
            . $command
            . $this->arguments
            . $this->argumentsForNextCommand;
        $this->argumentsForNextCommand = '';

        return $cmd;
    }

    /**
     * Convert a list-like argument to a comma separated list
     *
     * @param  string|array  $list
     * @param  bool  $addAssignment
     * @return string
     */
    protected function toCommaList($list, $addAssignment = false)
    {
        if (is_array($list)) {
            $list = implode(',', $list);
        }
        if ($addAssignment && mb_strlen($list)) {
            $list = "=$list";
        }
        return $list;
    }
}
