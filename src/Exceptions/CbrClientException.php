<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Exceptions;

use RuntimeException;

final class CbrClientException extends RuntimeException
{
    public static function unableToParse(string $context, ?\Throwable $previous = null): self
    {
        return new self("Unable to parse CBR {$context} response.", 0, $previous);
    }

    public static function soapFault(?string $code, ?string $message): self
    {
        $code = $code ? mb_trim($code) : 'Server';
        $message = $message ? mb_trim($message) : 'Unknown SOAP fault.';

        return new self(sprintf('CBR SOAP fault (%s): %s', $code, $message));
    }

    public static function missingSoapResult(string $action): self
    {
        return new self("Missing SOAP result node for {$action} response.");
    }

    public static function missingDataset(string $action, string $node): self
    {
        return new self("Missing {$node} dataset inside {$action} response.");
    }

    public static function invalidDateTime(string $context, string $value, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Unable to parse %s datetime "%s".', $context, $value), 0, $previous);
    }
}
