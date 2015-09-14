<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use ListenerTestModule;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Loader\AutoloaderFactory;
use Zend\Loader\ModuleAutoloader;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleEvent;

class ModuleResolverListenerTest extends TestCase
{
    public function setUp()
    {
        $this->loaders = spl_autoload_functions();
        if (!is_array($this->loaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->loaders = [];
        }

        // Store original include_path
        $this->includePath = get_include_path();

        $autoloader = new ModuleAutoloader([
            dirname(__DIR__) . '/TestAsset',
        ]);
        $autoloader->register();
    }

    public function tearDown()
    {
        // Restore original autoloaders
        AutoloaderFactory::unregisterAutoloaders();
        $loaders = spl_autoload_functions();
        if (is_array($loaders)) {
            foreach ($loaders as $loader) {
                spl_autoload_unregister($loader);
            }
        }

        foreach ($this->loaders as $loader) {
            spl_autoload_register($loader);
        }

        // Restore original include_path
        set_include_path($this->includePath);
    }

    /**
     * @dataProvider validModuleNameProvider
     */
    public function testModuleResolverListenerCanResolveModuleClasses($moduleName, $expectedInstanceOf)
    {
        $moduleResolver = new ModuleResolverListener;
        $e = new ModuleEvent;

        $e->setModuleName($moduleName);
        $this->assertInstanceOf($expectedInstanceOf, $moduleResolver($e));
    }

    public function validModuleNameProvider()
    {
        return [
            // Description => [module name, expectedInstanceOf]
            'Append Module' => ['ListenerTestModule', ListenerTestModule\Module::class],
        ];
    }

    public function testModuleResolverListenerReturnFalseIfCannotResolveModuleClasses()
    {
        $moduleResolver = new ModuleResolverListener;
        $e = new ModuleEvent;

        $e->setModuleName('DoesNotExist');
        $this->assertFalse($moduleResolver($e));
    }
}
