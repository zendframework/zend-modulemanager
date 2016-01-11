<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ModuleManager\Listener\TestAsset;

use Interop\Container\ContainerInterface;
use stdClass;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class SampleAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $name)
    {
        return true;
    }

    public function __invoke(ContainerInterface $container, $name, array $options = [])
    {
        return new stdClass;
    }
}
