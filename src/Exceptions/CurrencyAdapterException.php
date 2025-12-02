<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Exceptions;

use RuntimeException;
use Throwable;

final class CurrencyAdapterException extends RuntimeException
{
    public static function requestFailed(string $adapter, string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('[%s] Request failed: %s', $adapter, $message),
            0,
            $previous
        );
    }

    public static function connectionFailed(string $adapter, string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('[%s] Connection failed: %s', $adapter, $message),
            0,
            $previous
        );
    }

    public static function invalidResponse(string $adapter, string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('[%s] Invalid response: %s', $adapter, $message),
            0,
            $previous
        );
    }
}
