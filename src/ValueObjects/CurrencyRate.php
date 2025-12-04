<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

final readonly class CurrencyRate
{
    public function __construct(
        public FiatCurrency|CryptoCurrency $currency,
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
     * Uses bcdiv for precise calculations to avoid precision loss.
     */
    public function perUnit(): string
    {
        if (! \extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for precise currency calculations. Please install the bcmath extension.');
        }

        if ($this->unitRate !== null) {
            // Convert float to string without scientific notation
            return $this->floatToString($this->unitRate);
        }

        if ($this->nominal === null || $this->nominal === 0) {
            // Convert float to string without scientific notation
            return $this->floatToString($this->rate);
        }

        // Use bcdiv for precise division, with scale for sufficient decimal places
        return \bcdiv(
            $this->floatToString($this->rate),
            (string) $this->nominal,
            20
        );
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

    public function __toString(): string
    {
        return sprintf(
            '%s: %s (rate: %s, unitRate: %s)',
            $this->currency->code(),
            $this->name,
            (string) $this->rate,
            $this->unitRate !== null ? (string) $this->unitRate : 'N/A'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function __toArray(): array
    {
        return [
            'currency' => $this->currency->code(),
            'name' => $this->name,
            'numericCode' => $this->numericCode,
            'rate' => $this->rate,
            'unitRate' => $this->unitRate,
            'nominal' => $this->nominal,
        ];
    }
}
