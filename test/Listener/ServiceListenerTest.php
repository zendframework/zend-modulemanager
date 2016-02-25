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
use ReflectionProperty;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\ModuleManager\Exception;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\ServiceManager\ServiceManager;

/**
 * @covers Zend\ModuleManager\Listener\ServiceListener
 */
class ServiceListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

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
        $this->listener->addServiceManager(
            $this->services,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );

        $this->event          = new ModuleEvent();
        $this->configListener = new ConfigListener();
        $this->event->setConfigListener($this->configListener);
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

    public function getConfiguredServiceManager($listener = null)
    {
        $listener = $listener ?: $this->listener;
        $r = new ReflectionProperty($listener, 'defaultServiceManager');
        $r->setAccessible(true);
        return $r->getValue($listener);
    }

    public function assertServiceManagerConfiguration()
    {
        $this->listener->onLoadModulesPost($this->event);
        $services = $this->getConfiguredServiceManager();

        $this->assertInstanceOf(ServiceManager::class, $services);
        $this->assertSame($this->services, $services);

        $this->assertTrue($services->has(__CLASS__));
        $this->assertTrue($services->has('foo'));
        $this->assertTrue($services->has('bar'));
        $this->assertTrue($services->has('resolved-by-abstract'));
    }

    public function assertServicesFromConfigArePresent(array $config, ServiceManager $serviceManager)
    {
        foreach ($config as $type => $services) {
            switch ($type) {
                case 'invokables':
                    // fall through
                case 'factories':
                    // fall through
                case 'aliases':
                    foreach (array_keys($services) as $service) {
                        $this->assertTrue(
                            $serviceManager->has($service),
                            sprintf(
                                'Service manager is missing expected service %s',
                                $service
                            )
                        );
                    }
                    break;
                default:
                    // Cannot test other types
                    break;
            }
        }
    }

    public function testPassingInvalidModuleDoesNothing()
    {
        $module = new stdClass();
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);

        $this->assertSame($this->services, $this->getConfiguredServiceManager());
    }

    public function testInvalidReturnFromModuleDoesNothing()
    {
        $module = new TestAsset\ServiceInvalidReturnModule();
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);

        $this->assertSame($this->services, $this->getConfiguredServiceManager());
    }

    public function testModuleReturningArrayConfiguresServiceManager()
    {
        $config = $this->getServiceConfig();
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);
        $services = $this->getConfiguredServiceManager();
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
        $this->listener->addServiceManager(
            $this->services,
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

        $services = $this->getConfiguredServiceManager();
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
        $this->setExpectedException(Exception\RuntimeException::class, 'expected ServiceManager or string');
        $this->listener->addServiceManager(
            $serviceManager,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );
    }

    public function testCreatesPluginManagerBasedOnModuleImplementingSpecifiedProviderInterface()
    {
        $services = $this->services->setFactory('CustomPluginManager', TestAsset\CustomPluginManagerFactory::class);
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

        $configuredServices = $this->getConfiguredServiceManager($listener);
        $this->assertSame($services, $configuredServices);
        $this->assertTrue($configuredServices->has('CustomPluginManager'));
        $plugins = $configuredServices->get('CustomPluginManager');
        $this->assertInstanceOf(TestAsset\CustomPluginManager::class, $plugins);

        $this->assertServicesFromConfigArePresent($pluginConfig, $plugins);
    }

    public function testCreatesPluginManagerBasedOnModuleDuckTypingSpecifiedProviderInterface()
    {
        $services = $this->services->setFactory('CustomPluginManager', TestAsset\CustomPluginManagerFactory::class);
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

        $configuredServices = $this->getConfiguredServiceManager($listener);
        $this->assertSame($services, $configuredServices);
        $this->assertTrue($configuredServices->has('CustomPluginManager'));
        $plugins = $configuredServices->get('CustomPluginManager');
        $this->assertInstanceOf(TestAsset\CustomPluginManager::class, $plugins);

        $this->assertServicesFromConfigArePresent($pluginConfig, $plugins);
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
