<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use DateTimeInterface;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

interface CurrencyServiceContract
{
    /**
     * Get exchange rate(s) for the given base and quote currency(ies) on a specific date.
     *
     * For the built-in CBR adapter the base currency is always RUB; passing a different
     * base will result in an exception.
     *
     * @param  CurrencyEnum|CurrencyCode|CryptoCurrency|string|array<int, CurrencyEnum|CurrencyCode|CryptoCurrency|string>  $quote
     * @return CurrencyRate|array<int, CurrencyRate>
     */
    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array;
}
