# robo-genero

Genero tasks for Robo Task Runner

### Installation

Require `robo` and this package to your project.

    composer require consolidation/robo generoi/robo-genero

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

Add a `RoboFile.php`, you can check Genero's Bedrock repository for an example.

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
