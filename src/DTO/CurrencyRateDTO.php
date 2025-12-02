<?php

declare(strict_types=1);

namespace SvkDigital\Currency\DTO;

final class CurrencyRateDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $nominal,
        public readonly float $rate,
        public readonly string $numericCode,
        public readonly string $charCode,
        public readonly ?float $unitRate
    ) {}
}
