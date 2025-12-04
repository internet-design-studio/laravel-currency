---
title: Configuration
---

## Configuration

### Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=currency-config
```

This will create `config/currency.php` in your application.

## Configuration File

The configuration file contains the following sections:

### Default Provider

```php
'provider' => env('CURRENCY_PROVIDER', 'cbr'),
```

This specifies which adapter to use by default. The value must match one of the keys in the `providers` array below.

### Providers Configuration

Configure your adapters in the `providers` array:

```php
'providers' => [
    'cbr' => [
        'base_uri' => env('CBR_DAILY_INFO_BASE_URI', 'https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx'),
        'http' => [
            'timeout' => env('CBR_HTTP_TIMEOUT', 10.0),
            'retry' => [
                'times' => env('CBR_HTTP_RETRY_TIMES', 1),
                'sleep' => env('CBR_HTTP_RETRY_SLEEP', 100), // milliseconds
            ],
        ],
    ],
    'currency_freaks' => [
        'base_uri' => env('CURRENCY_FREAKS_BASE_URI', 'https://api.currencyfreaks.com/v2.0/rates/latest'),
        'api_key' => env('CURRENCY_FREAKS_API_KEY'),
        'http' => [
            'timeout' => env('CURRENCY_FREAKS_HTTP_TIMEOUT', 10.0),
            'retry' => [
                'times' => env('CURRENCY_FREAKS_HTTP_RETRY_TIMES', 1),
                'sleep' => env('CURRENCY_FREAKS_HTTP_RETRY_SLEEP', 100), // milliseconds
            ],
        ],
    ],
    'exchange_rate_host' => [
        'base_uri' => env('EXCHANGE_RATE_HOST_BASE_URI', 'https://api.exchangerate.host'),
        'access_key' => env('EXCHANGE_RATE_HOST_ACCESS_KEY'), // optional
        'http' => [
            'timeout' => env('EXCHANGE_RATE_HOST_HTTP_TIMEOUT', 10.0),
            'retry' => [
                'times' => env('EXCHANGE_RATE_HOST_HTTP_RETRY_TIMES', 1),
                'sleep' => env('EXCHANGE_RATE_HOST_HTTP_RETRY_SLEEP', 100), // milliseconds
            ],
        ],
    ],
],
```

See [Built-in Adapters](adapters.md) for details on configuring each adapter.

### Cache Configuration

```php
'cache' => [
    'enabled' => env('CURRENCY_CACHE_ENABLED', true),
    'ttl' => env('CURRENCY_CACHE_TTL', 3600), // seconds
    'store' => env('CURRENCY_CACHE_STORE'), // optional: specific cache store
    'prefix' => env('CURRENCY_CACHE_PREFIX', 'currency_rates'),
],
```

- **enabled**: Enable or disable caching of exchange rates
- **ttl**: Time to live for cached rates in seconds (default: 3600 = 1 hour)
- **store**: Optional cache store name (e.g., 'redis', 'memcached'). If not specified, uses the default cache store
- **prefix**: Cache key prefix for currency rates

## Service Provider

The service provider is auto-discovered by Laravel. However, if you need to override provider selection logic or register custom adapters, you can publish it:

```bash
php artisan vendor:publish --tag=currency-provider
```

This will create `app/Providers/CurrencyServiceProvider.php`. Then register it in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CurrencyServiceProvider::class,
],
```

### Overriding Provider Selection

By publishing the service provider, you can override the `provider()` method to dynamically select a provider without modifying configuration files:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use SvkDigital\Currency\CurrencyServiceProvider as BaseServiceProvider;

final class CurrencyServiceProvider extends BaseServiceProvider
{
    protected function provider(): string
    {
        // Use different providers based on environment
        if (app()->environment('production')) {
            return 'currency_freaks';
        }

        // Use different providers based on user preference
        if (auth()->check() && auth()->user()->prefersCbr()) {
            return 'cbr';
        }

        // Use different providers based on request
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

### Registering Custom Adapters

You can also override the adapter registration to use custom adapters. See [Custom Adapters](custom-adapters.md) for detailed instructions.
