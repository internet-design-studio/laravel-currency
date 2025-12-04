<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Fakes;

use DateTimeInterface;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

/**
 * Fake implementation of CurrencyAdapter for testing purposes.
 *
 * This adapter allows you to define fake exchange rates without making
 * real API calls. Use it in your tests to avoid hitting external APIs.
 *
 * @example
 * ```php
 * use SvkDigital\Currency\Facades\Currency;
 * use SvkDigital\Currency\ValueObjects\FiatCurrency;
 * use DateTimeImmutable;
 *
 * // Use the fake() method on the Currency facade
 * Currency::fake()
 *     ->setRateValue(
 *         new FiatCurrency('RUB'),
 *         new FiatCurrency('USD'),
 *         new DateTimeImmutable('2024-01-01'),
 *         90.5
 *     );
 *
 * // Now all currency calls will use the fake adapter
 * $rate = Currency::rate()
 *     ->base(new FiatCurrency('RUB'))
 *     ->quote(new FiatCurrency('USD'))
 *     ->date(new DateTimeImmutable('2024-01-01'))
 *     ->get();
 * ```
 */
final class FakeCurrencyAdapter implements CurrencyAdapter
{
    /**
     * @var array<string, CurrencyRate|array<int, CurrencyRate>>
     */
    private array $rates = [];

    /**
     * Set a fake exchange rate for the given currencies and date.
     *
     * @param  FiatCurrency|CryptoCurrency|string|array<int, FiatCurrency|CryptoCurrency|string>  $quote
     * @param  CurrencyRate|array<int, CurrencyRate>  $rate
     */
    public function setRate(
        FiatCurrency|CryptoCurrency|string       $base,
        FiatCurrency|CryptoCurrency|string|array $quote,
        DateTimeInterface                                     $date,
        CurrencyRate|array                                    $rate
    ): void {
        $key = $this->buildKey($base, $quote, $date);
        $this->rates[$key] = $rate;
    }

    /**
     * Set a fake exchange rate using a simple float value.
     * This is a convenience method that creates a CurrencyRate automatically.
     *
     * @param  FiatCurrency|CryptoCurrency|string|array<int, FiatCurrency|CryptoCurrency|string>  $quote
     * @param  float|array<int, float>  $rateValue
     */
    public function setRateValue(
        FiatCurrency|CryptoCurrency|string       $base,
        FiatCurrency|CryptoCurrency|string|array $quote,
        DateTimeInterface                                     $date,
        float|array                                           $rateValue
    ): void {
        if (is_array($quote) && is_array($rateValue)) {
            $rates = [];
            foreach ($quote as $index => $q) {
                $currency = $this->normalizeCurrencyCode($q);
                $rates[] = new CurrencyRate(
                    currency: $currency,
                    name: $currency->code().' Currency',
                    numericCode: '000',
                    rate: $rateValue[$index] ?? 1.0,
                    unitRate: $rateValue[$index] ?? 1.0,
                    nominal: 1
                );
            }
            $this->setRate($base, $quote, $date, $rates);
        } elseif (is_array($quote)) {
            throw new \InvalidArgumentException('When quote is an array, rateValue must also be an array.');
        } elseif (is_array($rateValue)) {
            throw new \InvalidArgumentException('When quote is not an array, rateValue must not be an array.');
        } else {
            $currency = $this->normalizeCurrencyCode($quote);
            $rate = new CurrencyRate(
                currency: $currency,
                name: $currency->code().' Currency',
                numericCode: '000',
                rate: $rateValue,
                unitRate: $rateValue,
                nominal: 1
            );
            $this->setRate($base, $quote, $date, $rate);
        }
    }

    /**
     * Clear all fake rates.
     */
    public function clear(): void
    {
        $this->rates = [];
    }

    /**
     * @param  array<int, FiatCurrency|CryptoCurrency|string>  $quote
     */
    public function getRate(
        FiatCurrency|CryptoCurrency|string       $base,
        FiatCurrency|CryptoCurrency|string|array $quote,
        DateTimeInterface                                     $date
    ): CurrencyRate|array {
        $key = $this->buildKey($base, $quote, $date);

        if (! isset($this->rates[$key])) {
            throw new \RuntimeException(
                sprintf(
                    'No fake rate set for base=%s, quote=%s, date=%s. Use setRate() or setRateValue() to set fake rates.',
                    $this->stringifyCurrency($base),
                    is_array($quote) ? implode(',', array_map(fn ($q) => $this->stringifyCurrency($q), $quote)) : $this->stringifyCurrency($quote),
                    $date->format('Y-m-d')
                )
            );
        }

        return $this->rates[$key];
    }

    /**
     * @param  array<int, FiatCurrency|CryptoCurrency|string>  $quote
     */
    private function buildKey(
        FiatCurrency|CryptoCurrency|string       $base,
        FiatCurrency|CryptoCurrency|string|array $quote,
        DateTimeInterface                                     $date
    ): string {
        $baseKey = $this->stringifyCurrency($base);
        $quoteItems = is_array($quote) ? $quote : [$quote];
        $quoteKeys = array_map(fn ($q) => $this->stringifyCurrency($q), $quoteItems);
        sort($quoteKeys);

        return sprintf(
            '%s_%s_%s',
            $date->format('Y-m-d'),
            $baseKey,
            implode('-', $quoteKeys)
        );
    }

    private function stringifyCurrency(
        FiatCurrency|CryptoCurrency|string $currency
    ): string {
        if ($currency instanceof FiatCurrency) {
            return $currency->code();
        }

        if ($currency instanceof CryptoCurrency) {
            return (string) $currency;
        }

        return mb_strtoupper(mb_trim((string) $currency));
    }

    private function normalizeCurrencyCode(
        FiatCurrency|CryptoCurrency|string $currency
    ): FiatCurrency {
        if ($currency instanceof FiatCurrency) {
            return $currency;
        }

        if ($currency instanceof CryptoCurrency) {
            // Use the symbol part for crypto currencies
            return new FiatCurrency($currency->symbol());
        }

        return new FiatCurrency($currency);
    }
}
