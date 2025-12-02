<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

final readonly class CurrencyRate
{
    public function __construct(
        public CurrencyCode $code,
        public string $name,
        public string $numericCode,
        /**
         * Raw rate value as returned by the underlying provider.
         */
        public float $rate,
        /**
         * Value of one unit of currency in the provider's base currency (if available).
         */
        public ?float $unitRate,
        public ?int $nominal = null,
    ) {}

    /**
     * Value of one unit of currency in the provider's base currency.
     */
    public function perUnit(): float
    {
        if ($this->unitRate !== null) {
            return $this->unitRate;
        }

        return $this->rate / $this->nominal;
    }
}
