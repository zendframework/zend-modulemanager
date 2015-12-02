# The Module Manager

The module manager, `Zend\ModuleManager\ModuleManager`, is a very simple class which is responsible
for iterating over an array of module names and triggering a sequence of events for each.
Instantiation of module classes, initialization tasks, and configuration are all performed by
attached event listeners.

## Module Manager Events

The Module Manager events are defined in `Zend\ModuleManager\ModuleEvent`.

### Events triggered by `Zend\ModuleManager\ModuleManager`

#### loadModules (ModuleEvent::EVENT\_LOAD\_MODULES)

This event is primarily used internally to help encapsulate the work of loading modules in event
listeners, and allow the loadModules.post event to be more user-friendly. Internal listeners will
attach to this event with a negative priority instead of loadModules.post so that users can safely
assume things like config merging have been done once loadModules.post is triggered, without having
to worry about priorities at all.

#### loadModule.resolve (ModuleEvent::EVENT\_LOAD\_MODULE\_RESOLVE)

Triggered for each module that is to be loaded. The listener(s) to this event are responsible for
taking a module name and resolving it to an instance of some class. The default module resolver
shipped with ZF2 simply looks for the class `{modulename}\Module`, instantiating and returning it if
it exists.

The name of the module may be retrieved by listeners using the `getModuleName()` method of the
`Event` object; a listener should then take that name and resolve it to an object instance
representing the given module. Multiple listeners can be attached to this event, and the module
manager will trigger them in order of their priority until one returns an object. This allows you to
attach additional listeners which have alternative methods of resolving modules from a given module
name.

#### loadModule (ModuleEvent::EVENT\_LOAD\_MODULE)

Once a module resolver listener has resolved the module name to an object, the module manager then
triggers this event, passing the newly created object to all listeners.

#### mergeConfig (ModuleEvent::EVENT\_MERGE\_CONFIG)

After all modules have been loaded, the `mergeConfig` event is triggered. By default,
`Zend\ModuleManager\Listener\ConfigLister` listens on this event at priority 1000, and merges all
configuration. You may attach additional listeners to this event in order to manipulate the merged
configuration. See [the tutorial on manipulating merged
configuration](tutorials.config.advanced.manipulating-merged-configuration) for more information.

#### loadModules.post (ModuleEvent::EVENT\_LOAD\_MODULES\_POST)

This event is triggered by the module manager to allow any listeners to perform work after every
module has finished loading. For example, the default configuration listener,
`Zend\ModuleManager\Listener\ConfigListener` (covered later), attaches to this event to merge
additional user-supplied configuration which is meant to override the default supplied
configurations of installed modules.

## Module Manager Listeners

By default, Zend Framework provides several useful module manager listeners.

### Provided Module Manager Listeners

#### Zend\\ModuleManager\\Listener\\DefaultListenerAggregate

To help simplify the most common use case of the module manager, ZF2 provides this default aggregate
listener. In most cases, this will be the only listener you will need to attach to use the module
manager, as it will take care of properly attaching the requisite listeners (those listed below) for
the module system to function properly.

#### Zend\\ModuleManager\\Listener\\AutoloaderListener

This listener checks each module to see if it has implemented
`Zend\ModuleManager\Feature\AutoloaderProviderInterface` or simply defined the
`getAutoloaderConfig()` method. If so, it calls the `getAutoloaderConfig()` method on the module
class and passes the returned array to `Zend\Loader\AutoloaderFactory`.

#### Zend\\ModuleManager\\Listener\\ModuleDependencyCheckerListener

This listener checks each module to verify if all the modules it depends on were loaded. When a
module class implements `Zend\ModuleManager\Feature\DependencyIndicatorInterface` or simply has a
defined `getModuleDependencies()` method, the listener will call `getModuleDependencies()`. Each of
the values returned by the method is checked against the loaded modules list: if one of the values
is not in that list, a `Zend\ModuleManager\Exception\MissingDependencyModuleException` is be thrown.

#### Zend\\ModuleManager\\Listener\\ConfigListener

If a module class has a `getConfig()` method, or implements
`Zend\ModuleManager\Feature\ConfigProviderInterface`, this listener will call it and merge the
returned array (or `Traversable` object) into the main application configuration.

