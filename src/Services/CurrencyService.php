<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Services;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Contracts\CurrencyServiceContract;
use SvkDigital\Currency\ValueObjects\CryptoCurrency;
use SvkDigital\Currency\ValueObjects\CurrencyRate;
use SvkDigital\Currency\ValueObjects\FiatCurrency;

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

    /**
     * @param  array<int, FiatCurrency|CryptoCurrency|string>  $quote
     * @return CurrencyRate|array<int, CurrencyRate>
     */
    public function getRate(
        FiatCurrency|CryptoCurrency $base,
        FiatCurrency|CryptoCurrency|array $quote,
        DateTimeInterface $date
    ): CurrencyRate|array {
        $rateService = $this->rate()
            ->base($this->normalizeCurrencyLike($base))
            ->date($date);

        if (is_array($quote)) {
            $rateService->quotes(
                array_map(
                    fn (FiatCurrency|CryptoCurrency|string $q) => $this->normalizeCurrencyLike($q),
                    $quote
                )
            );
        } else {
            $rateService->quote($this->normalizeCurrencyLike($quote));
        }

        return $rateService->get();
    }

    public function rate(): CurrencyRatesService
    {
        return new CurrencyRatesService(
            $this->adapter,
            $this->cache,
            $this->cacheEnabled,
            $this->cacheTtl,
            $this->cachePrefix
        );
    }

    public function convert(): CurrencyConverterService
    {
        return new CurrencyConverterService(
            $this->adapter,
            $this->cache,
            $this->cacheEnabled,
            $this->cacheTtl,
            $this->cachePrefix
        );
    }

    private function normalizeCurrencyLike(
        FiatCurrency|CryptoCurrency|string $currency
    ): FiatCurrency|CryptoCurrency {
        if ($currency instanceof FiatCurrency || $currency instanceof CryptoCurrency) {
            return $currency;
        }

        return new FiatCurrency((string) $currency);
    }
}
