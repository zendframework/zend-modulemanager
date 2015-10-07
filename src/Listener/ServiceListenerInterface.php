<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ModuleManager\Listener;

use Zend\EventManager\ListenerAggregateInterface;

interface ServiceListenerInterface extends ListenerAggregateInterface
{
    /**
     * Provide metadata describing how to aggregate service/plugin manager configuration.
     *
     * - $serviceManager is the service name for the service/plugin manager.
     * - $key is the configuration key containing configuration for it.
     * - $moduleInterface is the interface indicating a configuration provider for it.
     * - $method is used for duck-typing configuration providers.
     *
     * @param  string $serviceManager  Service name for service/plugin manager
     * @param  string $key             Configuration key
     * @param  string $moduleInterface FQCN as string
     * @param  string $method          Method name
     */
    public function addServiceManager($serviceManager, $key, $moduleInterface, $method);

    /**
     * Provide metadata describing how to aggregate configuration for the application service manager.
     *
     * Sets the same metadata as used by addServiceManager(), using the key
     * IS_APP_MANAGER for the service_manager value.
     *
     * @param  string $key             Configuration key
     * @param  string $moduleInterface FQCN as string
     * @param  string $method          Method name
     */
    public function setApplicationServiceManager($key, $moduleInterface, $method);

    /**
     * Retrieve the application service manager instance post-configuration.
     *
     * @return ServiceManager
     */
    public function getConfiguredServiceManager();

    /**
     * @param  array $configuration
     * @return ServiceListenerInterface
     */
    public function setDefaultServiceConfig(array $configuration);
}