#### Zend\\ModuleManager\\Listener\\InitTrigger

If a module class either implements `Zend\ModuleManager\Feature\InitProviderInterface`, or simply
defines an `init()` method, this listener will call `init()` and pass the current instance of
`Zend\ModuleManager\ModuleManager` as the sole parameter.

Like the `OnBootstrapListener`, the `init()` method is called for **every** module implementing this
feature, on **every** page request and should **only** be used for performing **lightweight** tasks
such as registering event listeners.

#### Zend\\ModuleManager\\Listener\\LocatorRegistrationListener

If a module class implements `Zend\ModuleManager\Feature\LocatorRegisteredInterface`, this listener
will inject the module class instance into the `ServiceManager` using the module class name as the
service name. This allows you to later retrieve the module class from the `ServiceManager`.

#### Zend\\ModuleManager\\Listener\\ModuleResolverListener

nThis is the default module resolver. It attaches to the "loadModule.resolve" event and simply
returns an instance of `{moduleName}\Module`.

#### Zend\\ModuleManager\\Listener\\OnBootstrapListener

If a module class implements `Zend\ModuleManager\Feature\BootstrapListenerInterface`, or simply
defines an `onBootstrap()` method, this listener will register the `onBootstrap()` method with the
`Zend\Mvc\Application` `bootstrap` event. This method will then be triggered during the `bootstrap`
event (and passed an `MvcEvent` instance).

Like the `InitTrigger`, the `onBootstrap()` method is called for **every** module implementing this
feature, on **every** page request, and should **only** be used for performing **lightweight** tasks
such as registering event listeners.

#### Zend\\ModuleManager\\Listener\\ServiceListener

If a module class implements `Zend\ModuleManager\Feature\ServiceProviderInterface`, or simply
defines an `getServiceConfig()` method, this listener will call that method and aggregate the return
values for use in configuring the `ServiceManager`.

The `getServiceConfig()` method may return either an array of configuration compatible with
`Zend\ServiceManager\Config`, an instance of that class, or the string name of a class that extends
it. Values are merged and aggregated on completion, and then merged with any configuration from the
`ConfigListener` falling under the `service_manager` key. For more information, see the
`ServiceManager` documentation.

Unlike the other listeners, this listener is not managed by the `DefaultListenerAggregate`; instead,
it is created and instantiated within the `Zend\Mvc\Service\ModuleManagerFactory`, where it is
injected with the current `ServiceManager` instance before being registered with the `ModuleManager`
events.

Additionally, this listener manages a variety of plugin managers, including [view
helpers](zend.view.helpers), controllers
&lt;zend.mvc.controllers&gt;, and [controller plugins](zend.mvc.controller-plugins). In each case,
you may either specify configuration to define plugins, or provide configuration via a `Module`
class. Configuration follows the same format as for the `ServiceManager`. The following table
outlines the plugin managers that may be configured this way (including the `ServiceManager`), the
configuration key to use, the `ModuleManager` feature interface to optionally implement (all
interfaces specified live in the `Zend\ModuleManager\Feature` namespace) , and the module method to
optionally define to provide configuration.

