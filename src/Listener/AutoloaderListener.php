<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\ModuleManager\Listener;

use Zend\Loader\AutoloaderFactory;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\ModuleEvent;

/**
 * Autoloader listener
 */
class AutoloaderListener extends AbstractListener
{
    /**
     * @param  ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        if (! $module instanceof AutoloaderProviderInterface
            && ! method_exists($module, 'getAutoloaderConfig')
        ) {
            return;
        }
        $autoloaderConfig = $module->getAutoloaderConfig();
        AutoloaderFactory::factory($autoloaderConfig);
    }
}
