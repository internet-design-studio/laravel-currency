<?php

declare(strict_types=1);

namespace SvkDigital\Currency\ValueObjects;

use InvalidArgumentException;

final class CryptoCurrency
{
    public function __construct(
        private string $symbol,
        private string $network
    ) {
        $symbol = mb_strtoupper(mb_trim($symbol));
        $network = mb_strtolower(mb_trim($network));

        if ($symbol === '' || $network === '') {
            throw new InvalidArgumentException('CryptoCurrency requires non-empty symbol and network.');
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

    public function equals(self $other): bool
    {
        return $this->symbol === $other->symbol && $this->network === $other->network;
    }

    public function __toString(): string
    {
        return $this->symbol.'@'.$this->network;
    }
}
