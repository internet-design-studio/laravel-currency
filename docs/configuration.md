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

### Publishing and Registering Service Provider

The service provider is auto-discovered by Laravel. However, if you need to manually register it or override provider selection logic, you can publish it:

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

#### Overriding Provider Selection

By publishing the service provider, you can override the `provider()` method to dynamically select a provider without modifying configuration files. This is useful when you need to:

- Switch providers based on environment
- Use different providers for different users
- Select providers based on request parameters
- Implement provider fallback logic

Example:

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

You can also completely override the adapter registration logic. See [Custom Adapters - Override Provider Resolution](custom-adapters.md#option-b-override-provider-resolution-recommended) for detailed instructions.

## Configuration File

`config/currency.php` contains the following sections.

### Default provider

```php
'provider' => env('CURRENCY_PROVIDER', 'currency_freaks'),
```

You can point this to any class that implements `SvkDigital\Currency\Contracts\CurrencyServiceContract`.

### Providers

Built-in CBR adapter (RUB base):

```php
'providers' => [
    'cbr' => [
        'base_uri' => env('CBR_DAILY_INFO_BASE_URI', 'https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx'),
        'http' => [
            'timeout' => env('CBR_HTTP_TIMEOUT', 10.0),
            'retry' => [
                'times' => env('CBR_HTTP_RETRY_TIMES', 1),
                'sleep' => env('CBR_HTTP_RETRY_SLEEP', 100),
            ],
        ],
    ],
],
```

To register your own adapter, you can either:

1. **Configuration-based**: Add your adapter configuration and ensure the adapter class follows the naming convention (see [Custom Adapters](custom-adapters.md))
2. **Service Provider Override**: Publish the service provider and override the adapter registration (recommended for application-specific adapters)

See [Custom Adapters](custom-adapters.md) for complete instructions on creating and registering custom adapters.

### Cache layer

```php
'cache' => [
    'enabled' => env('CURRENCY_CACHE_ENABLED', true),
    'ttl' => env('CURRENCY_CACHE_TTL', 3600),
    'store' => env('CURRENCY_CACHE_STORE'),
    'prefix' => env('CURRENCY_CACHE_PREFIX', 'currency_rates'),
],
```

Cache can be disabled entirely or pointed to a dedicated store (Redis, Memcached, etc.).
