---
title: Getting Started
---

## Introduction

`svk-digital/laravel-currency` is an extensible Laravel package for working with fiat and crypto currency exchange rates. Out of the box it ships with adapters for the Central Bank of Russia DailyInfo web-service and CurrencyFreaks API, but you can plug in any provider via configuration or custom adapters.

## Requirements

- PHP ^8.1
- Laravel 9.x, 10.x, 11.x or 12.x

## Quick Start

```php
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Facades\Currency as CurrencyFacade;

final class RatesController
{
    public function __construct(private CurrencyServiceContract $rates) {}

    public function __invoke()
    {
        $today = now();

        // Base currency, quote currency, date
        $usdToEur = $this->rates->getRate(CurrencyEnum::USD, CurrencyEnum::EUR, $today);

        // Or via facade
        $usdToRub = CurrencyFacade::getRate(CurrencyEnum::USD, CurrencyEnum::RUB, $today);
    }
}
```

## Installation

Install the package via Composer:

```bash
composer require svk-digital/laravel-currency
```

The service provider and facade are auto-discovered by Laravel. However, if you need to manually register the service provider, you can publish it:

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

### Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=currency-config
```

This will create `config/currency.php` in your application.

## Usage

### Service Container Injection

```php
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Enums\CurrencyEnum;

class RatesController
{
    public function __construct(private CurrencyServiceContract $currencyService) {}

    public function __invoke()
    {
        $date = now();

        // Base = RUB (CBR adapter), quote = USD
        $rubToUsd = $this->currencyService->getRate(CurrencyEnum::RUB, CurrencyEnum::USD, $date);
    }
}
```

### Facade

```php
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Facades\Currency as CurrencyFacade;

$eurToRub = CurrencyFacade::getRate(CurrencyEnum::EUR, CurrencyEnum::RUB, now());
```

### Multiple Quotes

You can request rates for multiple quote currencies at once:

```php
$rates = $currencyService->getRate(
    Currency::USD,
    [Currency::EUR, Currency::GBP, Currency::RUB],
    now()
);

// Returns an array of CurrencyRate objects
foreach ($rates as $rate) {
    echo $rate->code->value() . ': ' . $rate->rate . PHP_EOL;
}
```

## Currency Enum

The package exposes `SvkDigital\Currency\Enums\Currency`, a string-backed enum that covers the ISO-4217 table. Each case carries helper methods:

- **`country()`** – returns the country (or list of territories) where the currency is used.
- **`currencyName()`** – localized currency name.
- **`numericCode()`** – numeric ISO code (e.g., `840` for USD).
- **`toCurrencyCode()`** – converts to `CurrencyCode` value object.

```php
use SvkDigital\Currency\Enums\CurrencyEnum;

CurrencyEnum::USD->country();      // "Американское Самоа, ..., США, ..."
CurrencyEnum::USD->currencyName(); // "Доллар США"
CurrencyEnum::USD->numericCode();  // "840"
```

## CryptoCurrency Value Object

For crypto assets there is a `SvkDigital\Currency\ValueObjects\CryptoCurrency` value object:

```php
use SvkDigital\Currency\ValueObjects\CryptoCurrency;

$btcMainnet = new CryptoCurrency('BTC', 'bitcoin');
```

Support for concrete crypto providers can be added via custom adapters.

## Error Handling

All adapters throw `CurrencyAdapterException` when HTTP requests fail:

- `CurrencyAdapterException::requestFailed()` - When an HTTP request fails (4xx, 5xx errors)
- `CurrencyAdapterException::connectionFailed()` - When a connection cannot be established
- `CurrencyAdapterException::invalidResponse()` - When the response format is invalid or missing required data

Example error handling:

```php
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;

try {
    $rate = $currencyService->getRate(Currency::USD, Currency::EUR, now());
} catch (CurrencyAdapterException $e) {
    // Handle adapter errors (network issues, API errors, etc.)
    logger()->error('CurrencyEnum adapter error', ['exception' => $e]);
}
```

## Next Steps

- [Configuration](configuration.md) - Learn how to configure the package
- [Built-in Adapters](adapters.md) - Explore available adapters
- [Custom Adapters](custom-adapters.md) - Create your own adapter
- [Service Provider Override](custom-adapters.md#dynamic-provider-selection) - Override provider selection logic
