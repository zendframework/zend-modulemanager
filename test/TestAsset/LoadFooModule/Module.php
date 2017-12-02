<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace LoadFooModule;

use Zend\ModuleManager\ModuleManager;

class Module
{
    public function init(ModuleManager $moduleManager)
    {
        $moduleManager->loadModule('LoadBarModule');
    }

    public function getConfig()
    {
        return [
            'bar' => 'foo',
            'foo' => 'foo',
        ];
    }
}
