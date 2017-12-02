<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Foo;

use ListenerTestModule\Module;
use Zend\ModuleManager\ModuleManager;

class Bar
{
    public $module;
    public $moduleManager;

    public function __construct(Module $module, ModuleManager $moduleManager)
    {
        $this->module        = $module;
        $this->moduleManager = $moduleManager;
    }
}
