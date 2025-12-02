---
title: Custom Adapters
---

## Creating Custom Adapters

The package allows you to create custom adapters to integrate with any currency exchange rate provider. This is useful when you need to:

- Integrate with a provider that's not included in the package
- Add custom business logic to rate fetching
- Support crypto currency providers
- Use a custom internal API or database

## Implementation Guide

### Step 1: Implement the CurrencyAdapter Interface

Create a class that implements `SvkDigital\Currency\Contracts\CurrencyAdapter`:

```php
<?php

declare(strict_types=1);

namespace App\Currency\Adapters;

use DateTimeInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;

final class MyCustomAdapter implements CurrencyAdapter
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUri
    ) {
    }

    /**
     * Create adapter instance from configuration array.
     *
     * @param array<string, mixed> $config
     */
    public static function fromConfig(HttpFactory $http, array $config): self
    {
        return new self(
            $http,
            (string) ($config['api_key'] ?? ''),
            (string) ($config['base_uri'] ?? 'https://api.example.com')
        );
    }

    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        // Your implementation here
        // Convert base and quote to strings
        // Make HTTP request to your provider
        // Parse response and return CurrencyRate or array of CurrencyRate
    }
}
```

### Step 2: Register Your Adapter

There are two ways to register your custom adapter:

#### Option A: Configuration-Based Registration

Add your adapter configuration to `config/currency.php`:

```php
'provider' => 'my_custom',

'providers' => [
    'my_custom' => [
        'api_key' => env('MY_CUSTOM_API_KEY'),
        'base_uri' => env('MY_CUSTOM_BASE_URI', 'https://api.example.com'),
        // Add any other configuration your adapter needs
    ],
],
```

The service provider will automatically resolve your adapter class based on the provider name. It expects the class to be located at:

```
SvkDigital\Currency\Adapters\{ProviderName}Adapter
```

For example, if your provider name is `my_custom`, it will look for:
```
SvkDigital\Currency\Adapters\MyCustomAdapter
```

**Note**: This approach requires placing your adapter in the package's namespace, which is not ideal for application-specific adapters.

#### Option B: Override Provider Resolution (Recommended) {#option-b-override-provider-resolution-recommended}

Publish the service provider and override the adapter resolution logic:

```bash
php artisan vendor:publish --tag=currency-provider
```

This creates `app/Providers/CurrencyServiceProvider.php`. Modify it to register your custom adapter:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Currency\Adapters\MyCustomAdapter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use SvkDigital\Currency\CurrencyServiceProvider as BaseServiceProvider;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Services\CurrencyService;

final class CurrencyServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register your custom adapter
        $this->app->singleton(CurrencyAdapter::class, function ($app) {
            $config = config('currency.providers.my_custom', []);
            
            return MyCustomAdapter::fromConfig(
                $app->make(HttpFactory::class),
                $config
            );
        });

        // Register the currency service
        $this->app->singleton(CurrencyServiceContract::class, function ($app) {
            $cacheRepository = $this->resolveCacheRepository($app->make(CacheFactory::class));

            return CurrencyService::fromConfig(
                $app->make(CurrencyAdapter::class),
                $cacheRepository,
                config('currency')
            );
        });
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
```

Don't forget to register this provider in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CurrencyServiceProvider::class,
],
```

### Step 3: Dynamic Provider Selection {#dynamic-provider-selection}

You can also override the `provider()` method to dynamically select a provider based on runtime conditions:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use SvkDigital\Currency\CurrencyServiceProvider as BaseServiceProvider;

final class CurrencyServiceProvider extends BaseServiceProvider
{
    protected function provider(): string
    {
        // Example: Use different providers based on environment
        if (app()->environment('production')) {
            return 'currency_freaks';
        }

        // Example: Use different providers based on user preference
        if (auth()->check() && auth()->user()->prefersCbr()) {
            return 'cbr';
        }

        // Example: Use different providers based on request
        if (request()->has('use_cbr')) {
            return 'cbr';
        }

        // Fallback to config
        return config('currency.provider', 'cbr');
    }
}
```

This allows you to:
- Switch providers based on environment variables
- Use different providers for different users
- Select providers based on request parameters
- Implement provider fallback logic
- Use multiple providers simultaneously (by creating multiple service instances)

## Best Practices

### Error Handling

Always wrap HTTP requests in try-catch blocks and throw `CurrencyAdapterException`:

```php
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

