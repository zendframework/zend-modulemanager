# Introduction to the Module System

Zend Framework 2.0 introduced a new and powerful approach to modules. This new
module system is designed with flexibility, simplicity, and re-usability in
mind. A module may contain just about anything: PHP code, including MVC
functionality; library code; view scripts; and/or public assets such as images,
CSS, and JavaScript. The possibilities are endless.

> ### Event-based system
>
> The module system in ZF2 has been designed to be a generic and powerful foundation from which
> developers and other projects can build their own module or plugin systems.
> For a better understanding of the event-driven concepts behind the ZF2 module system, it may be
> helpful to read the [EventManager documentation](https://docs.zendframework.com/zend-eventmanager/).

The module system is made up of the following:

- [The Module Manager](module-manager.md) - `Zend\ModuleManager\ModuleManager`
  takes an array of module names and fires a sequence of events for each one,
  allowing the behavior of the module system to be defined entirely by the
  listeners which are attached to the module manager.
- **ModuleManager Listeners** - Event listeners can be attached to the module
  manager's various events. These listeners can do everything from resolving and
  loading modules to performing complex initialization tasks and introspection
  into each returned module object.

> ### Modules are PHP namespaces
>
> The name of a module in a Zend Framework application is a
> [PHP namespace](http://php.net/namespaces), and must follow all of the same
> rules for naming.

The recommended structure for a zend-mvc oriented module is as follows:

```text
module_root/
    config/
        module.config.php
    public/
        images/
        css/
        js/
    src/
        Module.php
        <code files as per PSR-4>
    test/
        <test code files>
    view/
        <dir-named-after-module-namespace>/
            <dir-named-after-a-controller>/
                <.phtml files>
    phpunit.xml.dist
    composer.json
```

## Autoloading

Since version 3, zend-modulemanager does not provide own autoloading mechanisms
and instead relies on [Composer dependency manager](https://getcomposer.org/)
to provide autoloading.
