<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use InvalidArgumentException;

final class CryptoCurrency implements Currency
{
    public function __construct(
        private string $symbol,
        private ?string $network = null,
    ) {
        $symbol = mb_strtoupper(mb_trim($symbol));
        $network = mb_strtolower(mb_trim($network ?? ''));

        if ($symbol === '') {
            throw new InvalidArgumentException('CryptoCurrency requires non-empty symbol.');
        }

        $this->symbol = $symbol;
        $this->network = $network;
    }

    public function symbol(): string
    {
        return $this->symbol;
    }

    public function network(): string
    {
        return $this->network;
    }

    public function equals(Currency $other): bool
    {
        if ($other instanceof CryptoCurrency) {
            return $this->symbol === $other->symbol && $this->network === $other->network;
        }

        return false;
    }

    public function code(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        if ($this->network !== '') {
            return $this->symbol.'@'.$this->network;
        }

        return $this->symbol;
    }
}
