<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\ExchangeRateHost;

use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use SvkDigital\Currency\Contracts\CurrencyClient;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;

final class ExchangeRateHostHttpClient implements CurrencyClient
{
    private const DEFAULT_BASE_URI = 'https://api.exchangerate.host';

    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUri,
        private readonly ?string $accessKey,
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
            isset($config['access_key']) ? (string) $config['access_key'] : null,
            (float) ($httpConfig['timeout'] ?? 10.0),
            (int) ($retryConfig['times'] ?? 1),
            (int) ($retryConfig['sleep'] ?? 100)
        );
    }

    /**
     * Get real-time or historical exchange rates.
     *
     * @param  string  $source  Base currency code (e.g., 'USD')
     * @param  list<string>  $currencies  List of quote currency codes
     * @param  DateTimeInterface  $date  Date for rates (uses live endpoint if date is today, historical otherwise)
     * @return array<string, float> Array of currency codes to rates
     *
     * @throws CurrencyAdapterException
     */
    public function getRates(string $source, array $currencies, DateTimeInterface $date): array
    {
        $today = new \DateTimeImmutable;
        $isHistorical = $date->format('Y-m-d') !== $today->format('Y-m-d');

        if ($isHistorical) {
            $endpoint = $this->endpoint('/historical');
            $query = [
                'date' => $date->format('Y-m-d'),
                'source' => $source,
            ];
        } else {
            $endpoint = $this->endpoint('/live');
            $query = [
                'source' => $source,
            ];
        }

        if ($this->accessKey !== null) {
            $query['access_key'] = $this->accessKey;
        }

        if ($currencies !== []) {
            $query['currencies'] = implode(',', $currencies);
        }

        try {
            $response = $this->pendingRequest()
                ->get($endpoint, $query)
                ->throw();
        } catch (RequestException $e) {
            throw CurrencyAdapterException::requestFailed(
                'ExchangeRateHost',
                $e->getMessage(),
                $e
            );
        } catch (ConnectionException $e) {
            throw CurrencyAdapterException::connectionFailed(
                'ExchangeRateHost',
                $e->getMessage(),
                $e
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        if (! isset($data['success']) || $data['success'] !== true) {
            $errorInfo = $data['error']['info'] ?? 'Unknown error';
            $errorCode = $data['error']['code'] ?? 'unknown';

            throw CurrencyAdapterException::invalidResponse(
                'ExchangeRateHost',
                sprintf('API returned error: %s (code: %s)', $errorInfo, $errorCode)
            );
        }

        if (! isset($data['quotes']) || ! is_array($data['quotes'])) {
            throw CurrencyAdapterException::invalidResponse(
                'ExchangeRateHost',
                'Missing or invalid "quotes" field in response'
            );
        }

        /** @var array<string, float|int> $quotes */
        $quotes = $data['quotes'];

        // ExchangeRateHost returns quotes in format "BASEQUOTE" (e.g., "USDEUR" for USD to EUR)
        // We need to extract the quote currency and convert to our format
        $result = [];
        $sourceUpper = mb_strtoupper($source);

        foreach ($quotes as $pair => $rate) {
            // Remove the base currency prefix to get the quote currency
            if (mb_strpos($pair, $sourceUpper) === 0) {
                $quoteCode = mb_substr($pair, mb_strlen($sourceUpper));
                $result[$quoteCode] = (float) $rate;
            }
        }

        return $result;
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

    private function endpoint(string $path = ''): string
    {
        $base = mb_rtrim($this->baseUri, '/');
        $path = mb_ltrim($path, '/');

        return $base.($path !== '' ? '/'.$path : '');
    }
}
