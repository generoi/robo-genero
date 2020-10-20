<?php

namespace Generoi\Robo\Command;

use Generoi\Robo\Common\ThemeTrait;

trait loadCommands
{
    use ConfigCommand,
        DeployCommand,
        RsyncCommand,
        SetupCommand,
        SearchReplaceCommand,
        TestCommand,
        InstallCommand,
        BuildCommand,
        WpCommand,
        ThemeTrait {
            ThemeTrait::getThemePath insteadof InstallCommand, TestCommand, BuildCommand, WpCommand;
    }
}
