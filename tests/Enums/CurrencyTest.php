<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests\Enums;

use PHPUnit\Framework\TestCase;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

final class CurrencyTest extends TestCase
{
    public function test_fiat_currency_normalizes_and_compares_by_code(): void
    {
        $usdLower = new FiatCurrency('usd');
        $usdUpper = new FiatCurrency('USD');

        $this->assertSame('USD', $usdLower->code());
        $this->assertTrue($usdLower->equals($usdUpper));
    }

    public function test_crypto_currency_code_and_equality(): void
    {
        $btcMainnet = new CryptoCurrency('btc', 'btc');
        $btcMainnetUpper = new CryptoCurrency('BTC', 'btc');
        $ethMainnet = new CryptoCurrency('eth', 'eth');

        $this->assertSame('BTC@btc', $btcMainnet->code());
        $this->assertTrue($btcMainnet->equals($btcMainnetUpper));
        $this->assertFalse($btcMainnet->equals($ethMainnet));
    }
}
