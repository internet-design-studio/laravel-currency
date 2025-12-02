<?php

declare(strict_types=1);

namespace SvkDigital\Currency\DTO;

/**
 * @phpstan-type EnumItems list<EnumValuteItemDTO>
 */
final class EnumValutesDTO
{
    /**
     * @param  EnumItems  $items
     */
    public function __construct(
        public readonly array $items
    ) {}
}
