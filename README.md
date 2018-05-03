# robo-genero

Genero tasks for Robo Task Runner

### Installation

Require `robo` and this package to your project.

    composer require consolidation/robo generoi/robo-genero

#### Composer scripts

Add the following `scripts` section to your `composer.json`

```json
  "scripts": {
    "post-create-project-cmd": [
      "Generoi\\Robo\\Installer\\ComposerScript::postCreateProject"
    ],
    "test": [
      "Generoi\\Robo\\Installer\\ComposerScript::test"
    ]
  }
```

#### `RoboFile.php`

Add a `RoboFile.php`, you can check Genero's Bedrock repository for an example.

```php
<?php

use Robo\Robo;

class RoboFile extends \Robo\Tasks
{
    use Generoi\Robo\Task\loadTasks;
    use Generoi\Robo\Task\loadCommands {
        test as public;
        sniff as public testSniff;
        buildProduction as public;
        buildDevelopment as public;
        installProduction as public;
        installDevelopment as public;
        searchReplace as public;
        setup as public;
        setupTheme as public;
        setupRemote as public;
        setupYaml as public;
    }
}
```

#### `robo.yml`

Create a `robo.yml` file:

```yaml
machine_name: <example-project>
theme_path: 'web/app/themes/${machine_name}'
organization: generoi
command:
  build:
    production:
      options:
        npm-script: 'build:production'
    development:
      options:
        npm-script: build
  setup:
    theme:
      options:
        theme-repository: 'git@github.com:generoi/sage.git'
```
