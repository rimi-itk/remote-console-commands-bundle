# Remote console commands

Run [Symfony console
commands](https://symfony.com/doc/current/components/console.html) on
remote hosts. Commands are run using
[Deployer](https://deployer.org/).

## Installation

```sh
composer require itk-dev/remote-console-commands-bundle
```

Edit `app/AppKernel.php` and include the bundle:

```php
            $bundles[] = new ItkDev\RemoteConsoleCommandsBundle\ItkDevRemoteConsoleCommandsBundle();
```

Create a [`hosts.yaml`](Resources/hosts.yaml) file
(cf. [https://deployer.org/docs/hosts](https://deployer.org/docs/hosts))
in the project root, e.g:

```yaml
# The first host is the default host
stg.example.com:
    # The stage
    stage: stg
    # Project root on server
    release_path: /home/www/stg.example.com/htdocs/
    # Use custom "bin/console" command. Relative paths are relative to `release_path`
    paths:
        bin/console: ../scripts/console
    # Environment
    env:
        APP_ENV: prod

example.com:
    stage: prod
    release_path: /home/www/stg.example.com/htdocs/
    paths:
        bin/console: ../scripts/console
    env:
        APP_ENV: prod
```

## Usage

```sh
bin/console help remote
```
