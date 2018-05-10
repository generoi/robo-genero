<?php

namespace Generoi\Robo\Command;

use Robo\Robo;
use Robo\Result;
use Generoi\Robo\Common\ThemeTrait;

trait loadCommands
{
    use RsyncCommand,
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
