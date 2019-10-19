# Upgrading to 3.0

## Module autoloading

zend-modulemanager originates from before the Composer was created, where each
framework had to provide its own autoloading implementation.
Since then Composer became the de-facto standard in managing dependencies and
autoloading for the php projects.  
In light of that, zend-servicemanager removes ModuleLoader and autoload
providers support in version 3.0 in favor of
[Composer dependency manager](https://getcomposer.org/).

### Application local modules

Autoloading rules for application local modules should now be defined in
application's composer.json

Before:
```php
namespace Application;

class Module
{
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
}
```
and after:
```json
{
    "name": "zendframework/skeleton-application",
    "description": "Skeleton Application for Zend Framework zend-mvc applications",
    "type": "project",
    ...
    "autoload": {
        "psr-4": {
            "Application\\": "module/Application/src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "ApplicationTest\\": "module/Application/test/"
        }
    }
}
```

[zf-composer-autoloading](https://github.com/zfcampus/zf-composer-autoloading)
provides a handy tool to easily add and remove autoloading rules for local modules to
application's composer.json

After autoloading rules were updated, composer will need to update autoloader:

```console
$ composer dump-autoload
```

### Composer installed modules

For composer installed modules, autoloading rules will be automatically picked
by composer from the module's composer.json and no extra effort is needed:
```json
{
    "name": "acme/my-module",
    "description": "Module for use with zend-mvc applications.",
    "type": "library",
    "require": {
        "php": "^7.1"
    },
    "autoload": {
        "psr-4": {
            "Acme\\MyModule\\": "src/"
        }
    }
}
```