<table>
<colgroup>
<col width="33%" />
<col width="17%" />
<col width="27%" />
<col width="21%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Plugin Manager</th>
<th align="left">Config Key</th>
<th align="left">Interface</th>
<th align="left">Module Method</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left"><code>Zend\Mvc\Controller\ControllerManager</code></td>
<td align="left"><code>controllers</code></td>
<td align="left"><code>ControllerProviderInterface</code></td>
<td align="left"><code>getControllerConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\Mvc\Controller\PluginManager</code></td>
<td align="left"><code>controller_plugins</code></td>
<td align="left"><code>ControllerPluginProviderInterface</code></td>
<td align="left"><code>getControllerPluginConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\Filter\FilterPluginManager</code></td>
<td align="left"><code>filters</code></td>
<td align="left"><code>FilterProviderInterface</code></td>
<td align="left"><code>getFilterConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\Form\FormElementManager</code></td>
<td align="left"><code>form_elements</code></td>
<td align="left"><code>FormElementProviderInterface</code></td>
<td align="left"><code>getFormElementConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\Stdlib\Hydrator\HydratorPluginManager</code></td>
<td align="left"><code>hydrators</code></td>
<td align="left"><code>HydratorProviderInterface</code></td>
<td align="left"><code>getHydratorConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\InputFilter\InputFilterPluginManager</code></td>
<td align="left"><code>input_filters</code></td>
<td align="left"><code>InputFilterProviderInterface</code></td>
<td align="left"><code>getInputFilterConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\Mvc\Router\RoutePluginManager</code></td>
<td align="left"><code>route_manager</code></td>
<td align="left"><code>RouteProviderInterface</code></td>
<td align="left"><code>getRouteConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\Serializer\AdapterPluginManager</code></td>
<td align="left"><code>serializers</code></td>
<td align="left"><code>SerializerProviderInterface</code></td>
<td align="left"><code>getSerializerConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\ServiceManager\ServiceManager</code></td>
<td align="left"><code>service_manager</code></td>
<td align="left"><code>ServiceProviderInterface</code></td>
<td align="left"><code>getServiceConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\Validator\ValidatorPluginManager</code></td>
<td align="left"><code>validators</code></td>
<td align="left"><code>ValidatorProviderInterface</code></td>
<td align="left"><code>getValidatorConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\View\HelperPluginManager</code></td>
<td align="left"><code>view_helpers</code></td>
<td align="left"><code>ViewHelperProviderInterface</code></td>
<td align="left"><code>getViewHelperConfig</code></td>
</tr>
<tr class="even">
<td align="left"><code>Zend\Log\ProcessorPluginManager</code></td>
<td align="left"><code>log_processors</code></td>
<td align="left"><code>LogProcessorProviderInterface</code></td>
<td align="left"><code>getLogProcessorConfig</code></td>
</tr>
<tr class="odd">
<td align="left"><code>Zend\Log\WriterPluginManager</code></td>
<td align="left"><code>log_writers</code></td>
<td align="left"><code>LogWriterProviderInterface</code></td>
<td align="left"><code>getLogWriterConfig</code></td>
</tr>
</tbody>
</table>

Configuration follows the examples in the ServiceManager configuration
section &lt;zend.service-manager.quick-start.config&gt;. As a brief recap, the following
configuration keys and values are allowed:

<table>
<colgroup>
<col width="29%" />
<col width="70%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Config Key</th>
<th align="left">Allowed values</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left"><code>services</code></td>
<td align="left">service name/instance pairs (these should likely be defined only in
<code>Module</code> classes)</td>
</tr>
<tr class="even">
<td align="left"><code>invokables</code></td>
<td align="left">service name/class name pairs of classes that may be invoked without constructor
arguments</td>
</tr>
<tr class="odd">
<td align="left"><code>factories</code></td>
<td align="left">service names pointing to factories. Factories may be any PHP callable, or a string
class name of a class implementing <code>Zend\ServiceManager\FactoryInterface</code>, or of a class
implementing the <code>__invoke</code> method (if a callable is used, it should be defined only in
<code>Module</code> classes)</td>
</tr>
<tr class="even">
<td align="left"><code>abstract_factories</code></td>
<td align="left">array of either concrete instances of
<code>Zend\ServiceManager\AbstractFactoryInterface</code>, or string class names of classes
implementing that interface (if an instance is used, it should be defined only in
<code>Module</code> classes)</td>
</tr>
<tr class="odd">
<td align="left"><code>initializers</code></td>
<td align="left">array of PHP callables or string class names of classes implementing
<code>Zend\ServiceManager\InitializerInterface</code> (if a callable is used, it should be defined
only in <code>Module</code> classes)</td>
</tr>
</tbody>
</table>

When working with plugin managers, you will be passed the plugin manager instance to factories,
abstract factories, and initializers. If you need access to the application services, you can use
the `getServiceLocator()` method, as in the following example:

```php
public function getViewHelperConfig()
{
    return array('factories' => array(
        'foo' => function ($helpers) {
            $services    = $helpers->getServiceLocator();
            $someService = $services->get('SomeService');
            $helper      = new Helper\Foo($someService);

            return $helper;
        },
    ));
}
```

This is a powerful technique, as it allows your various plugins to remain agnostic with regards to
where and how dependencies are injected, and thus allows you to use Inversion of Control principals
even with plugins.


