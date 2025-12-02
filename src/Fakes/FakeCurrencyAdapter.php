<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Fakes;

use DateTimeInterface;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

/**
 * Fake implementation of CurrencyAdapter for testing purposes.
 *
 * This adapter allows you to define fake exchange rates without making
 * real API calls. Use it in your tests to avoid hitting external APIs.
 *
 * @example
 * ```php
 * use SvkDigital\CurrencyEnum\Facades\CurrencyEnum;
 * use SvkDigital\CurrencyEnum\Enums\CurrencyEnum;
 *
 * // Use the fake() method on the CurrencyEnum facade
 * CurrencyEnum::fake()
 *     ->setRateValue(CurrencyEnum::RUB, CurrencyEnum::USD, new DateTimeImmutable('2024-01-01'), 90.5);
 *
 * // Now all currency calls will use the fake adapter
 * $rate = CurrencyEnum::getRate(CurrencyEnum::RUB, CurrencyEnum::USD, new DateTimeImmutable('2024-01-01'));
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
     * @param  CurrencyEnum|CurrencyCode|CryptoCurrency|string|array<int, CurrencyEnum|CurrencyCode|CryptoCurrency|string>  $quote
     * @param  CurrencyRate|array<int, CurrencyRate>  $rate
     */
    public function setRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date,
        CurrencyRate|array $rate
    ): void {
        $key = $this->buildKey($base, $quote, $date);
        $this->rates[$key] = $rate;
    }

    /**
     * Set a fake exchange rate using a simple float value.
     * This is a convenience method that creates a CurrencyRate automatically.
     *
     * @param  CurrencyEnum|CurrencyCode|CryptoCurrency|string|array<int, CurrencyEnum|CurrencyCode|CryptoCurrency|string>  $quote
     * @param  float|array<int, float>  $rateValue
     */
    public function setRateValue(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date,
        float|array $rateValue
    ): void {
        if (is_array($quote) && is_array($rateValue)) {
            $rates = [];
            foreach ($quote as $index => $q) {
                $code = $this->normalizeCurrencyCode($q);
                $rates[] = new CurrencyRate(
                    code: $code,
                    name: $code->value().' CurrencyEnum',
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
            $code = $this->normalizeCurrencyCode($quote);
            $rate = new CurrencyRate(
                code: $code,
                name: $code->value().' CurrencyEnum',
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

    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
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
     * @param  CurrencyEnum|CurrencyCode|CryptoCurrency|string|array<int, CurrencyEnum|CurrencyCode|CryptoCurrency|string>  $quote
     */
    private function buildKey(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
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
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $currency
    ): string {
        if ($currency instanceof CurrencyCode) {
            return $currency->value();
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->value;
        }

        if ($currency instanceof CryptoCurrency) {
            return (string) $currency;
        }

        return mb_strtoupper(mb_trim((string) $currency));
    }

    private function normalizeCurrencyCode(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $currency
    ): CurrencyCode {
        if ($currency instanceof CurrencyCode) {
            return $currency;
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->toCurrencyCode();
        }

        if ($currency instanceof CryptoCurrency) {
            // Use the symbol part for crypto currencies
            return new CurrencyCode($currency->symbol());
        }

        return new CurrencyCode($currency);
    }
}
