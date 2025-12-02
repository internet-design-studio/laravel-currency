<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests\Enums;

use PHPUnit\Framework\TestCase;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CurrencyCode;

final class CurrencyTest extends TestCase
{
    public function test_country_and_numeric_code_accessors(): void
    {
        $usd = CurrencyEnum::USD;

        $this->assertStringContainsString('США', $usd->country());
        $this->assertSame('Доллар США', $usd->currencyName());
        $this->assertSame('840', $usd->numericCode());
    }

    public function test_it_converts_to_currency_code(): void
    {
        $code = CurrencyEnum::EUR->toCurrencyCode();

        $this->assertInstanceOf(CurrencyCode::class, $code);
        $this->assertSame('EUR', $code->value());
    }
}
