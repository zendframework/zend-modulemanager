<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\ModuleManager\Listener;

use PHPUnit\Framework\TestCase as TestCase;
use Zend\Loader\ModuleAutoloader;
use ZendTest\ModuleManager\ResetAutoloadFunctionsTrait;

/**
 * Common test methods for all AbstractListener children.
 */
class AbstractListenerTestCase extends TestCase
{
    use ResetAutoloadFunctionsTrait;

    /**
     * @before
     */
    protected function registerTestAssetsOnModuleAutoloader()
    {
        $autoloader = new ModuleAutoloader([
            dirname(__DIR__) . '/TestAsset',
        ]);
        $autoloader->register();
    }
}
