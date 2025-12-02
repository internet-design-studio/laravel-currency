<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SvkDigital\Currency\CurrencyServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CurrencyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('currency.cache.enabled', false);
        $app['config']->set('currency.provider', 'cbr');
    }
}
