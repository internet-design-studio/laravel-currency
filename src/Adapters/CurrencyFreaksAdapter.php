<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use ValueError;

/**
 * Adapter for the CurrencyFreaks HTTP API.
 *
 * This adapter does not support crypto currencies yet because the underlying
 * value objects expect 3-letter ISO currency codes.
 */
final class CurrencyFreaksAdapter implements CurrencyAdapter
{
    private const DEFAULT_BASE_URI = 'https://api.currencyfreaks.com/v2.0/rates/latest';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUri,
        private readonly string $apiKey,
        private readonly float $timeout,
        private readonly int $retryTimes,
        private readonly int $retrySleep
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(HttpFactory $http, array $config): self
    {
        $httpConfig = $config['http'] ?? [];
        $retryConfig = $httpConfig['retry'] ?? [];

        return new self(
            $http,
            (string) ($config['base_uri'] ?? self::DEFAULT_BASE_URI),
            (string) ($config['api_key'] ?? ''),
            (float) ($httpConfig['timeout'] ?? 10.0),
            (int) ($retryConfig['times'] ?? 1),
            (int) ($retryConfig['sleep'] ?? 100)
        );
    }

    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        if ($base instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CurrencyFreaks adapter does not support crypto currencies as base yet.');
        }

        if ($quote instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CurrencyFreaks adapter does not support crypto currencies as quote yet.');
        }

        if (is_array($quote)) {
            foreach ($quote as $q) {
                if ($q instanceof CryptoCurrency) {
                    throw new InvalidArgumentException('CurrencyFreaks adapter does not support crypto currencies as quote yet.');
                }
            }
        }

        $baseCode = $this->stringCurrencyLike($base);
        $symbols = is_array($quote) ? $quote : [$quote];
        $symbolCodes = array_map(
            function (CurrencyEnum|CurrencyCode|CryptoCurrency|string $q): string {
                if ($q instanceof CryptoCurrency) {
                    throw new InvalidArgumentException('CurrencyFreaks adapter does not support crypto currencies.');
                }

                return $this->stringCurrencyLike($q);
            },
            $symbols
        );

        $rates = $this->fetchRates($baseCode, $symbolCodes, $date);

        $result = [];

        foreach ($symbolCodes as $code) {
            if (! array_key_exists($code, $rates)) {
                continue;
            }

            $rateValue = (float) $rates[$code];
            $result[] = $this->makeCurrencyRate($code, $rateValue);
        }

        if (! is_array($quote)) {
            // For single quote return a single CurrencyRate or fail if missing.
            if ($result === []) {
                throw CurrencyAdapterException::invalidResponse(
                    'CurrencyFreaks',
                    sprintf('Rate for %s/%s not found in response.', $baseCode, $this->stringCurrencyLike($quote))
                );
            }

            return $result[0];
        }

        return $result;
    }

    /**
     * @param  list<string>  $symbols
     * @return array<string, float|string>
     *
     * @throws CurrencyAdapterException
     */
    private function fetchRates(string $baseCode, array $symbols, DateTimeInterface $date): array
    {
        $query = [
            'base' => $baseCode,
            'symbols' => implode(',', $symbols),
            // CurrencyFreaks free plan only supports latest; date is here for
            // potential future use or paid plans.
            // 'date' => $date->format('Y-m-d'),
            'apikey' => $this->apiKey,
        ];

        try {
            $response = $this->pendingRequest()
                ->get($this->endpoint(), $query)
                ->throw();
        } catch (RequestException $e) {
            throw CurrencyAdapterException::requestFailed(
                'CurrencyFreaks',
                $e->getMessage(),
                $e
            );
        } catch (ConnectionException $e) {
            throw CurrencyAdapterException::connectionFailed(
                'CurrencyFreaks',
                $e->getMessage(),
                $e
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        if (! isset($data['rates']) || ! is_array($data['rates'])) {
            throw CurrencyAdapterException::invalidResponse(
                'CurrencyFreaks',
                'Missing or invalid "rates" field in response'
            );
        }

        /** @var array<string, float|string> $rates */
        $rates = $data['rates'];

        return $rates;
    }

    private function pendingRequest(): PendingRequest
    {
        $request = $this->http
            ->timeout($this->timeout);

        if ($this->retryTimes > 0) {
            $request = $request->retry($this->retryTimes, $this->retrySleep);
        }

        return $request;
    }

    private function endpoint(): string
    {
        return mb_rtrim($this->baseUri, '/');
    }

    private function makeCurrencyRate(string $code, float $rate): CurrencyRate
    {
        $currencyCode = new CurrencyCode($code);

        $name = $code;
        $numericCode = '000';

        try {
            $enum = CurrencyEnum::from($code);
            $name = $enum->currencyName();
            $numericCode = $enum->numericCode();
        } catch (ValueError) {
            // Not a known ISO-4217 code in our enum, leave defaults.
        }

        return new CurrencyRate(
            code: $currencyCode,
            name: $name,
            numericCode: $numericCode,
            rate: $rate,
            unitRate: $rate,
            nominal: 1,
        );
    }

    private function stringCurrencyLike(
        CurrencyEnum|CurrencyCode|string $currency
    ): string {
        if ($currency instanceof CurrencyCode) {
            return $currency->value();
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->value;
        }

        return mb_strtoupper(mb_trim((string) $currency));
    }
}
