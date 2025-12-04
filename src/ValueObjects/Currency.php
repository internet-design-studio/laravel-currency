<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

interface Currency
{
    public function code(): string;

    public function equals(Currency $other): bool;
}
