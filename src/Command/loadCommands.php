<?php

namespace Generoi\Robo\Command;

use Generoi\Robo\Common\ThemeTrait;

trait loadCommands
{
    use BuildCommand,
        ConfigCommand,
        DeployCommand,
        InstallCommand,
        RsyncCommand,
        SearchReplaceCommand,
        SetupCommand,
        TestCommand,
        ThemeTrait,
        WpCommand {
            ThemeTrait::getThemePath insteadof InstallCommand, TestCommand, BuildCommand, WpCommand;
        }
}
