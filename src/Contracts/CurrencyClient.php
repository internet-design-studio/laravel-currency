<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Contracts;

use Illuminate\Http\Client\Factory;

interface CurrencyClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(Factory $http, array $config): self;
}
