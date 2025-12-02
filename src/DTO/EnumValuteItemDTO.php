<?php

declare(strict_types=1);

namespace SvkDigital\Currency\DTO;

final class EnumValuteItemDTO
{
    public function __construct(
        public readonly string $internalCode,
        public readonly string $name,
        public readonly string $englishName,
        public readonly int $nominal,
        public readonly ?string $commonCode,
        public readonly ?string $isoNumericCode,
        public readonly ?string $isoCharCode
    ) {}
}
