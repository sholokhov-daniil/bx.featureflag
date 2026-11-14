<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Infrastructure\Logger;

class LegacyLogger
{
    /**
     * Запись в лог
     *
     * @param mixed $message
     *
     * @return void
     */
    public static function write(mixed $message): void
    {
        AddMessage2Log($message, 'sholokhov.featureflag');
    }
}