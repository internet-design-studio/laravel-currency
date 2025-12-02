<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use DateTimeImmutable;
use DateTimeInterface;
use SvkDigital\Currency\DTO\EnumValutesDTO;
use SvkDigital\Currency\DTO\RatesOnDateDTO;

interface CurrencyClient
{
    /**
     * Fetch daily currency rates published by a concrete provider for the given date.
     */
    public function getRatesOnDate(DateTimeInterface $date): RatesOnDateDTO;

    /**
     * Retrieve the dictionary of currencies (daily or monthly set).
     */
    public function getEnumValutes(bool $monthly = false): EnumValutesDTO;

    /**
     * Retrieve the latest publication timestamp provided by the provider.
     */
    public function getLatestDateTime(): DateTimeImmutable;
}
