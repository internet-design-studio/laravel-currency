<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use LogicException;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

final class CurrencyConverterService
{
    private FiatCurrency|CryptoCurrency|null $base = null;

    private FiatCurrency|CryptoCurrency|null $quote = null;

    private ?DateTimeInterface $date = null;

    private float|int|null $amount = null;

    public function __construct(
        private readonly CurrencyAdapter $adapter,
        private readonly ?CacheRepository $cache = null,
        private readonly bool $cacheEnabled = false,
        private readonly int $cacheTtl = 3600,
        private readonly string $cachePrefix = 'currency_rates'
    ) {}

    public function base(FiatCurrency|CryptoCurrency $base): self
    {
        $this->base = $base;

        return $this;
    }

    public function quote(FiatCurrency|CryptoCurrency $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    public function date(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function amount(int|float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function get(): string
    {
        $base = $this->base ?? throw new LogicException('Base currency must be specified before calling get().');
        $quote = $this->quote ?? throw new LogicException('Quote currency must be specified before calling get().');
        $amount = $this->amount ?? throw new LogicException('Amount must be specified before calling get().');
        $date = $this->date ?? new DateTimeImmutable('today');

        $rate = (new CurrencyRatesService(
            $this->adapter,
            $this->cache,
            $this->cacheEnabled,
            $this->cacheTtl,
            $this->cachePrefix
        ))
            ->base($base)
            ->quote($quote)
            ->date($date)
            ->get();

        if (! $rate instanceof CurrencyRate) {
            throw new LogicException('CurrencyConverterService expects single quote conversion.');
        }

        if (! \extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for precise currency calculations. Please install the bcmath extension.');
        }

        // Convert amount to string without scientific notation for BCMath
        $amountString = $this->floatToString((float) $amount);

        // Use bcmul for precise multiplication to avoid precision loss
        $result = \bcmul($rate->perUnit(), $amountString, 20);

        // Remove trailing zeros from the result
        return $this->removeTrailingZeros($result);
    }

    /**
     * Convert float to string without scientific notation for BCMath functions.
     */
    private function floatToString(float $value): string
    {
        // Use sprintf to avoid scientific notation
        $formatted = sprintf('%.20f', $value);
        // Remove trailing zeros but keep at least one decimal place if needed
        $formatted = mb_rtrim($formatted, '0');

        return mb_rtrim($formatted, '.') ?: '0';
    }

    /**
     * Remove trailing zeros from a decimal number string.
     */
    private function removeTrailingZeros(string $value): string
    {
        $parts = explode('.', $value);

        if (count($parts) === 2) {
            $integerPart = $parts[0];
            $decimalPart = mb_rtrim($parts[1], '0');

            return $decimalPart !== '' ? $integerPart.'.'.$decimalPart : $integerPart;
        }

        return $value;
    }
}
