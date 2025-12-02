<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters;

use DateTimeInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use InvalidArgumentException;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyClient;
use SvkDigital\Currency\DTO\CurrencyRateDTO;
use SvkDigital\Currency\DTO\RatesOnDateDTO;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\RatesOnDate;

/**
 * Adapter for the Central Bank of Russia SOAP client.
 *
 * CBR always exposes rates with RUB as the base currency. This adapter is
 * responsible for:
 *  - fetching raw DTOs from the SOAP client
 *  - converting them into value objects
 *  - enforcing that the base currency is RUB
 *  - resolving quotes from the provider's daily rates snapshot
 */
final class CbrAdapter implements CurrencyAdapter
{
    public function __construct(
        private readonly CurrencyClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(HttpFactory $http, array $config): self
    {
        $client = \SvkDigital\Currency\Http\CbrXmlClient::fromConfig($http, $config);

        return new self($client);
    }

    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        if ($base instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CBR adapter does not support crypto currencies as base.');
        }

        if ($quote instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CBR adapter does not support crypto currencies as quote.');
        }

        if (is_array($quote)) {
            foreach ($quote as $q) {
                if ($q instanceof CryptoCurrency) {
                    throw new InvalidArgumentException('CBR adapter does not support crypto currencies as quote.');
                }
            }
        }

        // CBR publishes rates as "1 unit of currency = X RUB".
        // For this built-in adapter we only support RUB as the base currency.
        $baseCode = $this->normalizeCurrencyCode($base);

        if ($baseCode->value() !== CurrencyEnum::RUB->value) {
            throw new InvalidArgumentException('CBR adapter supports only RUB as base currency.');
        }

        $ratesOnDate = $this->ratesForDate($date);

        if (is_array($quote)) {
            return array_map(
                fn ($q) => $ratesOnDate->get($this->normalizeCurrencyCode($q)),
                $quote
            );
        }

        return $ratesOnDate->get($this->normalizeCurrencyCode($quote));
    }

    private function ratesForDate(DateTimeInterface $date): RatesOnDate
    {
        $dto = $this->client->getRatesOnDate($date);

        return $this->mapRates($dto);
    }

    private function mapRates(RatesOnDateDTO $dto): RatesOnDate
    {
        $rates = array_map(
            static fn (CurrencyRateDTO $rate) => new CurrencyRate(
                code: new CurrencyCode($rate->charCode),
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

    private function normalizeCurrencyCode(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $currency
    ): CurrencyCode {
        if ($currency instanceof CurrencyCode) {
            return $currency;
        }

        if ($currency instanceof CryptoCurrency) {
            throw new InvalidArgumentException('CBR adapter does not support crypto currencies.');
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->toCurrencyCode();
        }

        return new CurrencyCode($currency);
    }
}
