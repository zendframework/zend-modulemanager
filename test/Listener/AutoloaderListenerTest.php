<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use Zend\ModuleManager\Listener\AutoloaderListener;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleEvent;

/**
 * @covers Zend\ModuleManager\Listener\AbstractListener
 * @covers Zend\ModuleManager\Listener\AutoloaderListener
 */
class AutoloaderListenerTest extends AbstractListenerTestCase
{
    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    public function setUp()
    {
        $this->moduleManager = new ModuleManager([]);
        $events = $this->moduleManager->getEventManager();
        $events->attach(ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, new ModuleResolverListener, 1000);
        $events->attach(ModuleEvent::EVENT_LOAD_MODULE, new AutoloaderListener, 2000);
    }

    public function testAutoloadersRegisteredByAutoloaderListener()
    {
        $moduleManager = $this->moduleManager;
        $moduleManager->setModules(['ListenerTestModule']);
        $moduleManager->loadModules();
        $modules = $moduleManager->getLoadedModules();
        $this->assertTrue($modules['ListenerTestModule']->getAutoloaderConfigCalled);
        $this->assertTrue(class_exists('Foo\Bar'));
    }
    // @codingStandardsIgnoreStart
    public function testAutoloadersRegisteredIfModuleDoesNotInheritAutoloaderProviderInterfaceButDefinesGetAutoloaderConfigMethod()
    {
        $moduleManager = $this->moduleManager;
        $moduleManager->setModules(['NotAutoloaderModule']);
        $moduleManager->loadModules();
        $modules = $moduleManager->getLoadedModules();
        $this->assertTrue($modules['NotAutoloaderModule']->getAutoloaderConfigCalled);
        $this->assertTrue(class_exists('Foo\Bar'));
    }
   // @codingStandardsIgnoreEnd
}
