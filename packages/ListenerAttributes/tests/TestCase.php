<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests;

use ImBrett\LaravelListenerAttributes\Providers\ListenerAttributesServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ListenerAttributesServiceProvider::class,
        ];
    }
}