try {
    $response = $this->http->get($url)->throw();
} catch (RequestException $e) {
    throw CurrencyAdapterException::requestFailed(
        'MyCustomAdapter',
        $e->getMessage(),
        $e
    );
} catch (ConnectionException $e) {
    throw CurrencyAdapterException::connectionFailed(
        'MyCustomAdapter',
        $e->getMessage(),
        $e
    );
}
```

### Currency Code Normalization

Handle different currency input types consistently:

```php
private function normalizeCurrencyCode(
    Currency|CurrencyCode|CryptoCurrency|string $currency
): string {
    if ($currency instanceof CurrencyCode) {
        return $currency->value();
    }

    if ($currency instanceof Currency) {
        return $currency->value;
    }

    if ($currency instanceof CryptoCurrency) {
        return $currency->code();
    }

    return strtoupper(trim((string) $currency));
}
```

### Response Validation

Always validate API responses before processing:

```php
$data = $response->json();

if (! isset($data['rates']) || ! is_array($data['rates'])) {
    throw CurrencyAdapterException::invalidResponse(
        'MyCustomAdapter',
        'Missing or invalid "rates" field in response'
    );
}
```

### Caching

The package handles caching automatically at the service level. Your adapter doesn't need to implement caching logic - just return the raw rates from your provider.

## Example: Complete Custom Adapter

Here's a complete example of a custom adapter:

```php
<?php

declare(strict_types=1);

namespace App\Currency\Adapters;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

final class ExchangeRateApiAdapter implements CurrencyAdapter
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUri,
        private readonly float $timeout
    ) {
    }

    public static function fromConfig(HttpFactory $http, array $config): self
    {
        return new self(
            $http,
            (string) ($config['api_key'] ?? ''),
            (string) ($config['base_uri'] ?? 'https://api.exchangerate-api.com/v4'),
            (float) ($config['timeout'] ?? 10.0)
        );
    }

    public function getRate(
        CurrencyEnum|CurrencyCode|string $base,
        CurrencyEnum|CurrencyCode|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        $baseCode = $this->normalizeCurrencyCode($base);
        $quotes = is_array($quote) ? $quote : [$quote];
        $quoteCodes = array_map(fn ($q) => $this->normalizeCurrencyCode($q), $quotes);

        $rates = $this->fetchRates($baseCode, $quoteCodes, $date);

        $result = array_map(
            fn (string $code, float $rate) => new CurrencyRate(
                code: new CurrencyCode($code),
                name: $code,
                numericCode: '000',
                rate: $rate,
                unitRate: $rate,
                nominal: 1
            ),
            array_keys($rates),
            $rates
        );

        return is_array($quote) ? $result : $result[0];
    }

    private function fetchRates(string $base, array $quotes, DateTimeInterface $date): array
    {
        $url = sprintf(
            '%s/latest/%s',
            rtrim($this->baseUri, '/'),
            $base
        );

        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->get($url, [
                    'access_key' => $this->apiKey,
                    'symbols' => implode(',', $quotes),
                ])
                ->throw();
        } catch (RequestException $e) {
            throw CurrencyAdapterException::requestFailed(
                'ExchangeRateApi',
                $e->getMessage(),
                $e
            );
        } catch (ConnectionException $e) {
            throw CurrencyAdapterException::connectionFailed(
                'ExchangeRateApi',
                $e->getMessage(),
                $e
            );
        }

        $data = $response->json();

        if (! isset($data['rates']) || ! is_array($data['rates'])) {
            throw CurrencyAdapterException::invalidResponse(
                'ExchangeRateApi',
                'Missing or invalid "rates" field in response'
            );
        }

        return $data['rates'];
    }

    private function normalizeCurrencyCode(CurrencyEnum|CurrencyCode|string $currency): string
    {
        if ($currency instanceof CurrencyCode) {
            return $currency->value();
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->value;
        }

        return strtoupper(trim((string) $currency));
    }
}
```

