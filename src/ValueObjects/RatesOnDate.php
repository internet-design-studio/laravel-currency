<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use DateTimeImmutable;
use SvkDigital\Currency\Exceptions\CurrencyNotFoundException;

final class RatesOnDate
{
    /**
     * @var array<string, CurrencyRate>
     */
    private array $indexedRates = [];

    /**
     * @param  list<CurrencyRate>  $rates
     */
    public function __construct(
        public readonly DateTimeImmutable $date,
        public readonly array $rates
    ) {
        foreach ($rates as $rate) {
            $this->indexedRates[$rate->currency->code()] = $rate;
        }
    }

    /**
     * Find a rate by currency code (case-insensitive).
     */
    public function find(FiatCurrency|string $code): ?CurrencyRate
    {
        $normalized = $this->normalizeCode($code);

        return $this->indexedRates[$normalized->code()] ?? null;
    }

    /**
     * Get a rate by code or throw CurrencyNotFoundException.
     */
    public function get(FiatCurrency $code): CurrencyRate
    {
        $normalized = $this->normalizeCode($code);
        $rate = $this->indexedRates[$normalized->code()] ?? null;

        if ($rate === null) {
            throw CurrencyNotFoundException::forCode($normalized->code());
        }

        return $rate;
    }

    /**
     * @return list<CurrencyRate>
     */
    public function all(): array
    {
        return $this->rates;
    }

    private function normalizeCode(FiatCurrency|string $code): FiatCurrency
    {
        if ($code instanceof FiatCurrency) {
            return $code;
        }

        return new FiatCurrency($code);
    }
}
