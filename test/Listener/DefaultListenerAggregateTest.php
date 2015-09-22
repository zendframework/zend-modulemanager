<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use Zend\ModuleManager\Listener\ListenerOptions;
use Zend\ModuleManager\Listener\DefaultListenerAggregate;
use Zend\ModuleManager\ModuleManager;

/**
 * @covers Zend\ModuleManager\Listener\AbstractListener
 * @covers Zend\ModuleManager\Listener\DefaultListenerAggregate
 */
class DefaultListenerAggregateTest extends AbstractListenerTestCase
{
    /**
     * @var DefaultListenerAggregate
     */
    protected $defaultListeners;

    public function setUp()
    {
        $this->defaultListeners = new DefaultListenerAggregate(
            new ListenerOptions([
                'module_paths'         => [
                    realpath(__DIR__ . '/TestAsset'),
                ],
            ])
        );
    }

    public function testDefaultListenerAggregateCanAttachItself()
    {
        $moduleManager = new ModuleManager(['ListenerTestModule']);
        $moduleManager->getEventManager()->attachAggregate(new DefaultListenerAggregate);

        $events = $moduleManager->getEventManager()->getEvents();
        $expectedEvents = [
            'loadModules' => [
                'Zend\Loader\ModuleAutoloader',
                'config-pre' => 'Zend\ModuleManager\Listener\ConfigListener',
                'config-post' => 'Zend\ModuleManager\Listener\ConfigListener',
                'Zend\ModuleManager\Listener\LocatorRegistrationListener',
                'Zend\ModuleManager\ModuleManager',
            ],
            'loadModule.resolve' => [
                'Zend\ModuleManager\Listener\ModuleResolverListener',
            ],
            'loadModule' => [
                'Zend\ModuleManager\Listener\AutoloaderListener',
                'Zend\ModuleManager\Listener\ModuleDependencyCheckerListener',
                'Zend\ModuleManager\Listener\InitTrigger',
                'Zend\ModuleManager\Listener\OnBootstrapListener',
                'Zend\ModuleManager\Listener\ConfigListener',
                'Zend\ModuleManager\Listener\LocatorRegistrationListener',
            ],
        ];
        foreach ($expectedEvents as $event => $expectedListeners) {
            $this->assertContains($event, $events);
            $listeners = $moduleManager->getEventManager()->getListeners($event);
            $this->assertSame(count($expectedListeners), count($listeners));
            foreach ($listeners as $listener) {
                $callback = $listener->getCallback();
                if (is_array($callback)) {
                    $callback = $callback[0];
                }
                $listenerClass = get_class($callback);
                $this->assertContains($listenerClass, $expectedListeners);
            }
        }
    }

    public function testDefaultListenerAggregateCanDetachItself()
    {
        $listenerAggregate = new DefaultListenerAggregate;
        $moduleManager     = new ModuleManager(['ListenerTestModule']);

        $this->assertEquals(1, count($moduleManager->getEventManager()->getEvents()));

        $listenerAggregate->attach($moduleManager->getEventManager());
        $this->assertEquals(4, count($moduleManager->getEventManager()->getEvents()));

        $listenerAggregate->detach($moduleManager->getEventManager());
        $this->assertEquals(1, count($moduleManager->getEventManager()->getEvents()));
    }

    public function testDefaultListenerAggregateSkipsAutoloadingListenersIfZendLoaderIsNotUsed()
    {
        $moduleManager = new ModuleManager(['ListenerTestModule']);
        $moduleManager->getEventManager()->attachAggregate(
            new DefaultListenerAggregate(new ListenerOptions([
                'use_zend_loader' => false,
            ]))
        );

        $events = $moduleManager->getEventManager()->getEvents();
        $expectedEvents = [
            'loadModules' => [
                'config-pre' => 'Zend\ModuleManager\Listener\ConfigListener',
                'config-post' => 'Zend\ModuleManager\Listener\ConfigListener',
                'Zend\ModuleManager\Listener\LocatorRegistrationListener',
                'Zend\ModuleManager\ModuleManager',
            ],
            'loadModule.resolve' => [
                'Zend\ModuleManager\Listener\ModuleResolverListener',
            ],
            'loadModule' => [
                'Zend\ModuleManager\Listener\ModuleDependencyCheckerListener',
                'Zend\ModuleManager\Listener\InitTrigger',
                'Zend\ModuleManager\Listener\OnBootstrapListener',
                'Zend\ModuleManager\Listener\ConfigListener',
                'Zend\ModuleManager\Listener\LocatorRegistrationListener',
            ],
        ];
        foreach ($expectedEvents as $event => $expectedListeners) {
            $this->assertContains($event, $events);
            $listeners = $moduleManager->getEventManager()->getListeners($event);
            $this->assertSame(count($expectedListeners), count($listeners));
            foreach ($listeners as $listener) {
                $callback = $listener->getCallback();
                if (is_array($callback)) {
                    $callback = $callback[0];
                }
                $listenerClass = get_class($callback);
                $this->assertContains($listenerClass, $expectedListeners);
            }
        }
    }
}
