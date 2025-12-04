<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use InvalidArgumentException;

final class FiatCurrency implements Currency
{
    private string $value;

    public function __construct(string $code)
    {
        $normalized = mb_strtoupper(mb_trim($code));

        if (mb_strlen($normalized) !== 3 || ! ctype_alpha($normalized)) {
            throw new InvalidArgumentException('Currency code must be a 3-letter ISO string.');
        }

        $this->value = $normalized;
    }

    public function equals(Currency $other): bool
    {
        return $this->code() === $other->code();
    }

    public function code(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
