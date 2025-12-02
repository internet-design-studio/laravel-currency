<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Facades;

use Illuminate\Support\Facades\Facade;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Fakes\FakeCurrencyAdapter;

/**
 * @method static \SvkDigital\Currency\ValueObjects\CurrencyRate|\SvkDigital\Currency\ValueObjects\CurrencyRate[] getRate(mixed $base, mixed $quote, \DateTimeInterface $date)
 *
 * @see CurrencyServiceContract
 */
final class Currency extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyServiceContract::class;
    }

    /**
     * Replace the currency adapter with a fake instance for testing.
     */
    public static function fake(): FakeCurrencyAdapter
    {
        $fake = new FakeCurrencyAdapter;

        self::swapAdapter($fake);

        return $fake;
    }

    /**
     * Replace the currency adapter in the container.
     */
    protected static function swapAdapter(CurrencyAdapter $adapter): void
    {
        $app = self::getFacadeApplication();

        if ($app === null) {
            throw new \RuntimeException('Application instance not found.');
        }

        $app->instance(CurrencyAdapter::class, $adapter);

        // Also rebind the service to use the new adapter
        $app->singleton(CurrencyServiceContract::class, function ($app) use ($adapter) {
            $cacheFactory = $app->make(\Illuminate\Contracts\Cache\Factory::class);
            $config = $app->make(\Illuminate\Contracts\Config\Repository::class);
            $cacheRepository = static::resolveCacheRepository($cacheFactory, $config);

            return \SvkDigital\Currency\Services\CurrencyService::fromConfig(
                $adapter,
                $cacheRepository,
                $config->get('currency', [])
            );
        });
    }

    /**
     * Resolve the cache repository.
     */
    protected static function resolveCacheRepository(\Illuminate\Contracts\Cache\Factory $cacheFactory, \Illuminate\Contracts\Config\Repository $config): ?\Illuminate\Contracts\Cache\Repository
    {
        if (! $config->get('currency.cache.enabled', false)) {
            return null;
        }

        $store = $config->get('currency.cache.store');

        return $store ? $cacheFactory->store($store) : $cacheFactory->store();
    }
}
