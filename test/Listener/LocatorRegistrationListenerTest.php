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
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ModuleManager\TestAsset\MockApplication;

require_once dirname(__DIR__) . '/TestAsset/ListenerTestModule/src/Foo/Bar.php';

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
        $module = null;
        $locator         = $this->serviceManager;
        $locator->setFactory('Foo\Bar', function (ServiceLocatorInterface $s) {
            $module   = $s->get('ListenerTestModule\Module');
            $manager  = $s->get('Zend\ModuleManager\ModuleManager');
            $instance = new \Foo\Bar($module, $manager);
            return $instance;
        });

        $locatorRegistrationListener = new LocatorRegistrationListener;
        $this->moduleManager->getEventManager()->attachAggregate($locatorRegistrationListener);
        $this->moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULE, function (ModuleEvent $e) use (&$module) {
            $module = $e->getModule();
        }, -1000);
        $this->moduleManager->loadModules();

        $this->application->bootstrap();
        $sharedInstance1 = $locator->get('ListenerTestModule\Module');
        $sharedInstance2 = $locator->get('Zend\ModuleManager\ModuleManager');

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

        $this->assertInstanceOf('Zend\ModuleManager\ModuleManager', $sharedInstance2);
        $this->assertSame($this->moduleManager, $locator->get('Foo\Bar')->moduleManager);
    }

    public function testNoDuplicateServicesAreDefinedForModuleManager()
    {
        $locatorRegistrationListener = new LocatorRegistrationListener;
        $this->moduleManager->getEventManager()->attachAggregate($locatorRegistrationListener);

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
