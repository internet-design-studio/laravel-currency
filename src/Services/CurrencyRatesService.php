<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use LogicException;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

final class CurrencyRatesService
{
    private FiatCurrency|CryptoCurrency|null $base = null;

    /**
     * @var FiatCurrency|CryptoCurrency|array<int, FiatCurrency|CryptoCurrency>|null
     */
    private FiatCurrency|CryptoCurrency|array|null $quote = null;

    private ?DateTimeInterface $date = null;

    public function __construct(
        private readonly CurrencyAdapter $adapter,
        private readonly ?CacheRepository $cache = null,
        private readonly bool $cacheEnabled = false,
        private readonly int $cacheTtl = 3600,
        private readonly string $cachePrefix = 'currency_rates'
    ) {}

    public function base(FiatCurrency|CryptoCurrency $base): self
    {
        $this->base = $base;

        return $this;
    }

    public function quote(FiatCurrency|CryptoCurrency $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * @param  array<int, FiatCurrency|CryptoCurrency>  $quotes
     */
    public function quotes(array $quotes): self
    {
        $this->quote = $quotes;

        return $this;
    }

    public function date(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return CurrencyRate|array<int, CurrencyRate>
     */
    public function get(): CurrencyRate|array
    {
        $base = $this->base ?? throw new LogicException('Base currency must be specified before calling get().');
        $quote = $this->quote ?? throw new LogicException('Quote currency must be specified before calling get().');
        $date = $this->date ?? new DateTimeImmutable('today');

        return $this->retrieveRate($base, $quote, $date);
    }

    /**
     * @param  FiatCurrency|CryptoCurrency|array<int, FiatCurrency|CryptoCurrency>  $quote
     * @return CurrencyRate|array<int, CurrencyRate>
     */
    private function retrieveRate(
        FiatCurrency|CryptoCurrency $base,
        FiatCurrency|CryptoCurrency|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        if (! $this->shouldCache()) {
            return $this->adapter->getRate($base, $quote, $date);
        }

        $cacheKey = $this->cacheKey($base, $quote, $date);

        /** @var CurrencyRate|array<int, CurrencyRate> $cached */
        $cached = $this->cache->remember(
            $cacheKey,
            max(1, $this->cacheTtl),
            fn () => $this->adapter->getRate($base, $quote, $date)
        );

        return $cached;
    }

    private function shouldCache(): bool
    {
        return $this->cacheEnabled && $this->cache !== null;
    }

    /**
     * @param  FiatCurrency|CryptoCurrency|array<int, FiatCurrency|CryptoCurrency>  $quote
     */
    private function cacheKey(
        FiatCurrency|CryptoCurrency $base,
        FiatCurrency|CryptoCurrency|array $quote,
        DateTimeInterface $date
    ): string {
        $baseKey = $this->stringifyCurrency($base);
        $quoteItems = is_array($quote) ? $quote : [$quote];

        $quoteKeys = array_map(
            fn (FiatCurrency|CryptoCurrency $q) => $this->stringifyCurrency($q),
            $quoteItems
        );

        sort($quoteKeys);

        return sprintf(
            '%s_%s_%s_%s',
            $this->cachePrefix,
            $date->format('Ymd'),
            $baseKey,
            implode('-', $quoteKeys)
        );
    }

    private function stringifyCurrency(FiatCurrency|CryptoCurrency $currency): string
    {
        return $currency->code();
    }
}
