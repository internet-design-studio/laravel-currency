<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default adapter (provider)
    |--------------------------------------------------------------------------
    |
    | Which concrete adapter should be used under the hood. This value must
    | match one of the keys in the "providers" array below, e.g. "cbr" or
    | "currency_freaks".
    |
    */
    'provider' => env('CURRENCY_PROVIDER', 'cbr'),

    /*
    |--------------------------------------------------------------------------
    | Providers configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure concrete adapters. Out of the box the package
    | ships with the Central Bank of Russia SOAP adapter which always exposes
    | rates with RUB as the base currency.
    |
    */
    'providers' => [
        'cbr' => [
            /*
             * Endpoint for the Central Bank of Russia DailyInfo web-service.
             */
            'base_uri' => env('CBR_DAILY_INFO_BASE_URI', 'https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx'),

            /*
             * Timeout (in seconds) and retry strategy (attempts + milliseconds sleep)
             * used by the internal HTTP client that fetches XML documents.
             */
            'http' => [
                'timeout' => env('CBR_HTTP_TIMEOUT', 10.0),
                'retry' => [
                    'times' => env('CBR_HTTP_RETRY_TIMES', 1),
                    'sleep' => env('CBR_HTTP_RETRY_SLEEP', 100), // milliseconds
                ],
            ],
        ],
        'currency_freaks' => [
            /*
             * Endpoint and API key for the CurrencyFreaks HTTP API.
             */
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

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Toggle caching for "rates on date" responses and configure TTL/store.
    |
    */
    'cache' => [
        'enabled' => env('CURRENCY_CACHE_ENABLED', true),
        'ttl' => env('CURRENCY_CACHE_TTL', 3600),
        'store' => env('CURRENCY_CACHE_STORE'),
        'prefix' => env('CURRENCY_CACHE_PREFIX', 'currency_rates'),
    ],
];
