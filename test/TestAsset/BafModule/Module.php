<?php
/**
 * @link      http://github.com/zendframework/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-modulemanager/blob/master/LICENSE.md New BSD License
 */

namespace BafModule;

use Zend\Config\Config;

class Module
{
    public function getConfig()
    {
        return new Config(include __DIR__ . '/configs/config.php');
    }
}
