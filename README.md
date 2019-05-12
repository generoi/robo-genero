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
      "Generoi\\Robo\\Composer\\ComposerScript::postCreateProject"
    ],
    "test": [
      "Generoi\\Robo\\Composer\\ComposerScript::test"
    ]
  }
```

#### `RoboFile.php`

Add a `RoboFile.php`, you can check Genero's Bedrock repository for an example.

```php
<?php

use Robo\Robo;
use Generoi\Robo\Task\loadTasks;
use Generoi\Robo\Command\loadCommands;

class RoboFile extends \Robo\Tasks
{
    use loadTasks;
    use loadCommands;

    /**
     * Pull uploads directory from remote to local.
     *
     * @param  string  $source  Source alias eg. `production`
     * @return \Robo\Result
     */
    public function filesPull(string $source, $options = ['exclude' => null, 'dry-run' => false])
    {
        return $this->rsyncPull("{$source}:%files", $options);
    }

    /**
     * Push uploads directory from local to remote.
     *
     * @param  string  $destination  Destination alias eg. `production`
     * @return \Robo\Result
     */
    public function filesPush(string $destination, $options = ['exclude' => null, 'dry-run' => true])
    {
        return $this->rsyncPush("{$destination}:%files", $options);
    }
}
```

#### `robo.yml`

Create a `robo.yml` file:

```yaml
machine_name: <example-project>
theme_path: 'web/app/themes/${machine_name}'
organization: generoi
env:
  '@docker':
    host: '${machine_name}.ddev.local'
    wpcli: 'ddev exec wp-cli'
    path: '/var/www/html'
    url: 'http://${machine_name}.ddev.local'
  '@dev':
    host: '${machine_name}.test'
    user: vagrant
    path: '/var/www/wordpress'
    url: 'http://${machine_name}.test'
  '@staging':
    host: staging.example.org
    user: deploy
    path: '/var/www/staging/${machine_name}/deploy/current'
    ssh: 'ssh -o ForwardAgent=yes'
    url: 'http://staging.example.org'
  '@production':
    host: production.example.org
    user: deploy
    path: '/home/www/${machine_name}/deploy/current'
    ssh: 'ssh -o ForwardAgent=yes -o "ProxyCommand ssh deploy@staging.example.org nc %h %p 2> /dev/null"'
    url: 'https://production.example.org'

placeholders:
  '%files': web/app/uploads/

command:
  build:
    production:
      options:
        npm-script: 'build:production'
    development:
      options:
        npm-script: 'build'
  setup:
    theme:
      options:
        theme-repository: 'git@github.com:generoi/sage.git'
  search:
    replace:
      options:
        dirs:
          - config
          - web/app/themes
          - web/app/mu-plugins
        exclude:
          - robo.yml
          - dist
  db:
    options:
      exclude_tables:
        - wp_gf_entry
        - wp_gf_entry_meta
        - wp_gf_entry_notes
        - wp_gf_incomplete_submissions
        - wp_rg_incomplete_submissions
        - wp_rg_lead
        - wp_rg_lead_detail
        - wp_rg_lead_detail_long
        - wp_rg_lead_meta
        - wp_rg_lead_notes
        - wp_stream
        - wp_stream_meta
    pull:
      options:
        target: '@dev'
    push:
      options:
        source: '@dev'
  files:
    options:
      options:
        chmod: 'Dug=rwx,Do=rx,Fug=rw,Fo=r'
      exclude:
        - 'gravity_forms/'
        - '*.webp'
        - '*-c-*.jpg'
        - '*-c-*.jpeg'
        - '*-c-*.png'

task:
  Remote:
    RsyncAlias:
      settings:
        progress: true
        humanReadable: true
```

## Usage

```sh
robo list
robo build:production
robo rsync production:~/.bashrc .
robo rsync:pull production:%files
robo files:pull production
robo test:sniff --autofix
robo setup
robo search:replace
```
