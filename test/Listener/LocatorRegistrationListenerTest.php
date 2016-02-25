<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\ModuleManager\Listener\LocatorRegistrationListener;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleEvent;
use Zend\Mvc\Application;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ModuleManager\TestAsset\MockApplication;

/**
 * @covers Zend\ModuleManager\Listener\AbstractListener
 * @covers Zend\ModuleManager\Listener\LocatorRegistrationListener
 */
class LocatorRegistrationListenerTest extends AbstractListenerTestCase
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var SharedEventManager
     */
    protected $sharedEvents;

    public function setUp()
    {
        if (! class_exists(Application::class)) {
            $this->markTestSkipped(
                'Skipping tests that rely on zend-mvc until that component is '
                . 'updated to be forwards-compatible with zend-eventmanager and '
                . 'zend-servicemanager v3 releases'
            );
        }

        $this->sharedEvents = new SharedEventManager();

        $this->moduleManager = new ModuleManager(['ListenerTestModule']);
        $this->moduleManager->getEventManager()->setSharedManager($this->sharedEvents);
        $this->moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, new ModuleResolverListener, 1000);

        $this->application = new MockApplication;
        $events            = new EventManager(['Zend\Mvc\Application', 'ZendTest\Module\TestAsset\MockApplication', 'application']);
        $events->setSharedManager($this->sharedEvents);
        $this->application->setEventManager($events);

        $this->serviceManager = new ServiceManager();
        $this->serviceManager->setService('ModuleManager', $this->moduleManager);
        $this->application->setServiceManager($this->serviceManager);
    }

    public function testModuleClassIsRegisteredWithDiAndInjectedWithSharedInstances()
    {
        $module  = null;
        $locator = $this->serviceManager;
        $locator->setFactory('Foo\Bar', function ($s) {
            $module   = $s->get('ListenerTestModule\Module');
            $manager  = $s->get('Zend\ModuleManager\ModuleManager');
            $instance = new \Foo\Bar($module, $manager);
            return $instance;
        });

        $locatorRegistrationListener = new LocatorRegistrationListener;
        $events = $this->moduleManager->getEventManager();
        $locatorRegistrationListener->attach($events);
        $events->attach(ModuleEvent::EVENT_LOAD_MODULE, function (ModuleEvent $e) use (&$module) {
            $module = $e->getModule();
        }, -1000);
        $this->moduleManager->loadModules();

        $this->application->bootstrap();
        $sharedInstance1 = $locator->get('ListenerTestModule\Module');
        $sharedInstance2 = $locator->get(ModuleManager::class);

        $this->assertInstanceOf('ListenerTestModule\Module', $sharedInstance1);
        $foo     = false;
        $message = '';
        try {
            $foo = $locator->get('Foo\Bar');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            while ($e = $e->getPrevious()) {
                $message .= "\n" . $e->getMessage();
            }
        }
        if (!$foo) {
            $this->fail($message);
        }
        $this->assertSame($module, $foo->module);

        $this->assertInstanceOf(ModuleManager::class, $sharedInstance2);
        $this->assertSame($this->moduleManager, $locator->get('Foo\Bar')->moduleManager);
    }

    public function testNoDuplicateServicesAreDefinedForModuleManager()
    {
        $locatorRegistrationListener = new LocatorRegistrationListener;
        $events = $this->moduleManager->getEventManager();
        $locatorRegistrationListener->attach($events);

        $this->moduleManager->loadModules();
        $this->application->bootstrap();
        $registeredServices = $this->application->getServiceManager()->getRegisteredServices();

        $aliases = $registeredServices['aliases'];
        $instances = $registeredServices['instances'];

        $this->assertContains('zendmodulemanagermodulemanager', $aliases);
        $this->assertNotContains('modulemanager', $aliases);

        $this->assertContains('modulemanager', $instances);
        $this->assertNotContains('zendmodulemanagermodulemanager', $instances);
    }
}
