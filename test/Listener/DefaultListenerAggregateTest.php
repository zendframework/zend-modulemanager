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
use ZendTest\ModuleManager\EventManagerIntrospectionTrait;

/**
 * @covers Zend\ModuleManager\Listener\AbstractListener
 * @covers Zend\ModuleManager\Listener\DefaultListenerAggregate
 */
class DefaultListenerAggregateTest extends AbstractListenerTestCase
{
    use EventManagerIntrospectionTrait;

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
        (new DefaultListenerAggregate)->attach($moduleManager->getEventManager());

        $events = $this->getEventsFromEventManager($moduleManager->getEventManager());
        $expectedEvents = [
            'loadModules' => [
                'config-pre' => 'Zend\ModuleManager\Listener\ConfigListener',
                'config-post' => 'Zend\ModuleManager\Listener\ConfigListener',
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
            ],
        ];
        foreach ($expectedEvents as $event => $expectedListeners) {
            $this->assertContains($event, $events);
            $count     = 0;
            foreach ($this->getListenersForEvent($event, $moduleManager->getEventManager()) as $listener) {
                if (is_array($listener)) {
                    $listener = $listener[0];
                }
                $listenerClass = get_class($listener);
                $this->assertContains($listenerClass, $expectedListeners);
                $count += 1;
            }

            $this->assertSame(count($expectedListeners), $count);
        }
    }

    public function testDefaultListenerAggregateCanDetachItself()
    {
        $listenerAggregate = new DefaultListenerAggregate;
        $moduleManager     = new ModuleManager(['ListenerTestModule']);
        $events            = $moduleManager->getEventManager();

        $this->assertEquals(1, count($this->getEventsFromEventManager($events)));

        $listenerAggregate->attach($events);
        $this->assertEquals(4, count($this->getEventsFromEventManager($events)));

        $listenerAggregate->detach($events);
        $this->assertEquals(1, count($this->getEventsFromEventManager($events)));
    }

    public function testDefaultListenerAggregateAddsAutoloadingListenersIfZendLoaderIsEnabled()
    {
        $moduleManager = new ModuleManager(['ListenerTestModule']);
        $defaultListeners = new DefaultListenerAggregate(new ListenerOptions([
            'use_zend_loader' => true,
        ]));
        $defaultListeners->attach($moduleManager->getEventManager());

        $events = $this->getEventsFromEventManager($moduleManager->getEventManager());
        $expectedEvents = [
            'loadModules' => [
                'Zend\Loader\ModuleAutoloader',
                'config-pre' => 'Zend\ModuleManager\Listener\ConfigListener',
                'config-post' => 'Zend\ModuleManager\Listener\ConfigListener',
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
            ],
        ];
        foreach ($expectedEvents as $event => $expectedListeners) {
            $this->assertContains($event, $events);
            $count     = 0;
            foreach ($this->getListenersForEvent($event, $moduleManager->getEventManager()) as $listener) {
                if (is_array($listener)) {
                    $listener = $listener[0];
                }
                $listenerClass = get_class($listener);
                $this->assertContains($listenerClass, $expectedListeners);
                $count += 1;
            }

            $this->assertSame(count($expectedListeners), $count);
        }
    }
}
