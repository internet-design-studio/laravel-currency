<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters;

use SvkDigital\Currency\Contracts\CurrencyAdapter;

abstract class AbstractAdapter implements CurrencyAdapter
{
    /**
     * @param  array<string, mixed>  $config
     */
    abstract public static function fromConfig(array $config): CurrencyAdapter;
}
