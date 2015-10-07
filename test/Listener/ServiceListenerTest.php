<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use ArrayObject;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\Exception;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ModuleManager\EventManagerIntrospectionTrait;

/**
 * @covers Zend\ModuleManager\Listener\ServiceListener
 */
class ServiceListenerTest extends TestCase
{
    use EventManagerIntrospectionTrait;

    /**
     * @var ConfigListener
     */
    protected $configListener;

    protected $defaultServiceConfig = [
        'abstract_factories' => [],
        'aliases'            => [],
        'delegators'         => [],
        'factories'          => [],
        'initializers'       => [],
        'invokables'         => [],
        'lazy_services'      => [],
        'services'           => [],
        'shared'             => [],
    ];

    /**
     * @var ModuleEvent
     */
    protected $event;

    /**
     * @var ServiceListener
     */
    protected $listener;

    /**
     * @var ServiceManager
     */
    protected $services;

    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->listener = new ServiceListener($this->services);
        $this->listener->setApplicationServiceManager(
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );

        $this->event          = new ModuleEvent();
        $this->configListener = new ConfigListener();
        $this->event->setConfigListener($this->configListener);
    }

    public function testPassingInvalidModuleDoesNothing()
    {
        $module = new stdClass();
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);

        $this->assertSame($this->services, $this->listener->getConfiguredServiceManager());
    }

    public function testInvalidReturnFromModuleDoesNothing()
    {
        $module = new TestAsset\ServiceInvalidReturnModule();
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);

        $this->assertSame($this->services, $this->listener->getConfiguredServiceManager());
    }

    public function getServiceConfig()
    {
        // @codingStandardsIgnoreStart
        return [
            'invokables' => [
                __CLASS__ => __CLASS__
            ],
            'factories' => [
                'foo' => function ($sm) { },
            ],
            'abstract_factories' => [
                new TestAsset\SampleAbstractFactory(),
            ],
            'shared' => [
                'foo' => false,
                'zendtestmodulemanagerlistenerservicelistenertest' => true,
            ],
            'aliases'  => [
                'bar' => 'foo',
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function assertServiceManagerConfiguration()
    {
        $this->listener->onLoadModulesPost($this->event);
        $services = $this->listener->getConfiguredServiceManager();
        $this->assertNotSame($this->services, $services);
        $this->assertInstanceOf(ServiceManager::class, $services);

        $this->assertTrue($services->has(__CLASS__));
        $this->assertTrue($services->has('foo'));
        $this->assertTrue($services->has('bar'));
        $this->assertFalse($services->has('resolved-by-abstract'));
        $this->assertTrue($services->has('resolved-by-abstract', true));
    }

    public function testModuleReturningArrayConfiguresServiceManager()
    {
        $config = $this->getServiceConfig();
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);
        $services = $this->listener->getConfiguredServiceManager();
        $this->assertServiceManagerConfiguration();
    }

    public function testModuleReturningTraversableConfiguresServiceManager()
    {
        $config = $this->getServiceConfig();
        $config = new ArrayObject($config);
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);
        $this->assertServiceManagerConfiguration();
    }

    public function testModuleServiceConfigOverridesGlobalConfig()
    {
        $defaultConfig = ['aliases' => ['foo' => 'bar'], 'services' => [
            'bar' => new stdClass(),
            'baz' => new stdClass(),
        ]];
        $this->listener = new ServiceListener($this->services, $defaultConfig);
        $this->listener->setApplicationServiceManager(
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );
        $config = ['aliases' => ['foo' => 'baz']];
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->event->setModuleName(__NAMESPACE__ . '\TestAsset\ServiceProvider');
        $this->listener->onLoadModule($this->event);
        $this->listener->onLoadModulesPost($this->event);

        $services = $this->listener->getConfiguredServiceManager();
        $this->assertNotSame($this->services, $services);
        $this->assertTrue($services->has('config'));
        $this->assertTrue($services->has('foo'));
        $this->assertNotSame($services->get('foo'), $services->get('bar'));
        $this->assertSame($services->get('foo'), $services->get('baz'));
    }

    public function testModuleReturningServiceConfigConfiguresServiceManager()
    {
        $config = $this->getServiceConfig();
        $config = new ServiceConfig($config);
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);
        $this->assertServiceManagerConfiguration();
    }

    public function testMergedConfigContainingServiceManagerKeyWillConfigureServiceManagerPostLoadModules()
    {
        $config = ['service_manager' => $this->getServiceConfig()];
        $configListener = new ConfigListener();
        $configListener->setMergedConfig($config);
        $this->event->setConfigListener($configListener);
        $this->assertServiceManagerConfiguration();
    }

    public function invalidServiceManagerTypes()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['FooBar']],
            'object'     => [(object) ['service_manager' => 'FooBar']],
        ];
    }

    /**
     * @dataProvider invalidServiceManagerTypes
     */
    public function testUsingNonStringServiceManagerWithAddServiceManagerRaisesException($serviceManager)
    {
        $this->setExpectedException(Exception\RuntimeException::class, 'expected string');
        $this->listener->addServiceManager(
            $serviceManager,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );
    }

    public function testAddingApplicationServiceManagerViaAddServiceManagerRaisesException()
    {
        $this->setExpectedException(Exception\RuntimeException::class, ServiceListener::IS_APP_MANAGER);
        $this->listener->addServiceManager(
            ServiceListener::IS_APP_MANAGER,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );
    }

    public function testCreatesPluginManagerBasedOnModuleImplementingSpecifiedProviderInterface()
    {
        $received = [];
        $services = $this->services->withConfig(['factories' => [
            'CustomPluginManager' => function ($services, $name, array $options = null) use (&$received) {
                $received = $options;
                return new TestAsset\CustomPluginManager($services, $options);
            }
        ]]);
        $listener = new ServiceListener($services);

        $listener->addServiceManager(
            'CustomPluginManager',
            'custom_plugins',
            TestAsset\CustomPluginProviderInterface::class,
            'getCustomPluginConfig'
        );

        $pluginConfig = $this->getServiceConfig();
        $module = new TestAsset\CustomPluginProviderModule($pluginConfig);
        $this->event->setModule($module);
        $listener->onLoadModule($this->event);
        $listener->onLoadModulesPost($this->event);
        $this->assertEquals($pluginConfig, $received);

        $configuredServices = $listener->getConfiguredServiceManager();
        $this->assertNotSame($services, $configuredServices);
        $this->assertTrue($configuredServices->has('CustomPluginManager'));
        $plugins = $configuredServices->get('CustomPluginManager');
        $this->assertInstanceOf(TestAsset\CustomPluginManager::class, $plugins);
    }

    public function testCreatesPluginManagerBasedOnModuleDuckTypingSpecifiedProviderInterface()
    {
        $received = [];
        $services = $this->services->withConfig(['factories' => [
            'CustomPluginManager' => function ($services, $name, array $options = null) use (&$received) {
                $received = $options;
                return new TestAsset\CustomPluginManager($services, $options);
            }
        ]]);
        $listener = new ServiceListener($services);

        $listener->addServiceManager(
            'CustomPluginManager',
            'custom_plugins',
            TestAsset\CustomPluginProviderInterface::class,
            'getCustomPluginConfig'
        );

        $pluginConfig = $this->getServiceConfig();
        $module = new TestAsset\CustomPluginDuckTypeProviderModule($pluginConfig);
        $this->event->setModule($module);
        $listener->onLoadModule($this->event);
        $listener->onLoadModulesPost($this->event);
        $this->assertEquals($pluginConfig, $received);

        $configuredServices = $listener->getConfiguredServiceManager();
        $this->assertNotSame($services, $configuredServices);
        $this->assertTrue($configuredServices->has('CustomPluginManager'));
        $plugins = $configuredServices->get('CustomPluginManager');
        $this->assertInstanceOf(TestAsset\CustomPluginManager::class, $plugins);
    }

    public function testAttachesListenersAtExpectedPriorities()
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $this->assertListenerAtPriority(
            [$this->listener, 'onLoadModule'],
            1,
            ModuleEvent::EVENT_LOAD_MODULE,
            $events,
            'onLoadModule not registered at expected priority'
        );
        $this->assertListenerAtPriority(
            [$this->listener, 'onLoadModulesPost'],
            1,
            ModuleEvent::EVENT_LOAD_MODULES_POST,
            $events,
            'onLoadModulesPost not registered at expected priority'
        );

        return [
            'listener' => $this->listener,
            'events'   => $events,
        ];
    }

    /**
     * @depends testAttachesListenersAtExpectedPriorities
     */
    public function testCanDetachListeners(array $dependencies)
    {
        $listener = $dependencies['listener'];
        $events   = $dependencies['events'];

        $listener->detach($events);

        $listeners = $this->getArrayOfListenersForEvent(ModuleEvent::EVENT_LOAD_MODULE, $events);
        $this->assertCount(0, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(ModuleEvent::EVENT_LOAD_MODULES_POST, $events);
        $this->assertCount(0, $listeners);
    }
}
