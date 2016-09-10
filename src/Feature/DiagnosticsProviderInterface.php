<?php

namespace Zend\ModuleManager\Feature;

/**
 * Install Zend Framework's diagnostics package:
 * composer require zendframework/zenddiagnostics
 */
interface DiagnosticsProviderInterface
{
    /**
     * Expected to return an array of diagnostics. Key should be the diagnostic name, and value should be
     * boolean or an instance of \ZendDiagnostics\Result\ResultInterface.
     *
     * @return array
     */
    public function getDiagnostics();
}
