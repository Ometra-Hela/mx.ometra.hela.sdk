<?php

namespace Ometra\HelaSdk\Tests;

use Ometra\HelaSdk\HelaSdkServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HelaSdkServiceProvider::class,
        ];
    }
}
