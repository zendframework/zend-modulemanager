<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ModuleManager\Listener;

use Traversable;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\ServiceManager\ConfigInterface as ServiceConfigInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

class ServiceListener implements ServiceListenerInterface
{
    const IS_APP_MANAGER = '__APPLICATION_SERVICE_MANAGER__';

    /**
     * Configuration aggregated for the application service manager.
     *
     * @var array
     */
    protected $appServiceConfig = [];

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = [];

    /**
     * Default service manager used to fulfill other SMs that need to be lazy loaded
     *
     * @var ServiceManager
     */
    protected $defaultServiceManager;

    /**
     * Default service configuration for the application service manager.
     *
     * @var array
     */
    protected $defaultServiceConfig = [];

    /**
     * @var array
     */
    protected $serviceManagers = [];

    /**
     * @param ServiceManager $serviceManager
     * @param null|array $configuration
     */
    public function __construct(ServiceManager $serviceManager, array $configuration = [])
    {
        $this->defaultServiceManager = $serviceManager;
        $this->setDefaultServiceConfig($configuration);
    }

    /**
     * @param  array $configuration
     * @return ServiceListener
     */
    public function setDefaultServiceConfig(array $configuration)
    {
        $this->defaultServiceConfig = $configuration;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addServiceManager($serviceManager, $key, $moduleInterface, $method)
    {
        if (! is_string($serviceManager)) {
            throw new Exception\RuntimeException(sprintf(
                'Invalid service or plugin manager provided, expected string name, %s provided',
                (is_object($serviceManager) ? get_class($serviceManager) : gettype($serviceManager))
            ));
        }

        if ($serviceManager === self::IS_APP_MANAGER) {
            throw new Exception\RuntimeException(sprintf(
                'Usage of the service key "%s" is forbidden; please use %s::setApplicationServiceManager',
                self::IS_APP_MANAGER,
                __CLASS__
            ));
        }

        $this->serviceManagers[$serviceManager] = [
            'service_manager'        => $serviceManager,
            'config_key'             => $key,
            'module_class_interface' => $moduleInterface,
            'module_class_method'    => $method,
            'configuration'          => [],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setApplicationServiceManager($key, $moduleInterface, $method)
    {
        $this->serviceManagers[self::IS_APP_MANAGER] = [
            'service_manager'        => self::IS_APP_MANAGER,
            'config_key'             => $key,
            'module_class_interface' => $moduleInterface,
            'module_class_method'    => $method,
            'configuration'          => [ $this->defaultServiceConfig ],
        ];
    }

    /**
     * Retrieve configuration aggregated for the application service manager.
     *
     * @param array
     */
    public function getServiceManagerConfig()
    {
        return $this->appServiceConfig;
    }

    /**
     * @param  EventManagerInterface $events
     * @param  int $priority
     * @return ServiceListener
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE, [$this, 'onLoadModule']);
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'onLoadModulesPost']);
        return $this;
    }

    /**
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $key => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$key]);
            }
        }
    }

    /**
     * Retrieve service manager configuration from module, and
     * configure the service manager.
     *
     * If the module does not implement a specific interface and does not
     * implement a specific method, does nothing. Also, if the return value
     * of that method is not a ServiceConfig object, or not an array or
     * Traversable that can seed one, does nothing.
     *
     * The interface and method name can be set by adding a new service manager
     * via the addServiceManager() method.
     *
     * @param  ModuleEvent $e
     * @return void
     */
    public function onLoadModule(ModuleEvent $e)
    {
        $module = $e->getModule();

        foreach ($this->serviceManagers as $key => $sm) {
            if (! $module instanceof $sm['module_class_interface']
                && ! method_exists($module, $sm['module_class_method'])
            ) {
                continue;
            }

            $config = $module->{$sm['module_class_method']}();

            if ($config instanceof ServiceConfigInterface) {
                $config = $config->toArray();
            }

            if ($config instanceof Traversable) {
                $config = ArrayUtils::iteratorToArray($config);
            }

            if (! is_array($config)) {
                // If we don't have an array by this point, nothing left to do.
                continue;
            }

            // We're keeping track of which modules provided which configuration to which service managers.
            // The actual merging takes place later. Doing it this way will enable us to provide more powerful
            // debugging tools for showing which modules overrode what.
            $fullname = $e->getModuleName() . '::' . $sm['module_class_method'] . '()';
            $this->serviceManagers[$key]['configuration'][$fullname] = $config;
        }
    }

    /**
     * Use merged configuration to configure service manager
     *
     * If the merged configuration has a non-empty, array 'service_manager'
     * key, it will be passed to a ServiceManager Config object, and
     * used to configure the service manager.
     *
     * @param  ModuleEvent $e
     * @throws Exception\RuntimeException
     * @return void
     */
    public function onLoadModulesPost(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config         = $configListener->getMergedConfig(false);

        $appServiceConfig = [];
        $pluginManagers   = [];

        foreach ($this->serviceManagers as $key => $sm) {
            if (isset($config[$sm['config_key']])
                && is_array($config[$sm['config_key']])
                && !empty($config[$sm['config_key']])
            ) {
                $this->serviceManagers[$key]['configuration']['merged_config'] = $config[$sm['config_key']];
            }

            // Merge all of the things!
            $smConfig = [];
            foreach ($this->serviceManagers[$key]['configuration'] as $name => $configs) {
                if (isset($configs['configuration_classes'])) {
                    foreach ($configs['configuration_classes'] as $class) {
                        $configs = ArrayUtils::merge($configs, $this->serviceConfigToArray($class));
                    }
                }
                $smConfig = ArrayUtils::merge($smConfig, $configs);
            }

            // If this is for the application service manager, we're done.
            if ($key === self::IS_APP_MANAGER) {
                $appServiceConfig = $smConfig;
                continue;
            }

            // Create the plugin manager instance.
            //
            // Use the build method, so that we can pass the configuration, but
            // also so we can prevent caching it in the SM instance itself.
            $instance = $this->defaultServiceManager->build($sm['service_manager'], $smConfig);

            if (! $instance instanceof ServiceManager) {
                throw new Exception\RuntimeException(sprintf(
                    'Instance returned for %s is not a valid service or plugin manager; received instance of %s',
                    $sm['service_manager'],
                    (is_object($instance) ? get_class($instance) : gettype($instance))
                ));
            }

            // Map the configuration key (and class name, if it differs) to the instance.
            $pluginManagers[$key] = $instance;
            if ($key !== get_class($instance)) {
                $pluginManagers[get_class($instance)] = $instance;
            }
        }

        // Register plugin managers as services in the application configuration.
        if (! isset($appServiceConfig['services'])) {
            $appServiceConfig['services'] = [];
        }
        $appServiceConfig['services'] = array_merge($appServiceConfig['services'], $pluginManagers);

        // Set the application service manager configuration
        $this->appServiceConfig = (new ServiceConfig($appServiceConfig))->toArray();
    }

    /**
     * Merge a service configuration container
     *
     * Extracts the various service configuration arrays, and then merges with
     * the internal service configuration.
     *
     * @param  ServiceConfig|string $config Instance of ServiceConfig or class name
     * @throws Exception\RuntimeException
     * @return array
     */
    protected function serviceConfigToArray($config)
    {
        if (is_string($config) && class_exists($config)) {
            $class  = $config;
            $config = new $class;
        }

        if (! $config instanceof ServiceConfig) {
            throw new Exception\RuntimeException(sprintf(
                'Invalid service manager configuration class provided; received "%s", expected an instance of Zend\ServiceManager\Config',
                (is_object($config) ? get_class($config) : (is_scalar($config) ? $config : gettype($config)))
            ));
        }

        return $config->toArray();
    }
}
