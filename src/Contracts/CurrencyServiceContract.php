<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use SvkDigital\Currency\Services\CurrencyConverterService;
use SvkDigital\Currency\Services\CurrencyRatesService;

interface CurrencyServiceContract
{
    /**
     * Get exchange rate(s) for the given base and quote currency(ies) on a specific date.
     */
    public function rate(): CurrencyRatesService;

    /**
     * Convert rate(s) for the given base and quote currency on a specific date.
     */
    public function convert(): CurrencyConverterService;
}
