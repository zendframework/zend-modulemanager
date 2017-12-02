<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\ModuleManager\Listener\TestAsset;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

class CustomPluginManager extends AbstractPluginManager
{
    protected $instanceOf = CustomPluginInterface::class;

    public function validate($plugin)
    {
        if (! $plugin instanceof $this->instanceOf) {
            throw new InvalidServiceException();
        }
    }

    public function validatePlugin($plugin)
    {
        $this->validate($plugin);
    }
}
