<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener;

use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleEvent;

class ModuleResolverListenerTest extends AbstractListenerTestCase
{
    public function testModuleResolverListenerCanResolveModuleClasses()
    {
        $moduleResolver = new ModuleResolverListener;
        $e = new ModuleEvent;

        $e->setModuleName('ListenerTestModule');
        $this->assertInstanceOf('ListenerTestModule\Module', $moduleResolver($e));

        $e->setModuleName('DoesNotExist');
        $this->assertFalse($moduleResolver($e));
    }
}
