<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

final class CurrencyCodeTest extends TestCase
{
    public function test_it_normalizes_to_uppercase(): void
    {
        $code = new FiatCurrency('usd');

        $this->assertSame('USD', $code->code());
        $this->assertSame('USD', (string) $code);
    }

    public function test_it_rejects_invalid_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FiatCurrency('12');
    }
}
