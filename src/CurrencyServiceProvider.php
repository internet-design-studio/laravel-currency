<?php

declare(strict_types=1);

namespace SvkDigital\Currency;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Services\CurrencyService;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrencyAdapter::class, function ($app) {

            $providerKey = $this->provider();
            $providersConfig = config("currency.providers.$providerKey", []);

            /** @var string $providerKey */
            $camelized = Str::camel($providerKey);
            $normalizedProvider = Str::replace('_', '', $camelized);
            /** @var string $normalizedProvider */
            $adapterClassName = Str::ucfirst($normalizedProvider).'Adapter';
            /** @var class-string<CurrencyAdapter> $adapter */
            $adapter = '\\SvkDigital\\CurrencyEnum\\Adapters\\'.$adapterClassName;

            if (! method_exists($adapter, 'fromConfig')) {
                throw new \RuntimeException("Adapter class {$adapter} does not have fromConfig method.");
            }

            /** @var callable(HttpFactory, array<string, mixed>): CurrencyAdapter $fromConfig */
            $fromConfig = [$adapter, 'fromConfig'];

            return $fromConfig(
                $app->make(HttpFactory::class),
                $providersConfig
            );
        });

        $this->app->singleton(CurrencyServiceContract::class, function ($app) {
            $cacheRepository = $this->resolveCacheRepository($app->make(CacheFactory::class));

            return CurrencyService::fromConfig(
                $app->make(CurrencyAdapter::class),
                $cacheRepository,
                config('currency')
            );
        });
    }

    protected function provider(): string
    {
        /** @var string $provider */
        $provider = config('currency.provider', 'cbr');

        return $provider;
    }

    public function boot(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'currency');
        $this->offerPublishing();
    }

    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => $this->app->configPath('currency.php'),
            ]);
            $this->publishes([
                __DIR__.'/../stubs/CurrencyServiceProvider.stub' => app_path('Providers/CurrencyServiceProvider.php'),
            ], 'currency-provider');
        }
    }

    /**
     * Resolve the absolute config path.
     */
    protected function configPath(): string
    {
        return __DIR__.'/../config/currency.php';
    }

    protected function resolveCacheRepository(CacheFactory $cacheFactory): ?CacheRepository
    {
        if (! config('currency.cache.enabled')) {
            return null;
        }

        $store = config('currency.cache.store');

        return $store ? $cacheFactory->store($store) : $cacheFactory->store();
    }
}
