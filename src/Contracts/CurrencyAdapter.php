<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use DateTimeInterface;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

interface CurrencyAdapter
{
    /**
     * @param  array<int, FiatCurrency|CryptoCurrency>|FiatCurrency|CryptoCurrency  $quote
     * @return CurrencyRate|array<int, CurrencyRate>
     */
    public function getRate(
        FiatCurrency|CryptoCurrency $base,
        FiatCurrency|CryptoCurrency|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array;
}
