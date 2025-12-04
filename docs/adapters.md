---
title: Built-in Adapters
---

## Built-in Adapters

The package comes with three built-in adapters that you can use out of the box.

## CBR Adapter

The **CBR (Central Bank of Russia) Adapter** connects to the Central Bank of Russia DailyInfo SOAP web service.

### Features

- **Base Currency**: Only supports RUB (Russian Ruble) as the base currency
- **Protocol**: SOAP over HTTP
- **Data Source**: Official Central Bank of Russia daily exchange rates
- **Crypto Support**: Does not support crypto currencies
- **Rate Format**: Returns rates as published by the central bank (1 unit of currency = X RUB)

### Configuration

Add the following to your `config/currency.php`:

```php
'provider' => 'cbr',

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
],
```

### Usage Example

```php
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use DateTimeImmutable;

// CBR adapter only supports RUB as base currency
$rate = Currency::rate()
    ->base(new FiatCurrency('RUB'))
    ->quote(new FiatCurrency('USD'))
    ->date(new DateTimeImmutable('2024-11-30'))
    ->get();
```

### Limitations

- **Base Currency Restriction**: Attempting to use any currency other than RUB as the base will throw an exception
- **Crypto Currencies**: Crypto currencies are not supported
- **Date Support**: Supports historical rates for any date available in the CBR database

### Error Handling

The adapter throws `CurrencyAdapterException` in the following cases:
- HTTP request failures (4xx, 5xx errors)
- Connection failures
- Invalid or malformed SOAP responses

---

## CurrencyFreaks Adapter

The **CurrencyFreaks Adapter** connects to the CurrencyFreaks HTTP API for real-time and historical exchange rates.

### Features

- **Base Currency**: Supports any ISO-4217 currency as base
- **Protocol**: REST API over HTTP
- **Data Source**: CurrencyFreaks API
- **Crypto Support**: Does not support crypto currencies yet
- **API Key Required**: Requires a valid API key from CurrencyFreaks

### Configuration

Add the following to your `config/currency.php`:

```php
'provider' => 'currency_freaks',

'providers' => [
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
],
```

Don't forget to set your API key in your `.env` file:

```env
CURRENCY_FREAKS_API_KEY=your_api_key_here
```

### Usage Example

```php
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use DateTimeImmutable;

// CurrencyFreaks supports any base currency
$rate = Currency::rate()
    ->base(new FiatCurrency('USD'))
    ->quote(new FiatCurrency('EUR'))
    ->date(new DateTimeImmutable('2024-11-30'))
    ->get();
```

### Limitations

- **Crypto Currencies**: Crypto currencies are not yet supported
- **Free Plan**: The free plan only supports latest rates (historical dates may not work)
- **API Key**: A valid API key is required for all requests

### Error Handling

The adapter throws `CurrencyAdapterException` in the following cases:
- HTTP request failures (4xx, 5xx errors, invalid API key)
- Connection failures
- Invalid or malformed API responses

---

## ExchangeRateHost Adapter

The **ExchangeRateHost Adapter** connects to the ExchangeRateHost HTTP API for real-time and historical exchange rates.

### Features

- **Base Currency**: Supports any ISO-4217 currency as base
- **Protocol**: REST API over HTTP
- **Data Source**: ExchangeRateHost API
- **Crypto Support**: Does not support crypto currencies
- **Access Key**: Optional access key (free tier available)

### Configuration

Add the following to your `config/currency.php`:

```php
'provider' => 'exchange_rate_host',

'providers' => [
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

### Usage Example

```php
use SvkDigital\Currency\Facades\Currency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use DateTimeImmutable;

// ExchangeRateHost supports any base currency
$rate = Currency::rate()
    ->base(new FiatCurrency('USD'))
    ->quote(new FiatCurrency('EUR'))
    ->date(new DateTimeImmutable('2024-11-30'))
    ->get();
```

### Limitations

- **Crypto Currencies**: Crypto currencies are not supported
- **Access Key**: Access key is optional but recommended for production use

### Error Handling

The adapter throws `CurrencyAdapterException` in the following cases:
- HTTP request failures (4xx, 5xx errors)
- Connection failures
- Invalid or malformed API responses

---

## Custom Adapters

You can create your own adapter by implementing the `CurrencyAdapter` interface. See the [Custom Adapters Guide](custom-adapters.md) for detailed instructions.
