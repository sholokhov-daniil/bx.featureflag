<?php

namespace Sholokhov\Featureflag\DTO;

class FlagInfo
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $enabled
    )
    {
    }
}