<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use DateTimeInterface;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

interface CurrencyAdapter
{
    /**
     * Get exchange rate(s) for the given base and quote currency(ies) on a specific date.
     *
     * Implementations are responsible for talking to a concrete upstream provider
     * (CBR, CurrencyFreaks, etc.), handling base currency semantics and converting
     * everything into a unified value-object response.
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
