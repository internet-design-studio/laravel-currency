---
title: Testing
---

## Testing with Fake Adapter

The package provides a `fake()` method on the `Currency` facade that allows you to mock exchange rates in your tests without making real API calls, similar to Laravel's `Http::fake()`.

## Basic Usage

### Setting Up Fake Rates

Use the `fake()` method to replace the real adapter with a fake one:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

// In your test setup
// You can use FiatCurrency objects or strings
Currency::fake()
    ->setRateValue(
        new FiatCurrency('RUB'),
        new FiatCurrency('USD'),
        new DateTimeImmutable('2024-01-01'),
        90.5
    );

// Or use strings directly
Currency::fake()
    ->setRateValue('RUB', 'USD', new DateTimeImmutable('2024-01-01'), 90.5);

// Now your code will use the fake adapter
$rate = Currency::rate()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->date(new DateTimeImmutable('2024-01-01'))
    ->get();

// $rate->perUnit() will return 90.5
```

### Setting Multiple Rates

You can set multiple rates for different currency pairs and dates:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

$fake = Currency::fake();

$fake->setRateValue(
    new FiatCurrency('RUB'),
    new FiatCurrency('USD'),
    new DateTimeImmutable('2024-01-01'),
    90.5
);

$fake->setRateValue(
    new FiatCurrency('RUB'),
    new FiatCurrency('EUR'),
    new DateTimeImmutable('2024-01-01'),
    100.2
);

$fake->setRateValue(
    new FiatCurrency('USD'),
    new FiatCurrency('EUR'),
    new DateTimeImmutable('2024-01-01'),
    1.1
);
```

### Setting Multiple Quote Currencies

You can set rates for multiple quote currencies at once:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

Currency::fake()
    ->setRateValue(
        new FiatCurrency('RUB'),
        [
            new FiatCurrency('USD'),
            new FiatCurrency('EUR'),
        ],
        new DateTimeImmutable('2024-01-01'),
        [90.5, 100.2] // Array of rates matching the quotes array
    );

// Now you can get multiple rates
$rates = Currency::rate()
    ->base(new FiatCurrency('RUB'))
    ->quotes([
        new FiatCurrency('USD'),
        new FiatCurrency('EUR'),
    ])
    ->date(new DateTimeImmutable('2024-01-01'))
    ->get();

// $rates is an array of CurrencyRate objects
```

### Using CurrencyRate Objects

For more control, you can set `CurrencyRate` objects directly:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

$rate = new CurrencyRate(
    currency: new FiatCurrency('USD'),
    name: 'US Dollar',
    numericCode: '840',
    rate: 90.5,
    unitRate: 90.5,
    nominal: 1
);

Currency::fake()
    ->setRate(
        new FiatCurrency('RUB'),
        new FiatCurrency('USD'),
        new DateTimeImmutable('2024-01-01'),
        $rate
    );
```

### Clearing Fake Rates

Clear all fake rates between tests:

```php
use SvkDigital\Currency\Facades\Currency;

Currency::fake()->clear();
```

## Complete Test Example

Here's a complete example of testing a service that uses the currency package:

```php
<?php

declare(strict_types=1);

namespace Tests\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

final class PaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up fake rates for all tests
        Currency::fake()
            ->setRateValue(
                new FiatCurrency('RUB'),
                new FiatCurrency('USD'),
                new DateTimeImmutable('2024-01-01'),
                90.5
            );
    }

    protected function tearDown(): void
    {
        // Clear fake rates after each test
        Currency::fake()->clear();
        
        parent::tearDown();
    }

    public function test_converts_rub_to_usd(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        
        $result = Currency::convert()
            ->base(new FiatCurrency('RUB'))
            ->quote(new FiatCurrency('USD'))
            ->amount(1000)
            ->date($date)
            ->get();

        // 1000 RUB / 90.5 = 11.05 USD
        $this->assertEquals(11.05, $result, '', 0.01);
    }

    public function test_gets_exchange_rate(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        
        $rate = Currency::rate()
            ->base(new FiatCurrency('RUB'))
            ->quote(new FiatCurrency('USD'))
            ->date($date)
            ->get();

        $this->assertEquals(90.5, $rate->perUnit());
        $this->assertEquals('USD', $rate->currency->code());
    }
}
```

## Testing Different Scenarios

### Testing with Different Dates

Set different rates for different dates:

```php
$fake = Currency::fake();

$fake->setRateValue(
    new FiatCurrency('RUB'),
    new FiatCurrency('USD'),
    new DateTimeImmutable('2024-01-01'),
    90.5
);

$fake->setRateValue(
    new FiatCurrency('RUB'),
    new FiatCurrency('USD'),
    new DateTimeImmutable('2024-01-02'),
    91.0
);
```

### Testing Error Scenarios

If you try to get a rate that hasn't been set, the fake adapter will throw a `RuntimeException`:

```php
use DateTimeImmutable;
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

Currency::fake();

// This will throw RuntimeException because no rate was set
$this->expectException(\RuntimeException::class);
$this->expectExceptionMessage('No fake rate set');

Currency::rate()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->date(new DateTimeImmutable('2024-01-01'))
    ->get();
```

## Tips

1. **Set up fake rates in `setUp()`**: This ensures consistent test data across all tests
2. **Clear fake rates in `tearDown()`**: Prevents test pollution
3. **Use descriptive rate values**: Use values that make calculations easy to verify (e.g., 100.0 instead of 99.876543)
4. **Test with multiple currencies**: Set rates for all currencies your code uses
5. **Test with different dates**: Ensure your code handles historical rates correctly

