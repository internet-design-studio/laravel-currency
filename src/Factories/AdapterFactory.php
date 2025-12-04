<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Factories;

use Illuminate\Support\Str;
use SvkDigital\Currency\Contracts\CurrencyAdapter;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;

/**
 * Factory for creating currency adapters based on provider configuration.
 */
final class AdapterFactory
{
    /**
     * Base namespace for adapters.
     */
    private const ADAPTER_NAMESPACE = 'SvkDigital\\Currency\\Adapters\\';

    /**
     * Create an adapter instance for the given provider.
     *
     * @param  string  $provider  Provider name (e.g., 'cbr', 'currency_freaks')
     * @param  array<string, mixed>  $config  Provider configuration
     *
     * @throws CurrencyAdapterException
     */
    public static function create(string $provider, array $config): CurrencyAdapter
    {
        $adapterClass = self::resolveAdapterClass($provider);

        if (! class_exists($adapterClass)) {
            throw CurrencyAdapterException::adapterNotFound($provider, $adapterClass);
        }

        if (! is_subclass_of($adapterClass, CurrencyAdapter::class)) {
            throw CurrencyAdapterException::invalidAdapter($provider, $adapterClass);
        }

        if (! method_exists($adapterClass, 'fromConfig')) {
            throw CurrencyAdapterException::missingFromConfigMethod($provider, $adapterClass);
        }

        /** @var callable(array<string, mixed>): CurrencyAdapter $fromConfig */
        $fromConfig = [$adapterClass, 'fromConfig'];

        return $fromConfig($config);
    }

    /**
     * Resolve the adapter class name from provider name.
     *
     * @param  string  $provider  Provider name (e.g., 'cbr', 'currency_freaks')
     * @return class-string<CurrencyAdapter>
     */
    private static function resolveAdapterClass(string $provider): string
    {
        // Convert provider name to namespace segment
        // 'cbr' -> 'Cbr', 'currency_freaks' -> 'CurrencyFreaks'
        $namespaceSegment = self::providerToNamespace($provider);

        // Build adapter class name: {NamespaceSegment}Adapter
        $adapterClassName = $namespaceSegment.'Adapter';

        /** @var class-string<CurrencyAdapter> */
        return self::ADAPTER_NAMESPACE.$namespaceSegment.'\\'.$adapterClassName;
    }

    /**
     * Convert provider name to namespace segment.
     *
     * Examples:
     * - 'cbr' -> 'Cbr'
     * - 'currency_freaks' -> 'CurrencyFreaks'
     * - 'my_provider' -> 'MyProvider'
     *
     * @param  string  $provider  Provider name
     * @return string Namespace segment
     */
    private static function providerToNamespace(string $provider): string
    {
        // Convert snake_case to PascalCase
        $camelized = Str::camel($provider);
        $pascalCase = Str::ucfirst($camelized);

        return $pascalCase;
    }
}
