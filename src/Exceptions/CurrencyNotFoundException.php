<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Exceptions;

use RuntimeException;

final class CurrencyNotFoundException extends RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self(sprintf('Currency with code %s was not found in the rates list.', $code));
    }
}
