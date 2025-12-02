<?php

declare(strict_types=1);

namespace SvkDigital\Currency\DTO;

use DateTimeImmutable;

final class RatesOnDateDTO
{
    /**
     * @param  list<CurrencyRateDTO>  $rates
     */
    public function __construct(
        public readonly DateTimeImmutable $onDate,
        public readonly array $rates
    ) {}
}
