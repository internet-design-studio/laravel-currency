<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests\Services;

use DateTimeImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use PHPUnit\Framework\MockObject\MockObject;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\Services\CurrencyService;
use SvkDigital\Currency\Tests\TestCase;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

final class CurrencyServiceTest extends TestCase
{
    public function test_it_gets_rate_from_adapter(): void
    {
        $service = CurrencyService::fromConfig(
            $this->fakeAdapter(),
            null,
            ['cache' => ['enabled' => false]]
        );

        $rate = $service->getRate(CurrencyEnum::RUB, CurrencyEnum::USD, new DateTimeImmutable('2024-11-30'));

        $this->assertInstanceOf(CurrencyRate::class, $rate);
        $this->assertSame('USD', $rate->code->value());
    }

    public function test_it_uses_cache_when_enabled(): void
    {
        /** @var CurrencyAdapter&MockObject $adapter */
        $adapter = $this->createMock(CurrencyAdapter::class);
        $adapter->expects($this->once())
            ->method('getRate')
            ->willReturn($this->sampleRate());

        $cache = new CacheRepository(new ArrayStore);

        $service = CurrencyService::fromConfig(
            $adapter,
            $cache,
            ['cache' => ['enabled' => true, 'ttl' => 60, 'prefix' => 'test']]
        );

        $date = new DateTimeImmutable('2024-11-30');

        $service->getRate(CurrencyEnum::RUB, CurrencyEnum::USD, $date);
        $service->getRate(CurrencyEnum::RUB, CurrencyEnum::USD, $date);

        $this->addToAssertionCount(1); // expectations already assert caching
    }

    public function test_it_gets_multiple_rates(): void
    {
        /** @var CurrencyAdapter&MockObject $adapter */
        $adapter = $this->createMock(CurrencyAdapter::class);
        $adapter->method('getRate')
            ->willReturnCallback(function ($base, $quote) {
                if (is_array($quote)) {
                    return [
                        $this->sampleRate('USD'),
                        $this->sampleRate('EUR'),
                    ];
                }

                return $this->sampleRate('USD');
            });

        $service = CurrencyService::fromConfig(
            $adapter,
            null,
            ['cache' => ['enabled' => false]]
        );

        $rates = $service->getRate(
            CurrencyEnum::RUB,
            [CurrencyEnum::USD, CurrencyEnum::EUR],
            new DateTimeImmutable('2024-11-30')
        );

        $this->assertIsArray($rates);
        $this->assertCount(2, $rates);
        $this->assertInstanceOf(CurrencyRate::class, $rates[0]);
    }

    private function fakeAdapter(): CurrencyAdapter
    {
        /** @var CurrencyAdapter&MockObject $adapter */
        $adapter = $this->createMock(CurrencyAdapter::class);
        $adapter->method('getRate')->willReturn($this->sampleRate());

        return $adapter;
    }

    private function sampleRate(?string $code = null): CurrencyRate
    {
        return new CurrencyRate(
            code: new \SvkDigital\Currency\ValueObjects\CurrencyCode($code ?? 'USD'),
            name: 'US Dollar',
            numericCode: '840',
            rate: 90.1234,
            unitRate: 90.1234,
            nominal: 1
        );
    }
}
