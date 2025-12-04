<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\CurrencyFreaks;

use DateTimeInterface;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use SvkDigital\Currency\Adapters\AbstractAdapter;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

/**
 * Adapter for the CurrencyFreaks HTTP API.
 *
 * This adapter does not support crypto currencies yet because the underlying
 * value objects expect 3-letter ISO currency codes.
 */
final class CurrencyFreaksAdapter extends AbstractAdapter implements CurrencyAdapter
{
    public function __construct(
        private readonly CurrencyFreaksHttpClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $client = CurrencyFreaksHttpClient::fromConfig(app(Factory::class), $config);

        return new self($client);
    }

    public function getRate(
        FiatCurrency|CryptoCurrency|string $base,
        FiatCurrency|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        $baseCurrency = $this->normalizeFiatCurrency($base, 'base');

        $quotes = is_array($quote) ? $quote : [$quote];

        $fiatQuotes = array_map(
            fn (FiatCurrency|CryptoCurrency|string $q) => $this->normalizeFiatCurrency($q, 'quote'),
            $quotes
        );

        $symbolCodes = array_map(
            static fn (FiatCurrency $currency) => $currency->code(),
            $fiatQuotes
        );

        $rates = $this->client->getRates($baseCurrency->code(), $symbolCodes, $date);
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
                    'CurrencyFreaks',
                    sprintf('Rate for %s/%s not found in response.', $baseCurrency->code(), $fiatQuotes[0]->code())
                );
            }

            return $result[0];
        }

        return $result;
    }

    private function makeCurrencyRate(string $code, float $rate): CurrencyRate
    {
        $currencyCode = new FiatCurrency($code);

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

    private function normalizeFiatCurrency(
        FiatCurrency|CryptoCurrency|string $currency,
        string $role
    ): FiatCurrency {
        if ($currency instanceof CryptoCurrency) {
            throw new InvalidArgumentException(sprintf('CurrencyFreaks adapter does not support crypto currencies as %s yet.', $role));
        }

        if ($currency instanceof FiatCurrency) {
            return $currency;
        }

        return new FiatCurrency((string) $currency);
    }
}
