<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\Cbr;

use DateTimeInterface;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use SvkDigital\Currency\Adapters\AbstractAdapter;
use SvkDigital\Currency\DTO\CurrencyRateDTO;
use SvkDigital\Currency\DTO\RatesOnDateDTO;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use SvkDigital\Currency\ValueObjects\RatesOnDate;

/**
 * Adapter for the Central Bank of Russia SOAP client.
 *
 * CBR always exposes rates with RUB as the base currency.
 */
final class CbrAdapter extends AbstractAdapter
{
    public function __construct(
        private readonly CbrXmlClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $client = CbrXmlClient::fromConfig(app(Factory::class), $config);

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
        $baseCurrency = $this->normalizeFiatCurrency($base);

        if ($baseCurrency->code() !== 'RUB') {
            throw new InvalidArgumentException('CBR adapter supports only RUB as base currency.');
        }

        $quotes = is_array($quote) ? $quote : [$quote];

        $fiatQuotes = array_map(
            fn (FiatCurrency|CryptoCurrency|string $q) => $this->normalizeFiatCurrency($q),
            $quotes
        );

        $rates = $this->mapRates($this->client->getRatesOnDate($date));

        $result = array_map(
            static fn (FiatCurrency $fiatQuote) => $rates->get($fiatQuote),
            $fiatQuotes
        );

        if (! is_array($quote)) {
            return $result[0];
        }

        return array_values($result);
    }

    private function mapRates(RatesOnDateDTO $dto): RatesOnDate
    {
        $rates = array_map(
            static fn (CurrencyRateDTO $rate) => new CurrencyRate(
                currency: new FiatCurrency($rate->charCode),
                name: $rate->name,
                numericCode: $rate->numericCode,
                rate: $rate->rate,
                unitRate: $rate->unitRate,
                nominal: $rate->nominal,
            ),
            $dto->rates
        );

        return new RatesOnDate($dto->onDate, $rates);
    }

    private function normalizeFiatCurrency(
        FiatCurrency|CryptoCurrency|string $currency
    ): FiatCurrency {
        if ($currency instanceof FiatCurrency) {
            return $currency;
        }

        if ($currency instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CBR adapter does not support crypto currencies.');
        }

        return new FiatCurrency((string) $currency);
    }
}
