---
title: Adapters
---

## Built-in Adapters

The package comes with two built-in adapters that you can use out of the box.

### CBR Adapter

The **CBR (Central Bank of Russia) Adapter** connects to the Central Bank of Russia DailyInfo SOAP web service.

#### Features

- **Base Currency**: Only supports RUB (Russian Ruble) as the base currency
- **Protocol**: SOAP over HTTP
- **Data Source**: Official Central Bank of Russia daily exchange rates
- **Crypto Support**: Does not support crypto currencies
- **Rate Format**: Returns rates as published by the central bank (1 unit of currency = X RUB)

#### Configuration

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
],
```

#### Usage Example

```php
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Facades\Currency;

// CBR adapter only supports RUB as base currency
$rate = Currency::getRate(Currency::RUB, Currency::USD, now());
```

#### Limitations

- **Base Currency Restriction**: Attempting to use any currency other than RUB as the base will throw an `InvalidArgumentException`
- **Crypto Currencies**: Crypto currencies are not supported
- **Date Support**: Supports historical rates for any date available in the CBR database

#### Error Handling

The adapter throws `CurrencyAdapterException` in the following cases:
- `requestFailed()` - When HTTP requests fail (4xx, 5xx errors)
- `connectionFailed()` - When connection cannot be established
- `invalidResponse()` - When SOAP response is malformed or missing required data

---

### CurrencyFreaks Adapter

The **CurrencyFreaks Adapter** connects to the CurrencyFreaks HTTP API for real-time and historical exchange rates.

#### Features

- **Base Currency**: Supports any ISO-4217 currency as base
- **Protocol**: REST API over HTTP
- **Data Source**: CurrencyFreaks API
- **Crypto Support**: Does not support crypto currencies yet
- **API Key Required**: Requires a valid API key from CurrencyFreaks

#### Configuration

```php
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

#### Usage Example

```php
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Facades\Currency;

// CurrencyFreaks supports any base currency
$rate = CurrencyEnum::getRate(CurrencyEnum::USD, CurrencyEnum::EUR, now());
```

#### Limitations

- **Crypto Currencies**: Crypto currencies are not yet supported
- **Free Plan**: The free plan only supports latest rates (historical dates may not work)
- **API Key**: A valid API key is required for all requests

#### Error Handling

The adapter throws `CurrencyAdapterException` in the following cases:
- `requestFailed()` - When HTTP requests fail (4xx, 5xx errors, invalid API key)
- `connectionFailed()` - When connection cannot be established
- `invalidResponse()` - When response format is invalid or missing required data

---

## Custom Adapters

You can create your own adapter by implementing the `CurrencyAdapter` interface. See the [Custom Adapters Guide](custom-adapters.md) for detailed instructions.

