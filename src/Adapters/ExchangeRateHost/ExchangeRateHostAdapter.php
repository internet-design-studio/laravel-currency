<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\ExchangeRateHost;

use DateTimeInterface;
use Illuminate\Http\Client\Factory;
use SvkDigital\Currency\Adapters\AbstractAdapter;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

/**
 * Adapter for the ExchangeRateHost HTTP API.
 *
 * This adapter supports real-time and historical exchange rates.
 * API documentation: https://exchangerate.host/documentation
 */
final class ExchangeRateHostAdapter extends AbstractAdapter
{
    public function __construct(
        private readonly ExchangeRateHostHttpClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $client = ExchangeRateHostHttpClient::fromConfig(app(Factory::class), $config);

        return new self($client);
    }

    /**
     * @param  array<int, FiatCurrency|CryptoCurrency>  $quote
     */
    public function getRate(
        FiatCurrency|CryptoCurrency $base,
        FiatCurrency|CryptoCurrency|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        $quotes = is_array($quote) ? $quote : [$quote];

        $symbolCodes = array_map(
            static fn (FiatCurrency|CryptoCurrency $currency) => $currency->code(),
            $quotes
        );

        $rates = $this->client->getRates($base->code(), $symbolCodes, $date);
        $result = [];

        foreach ($symbolCodes as $code) {
            if (! array_key_exists($code, $rates)) {
                continue;
            }

            $rateValue = (float) $rates[$code];
            $result[] = $this->makeCurrencyRate($code, $rateValue);
        }

        if (! is_array($quote)) {
            if ($result === []) {
                throw CurrencyAdapterException::invalidResponse(
                    'ExchangeRateHost',
                    sprintf('Rate for %s/%s not found in response.', $base->code(), $quotes[0]->code())
                );
            }

            return $result[0];
        }

        return $result;
    }

    private function makeCurrencyRate(string $code, float $rate): CurrencyRate
    {
        $currencyCode = new FiatCurrency($code);

        // ExchangeRateHost doesn't provide currency names or numeric codes in the live endpoint
        // We'll use the currency code as the name and a placeholder for numeric code
        $name = $code;
        $numericCode = '000';

        return new CurrencyRate(
            currency: $currencyCode,
            name: $name,
            numericCode: $numericCode,
            rate: $rate,
            unitRate: $rate,
            nominal: 1,
        );
    }
}
