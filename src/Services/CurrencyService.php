<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Services;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\Enums\CurrencyEnum;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyCode;
use SvkDigital\Currency\ValueObjects\CurrencyRate;

final class CurrencyService implements CurrencyServiceContract
{
    private const DEFAULT_CACHE_PREFIX = 'currency_rates';

    public function __construct(
        private readonly CurrencyAdapter $adapter,
        private readonly ?CacheRepository $cache,
        private readonly bool $cacheEnabled,
        private readonly int $cacheTtl,
        private readonly string $cachePrefix
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(CurrencyAdapter $adapter, ?CacheRepository $cache, array $config): self
    {
        $cacheConfig = $config['cache'] ?? [];

        return new self(
            $adapter,
            $cache,
            (bool) ($cacheConfig['enabled'] ?? false),
            (int) ($cacheConfig['ttl'] ?? 3600),
            (string) ($cacheConfig['prefix'] ?? self::DEFAULT_CACHE_PREFIX)
        );
    }

    public function getRate(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {

        if (! $this->shouldCache()) {
            return $this->adapter->getRate($base, $quote, $date);
        }

        $key = $this->cacheKey($base, $quote, $date);

        /** @var CurrencyRate|array<int, CurrencyRate> $cached */
        $cached = $this->cache->remember(
            $key,
            max(1, $this->cacheTtl),
            fn () => $this->adapter->getRate($base, $quote, $date)
        );

        return $cached;
    }

    /**
     * @param  CurrencyEnum|CurrencyCode|CryptoCurrency|string|array<int, CurrencyEnum|CurrencyCode|CryptoCurrency|string>  $quote
     */
    private function cacheKey(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $base,
        CurrencyEnum|CurrencyCode|CryptoCurrency|string|array $quote,
        DateTimeInterface $date
    ): string {
        $baseKey = $this->stringifyCurrencyLike($base);
        $quoteItems = is_array($quote) ? $quote : [$quote];

        $quoteKeys = array_map(
            fn (CurrencyEnum|CurrencyCode|CryptoCurrency|string $q) => $this->stringifyCurrencyLike($q),
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

    private function stringifyCurrencyLike(
        CurrencyEnum|CurrencyCode|CryptoCurrency|string $currency
    ): string {
        if ($currency instanceof CurrencyCode) {
            return $currency->value();
        }

        if ($currency instanceof CurrencyEnum) {
            return $currency->value;
        }

        if ($currency instanceof CryptoCurrency) {
            return (string) $currency;
        }

        return mb_strtoupper(mb_trim((string) $currency));
    }

    private function shouldCache(): bool
    {
        return $this->cacheEnabled && $this->cache !== null;
    }
}
