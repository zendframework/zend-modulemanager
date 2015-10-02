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
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

/**
 * @covers Zend\ModuleManager\Listener\ServiceListener
 */
class ServiceListenerTest extends TestCase
{
    /**
     * @var ConfigListener
     */
    protected $configListener;

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

        $this->assertEquals([], $this->listener->getServiceManagerConfig());
    }

    public function testInvalidReturnFromModuleDoesNothing()
    {
        $module = new TestAsset\ServiceInvalidReturnModule();
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);

        $this->assertEquals([], $this->listener->getServiceManagerConfig());
    }

    public function getServiceConfig()
    {
        // @codingStandardsIgnoreStart
        return [
            'invokables' => [__CLASS__ => __CLASS__],
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
        $default = [
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
        $expected = ArrayUtils::merge($default, $this->getServiceConfig());
        $this->listener->onLoadModulesPost($this->event);
        $this->assertEquals($expected, $this->listener->getServiceManagerConfig());
    }

    public function testModuleReturningArrayConfiguresServiceManager()
    {
        $config = $this->getServiceConfig();
        $module = new TestAsset\ServiceProviderModule($config);
        $this->event->setModule($module);
        $this->listener->onLoadModule($this->event);
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
        $defaultConfig = ['aliases' => ['foo' => 'bar']];
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
        $expected = ArrayUtils::merge($defaultConfig, $config);
        $expected = (new ServiceConfig($expected))->toArray();
        $this->assertEquals(
            $expected,
            $this->listener->getServiceManagerConfig(),
            'Default configuration was not overridden'
        );
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
}
