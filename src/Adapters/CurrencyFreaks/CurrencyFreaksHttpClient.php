<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\CurrencyFreaks;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use SvkDigital\Currency\Contracts\CurrencyClient;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;

final class CurrencyFreaksHttpClient implements CurrencyClient
{
    private const DEFAULT_BASE_URI = 'https://api.currencyfreaks.com/v2.0/rates/latest';

    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUri,
        private readonly string $apiKey,
        private readonly float $timeout,
        private readonly int $retryTimes,
        private readonly int $retrySleep
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(Factory $http, array $config): self
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

    /**
     * @param  list<string>  $symbols
     * @return array<string, float|string>
     *
     * @throws CurrencyAdapterException
     */
    public function getRates(string $baseCode, array $symbols, DateTimeInterface $date): array
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
}
