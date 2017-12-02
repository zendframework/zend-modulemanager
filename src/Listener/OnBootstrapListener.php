<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\ModuleManager\Listener;

use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

/**
 * Autoloader listener
 */
class OnBootstrapListener extends AbstractListener
{
    /**
     * @param  ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        if (! $module instanceof BootstrapListenerInterface
            && ! method_exists($module, 'onBootstrap')
        ) {
            return;
        }

        $moduleManager = $e->getTarget();
        $events        = $moduleManager->getEventManager();
        $sharedEvents  = $events->getSharedManager();
        $sharedEvents->attach('Zend\Mvc\Application', ModuleManager::EVENT_BOOTSTRAP, [$module, 'onBootstrap']);
    }
}
