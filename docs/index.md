---
title: Getting Started
---

## Introduction

`svk-digital/laravel-currency` is an extensible Laravel package for working with fiat and crypto currency exchange rates. Out of the box it ships with adapters for the Central Bank of Russia DailyInfo web-service, CurrencyFreaks API, and ExchangeRateHost API, but you can plug in any provider via configuration or custom adapters.

## Requirements

- PHP ^8.1
- Laravel 9.x, 10.x, 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require svk-digital/laravel-currency
```

The service provider and facade are auto-discovered by Laravel.

### Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=currency-config
```

This will create `config/currency.php` in your application.

See [Configuration](configuration.md) for detailed configuration options.

## Quick Start

### Getting Exchange Rates

The package provides a fluent API for getting exchange rates:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

// Via dependency injection
final class RatesController
{
    public function __construct(private CurrencyServiceContract $currencyService) {}

    public function __invoke()
    {
        $date = new DateTimeImmutable('2024-11-30');

        // Get a single exchange rate
        $rate = $this->currencyService
            ->rate()
            ->base(new FiatCurrency('RUB'))
            ->quote(new FiatCurrency('USD'))
            ->date($date)
            ->get();

        // Or via facade
        $rate = Currency::rate()
            ->base(new FiatCurrency('RUB'))
            ->quote(new FiatCurrency('USD'))
            ->date($date)
            ->get();

        // Get multiple exchange rates at once
        $rates = Currency::rate()
            ->base(new FiatCurrency('RUB'))
            ->quotes([
                new FiatCurrency('USD'),
                new FiatCurrency('EUR'),
                new FiatCurrency('GBP')
            ])
            ->date($date)
            ->get();
    }
}
```

### Converting Currency Amounts

Convert amounts from one currency to another:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

$date = new DateTimeImmutable('2024-11-30');

// Convert 1000 RUB to USD
$result = Currency::convert()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->amount(1000)
    ->date($date)
    ->get();

// If date is not specified, current date is used
$result = Currency::convert()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->amount(1000)
    ->get();
```

### Working with Currency Objects

The package provides value objects for working with currencies:

```php
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;

// Fiat currencies (ISO 4217 codes)
$usd = new FiatCurrency('USD');
$rub = new FiatCurrency('RUB');
$eur = new FiatCurrency('EUR');

// Crypto currencies
$btc = new CryptoCurrency('BTC', 'bitcoin');
$eth = new CryptoCurrency('ETH', 'ethereum');
```

### Working with CurrencyRate Results

The `get()` method returns a `CurrencyRate` object (or array of `CurrencyRate` objects):

```php
$rate = Currency::rate()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->date($date)
    ->get();

// Access rate properties
$rate->currency;      // FiatCurrency|CryptoCurrency object
$rate->name;          // string - currency name (e.g., "US Dollar")
$rate->numericCode;   // string - ISO numeric code (e.g., "840")
$rate->rate;          // float - raw rate from provider
$rate->unitRate;      // float|null - rate per unit
$rate->nominal;       // int|null - nominal value

// Helper methods
$rate->perUnit();     // float - rate per unit (unitRate or rate/nominal)
$rate->toArray();     // array - convert to array
(string) $rate;       // string - string representation
```

## Next Steps

- [Configuration](configuration.md) - Configure providers, cache, and service provider
- [Built-in Adapters](adapters.md) - Learn about CBR, CurrencyFreaks, and ExchangeRateHost adapters
- [Custom Adapters](custom-adapters.md) - Create your own adapter
- [Testing](testing.md) - Test your code with fake exchange rates
