# Laravel Currency Package

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

```bash
composer require svk-digital/laravel-currency
```

The service provider and facade are auto-discovered by Laravel. Publish the configuration:

```bash
php artisan vendor:publish --tag=currency-config
```

For advanced usage, you can also publish and customize the service provider:

```bash
php artisan vendor:publish --tag=currency-provider
```

## Documentation

Full documentation is available in the [`docs/`](docs/) directory:

- **[Getting Started](docs/index.md)** - Installation and basic usage
- **[Configuration](docs/configuration.md)** - Package configuration options
- **[Built-in Adapters](docs/adapters.md)** - Available adapters (CBR, CurrencyFreaks)
- **[Custom Adapters](docs/custom-adapters.md)** - Create your own adapter and override provider selection

## Features

- ✅ Multiple built-in adapters (CBR, CurrencyFreaks)
- ✅ Support for fiat currencies via ISO-4217 enum
- ✅ Crypto currency support (via custom adapters)
- ✅ Automatic caching with configurable TTL
- ✅ Custom adapter support
- ✅ Dynamic provider selection
- ✅ Comprehensive error handling
- ✅ Multiple quote currencies in a single request

## Built-in Adapters

### CBR Adapter
- Central Bank of Russia SOAP service
- RUB base currency only
- Historical rates support

### CurrencyFreaks Adapter
- REST API integration
- Any base currency support
- API key required

See [Built-in Adapters](docs/adapters.md) for detailed information.

## Custom Adapters

You can create custom adapters to integrate with any currency provider. The package also allows you to override the service provider to implement dynamic provider selection logic without modifying configuration files.

See [Custom Adapters Guide](docs/custom-adapters.md) for complete instructions.

## Testing

The package provides a `fake()` method on the `Currency` facade that allows you to mock exchange rates in your tests without making real API calls, similar to Laravel's `Http::fake()`.

```php
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Facades\Currency;
use DateTimeImmutable;

// In your test setup
Currency::fake()
    ->setRateValue(Currency::RUB, Currency::USD, new DateTimeImmutable('2024-01-01'), 90.5);

// Now your code will use the fake adapter
$rate = Currency::getRate(
    Currency::RUB,
    Currency::USD,
    new DateTimeImmutable('2024-01-01')
);
```

## License

MIT © Internet Design Studio.
