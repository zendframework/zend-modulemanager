<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\ModuleManager\Listener;

use Zend\ModuleManager\Listener\InitTrigger;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleEvent;

/**
 * @covers Zend\ModuleManager\Listener\AbstractListener
 * @covers Zend\ModuleManager\Listener\InitTrigger
 */
class InitTriggerTest extends AbstractListenerTestCase
{
    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    public function setUp()
    {
        $this->moduleManager = new ModuleManager([]);
        $this->moduleManager->getEventManager()->attach(
            ModuleEvent::EVENT_LOAD_MODULE_RESOLVE,
            new ModuleResolverListener,
            1000
        );
        $this->moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULE, new InitTrigger, 2000);
    }

    public function testInitMethodCalledByInitTriggerListener()
    {
        $moduleManager = $this->moduleManager;
        $moduleManager->setModules(['ListenerTestModule']);
        $moduleManager->loadModules();
        $modules = $moduleManager->getLoadedModules();
        $this->assertTrue($modules['ListenerTestModule']->initCalled);
    }
}
