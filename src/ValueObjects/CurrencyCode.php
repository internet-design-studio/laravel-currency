<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use InvalidArgumentException;

final class CurrencyCode
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtoupper(mb_trim($value));

        if (mb_strlen($normalized) !== 3 || ! ctype_alpha($normalized)) {
            throw new InvalidArgumentException('CurrencyEnum code must be a 3-letter ISO string.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
