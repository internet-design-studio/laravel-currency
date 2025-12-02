<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use DateTimeImmutable;
use SvkDigital\Currency\Enums\CurrencyEnum;
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
            $this->indexedRates[$rate->code->value()] = $rate;
        }
    }

    /**
     * Find a rate by currency code (case-insensitive).
     */
    public function find(CurrencyEnum|CurrencyCode|string $code): ?CurrencyRate
    {
        $normalized = $this->normalizeCode($code);

        return $this->indexedRates[$normalized->value()] ?? null;
    }

    /**
     * Get a rate by code or throw CurrencyNotFoundException.
     */
    public function get(CurrencyEnum|CurrencyCode|string $code): CurrencyRate
    {
        $normalized = $this->normalizeCode($code);
        $rate = $this->indexedRates[$normalized->value()] ?? null;

        if ($rate === null) {
            throw CurrencyNotFoundException::forCode($normalized->value());
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

    private function normalizeCode(CurrencyEnum|CurrencyCode|string $code): CurrencyCode
    {
        if ($code instanceof CurrencyCode) {
            return $code;
        }

        if ($code instanceof CurrencyEnum) {
            return $code->toCurrencyCode();
        }

        return new CurrencyCode($code);
    }
}
