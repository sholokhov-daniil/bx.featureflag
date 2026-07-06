<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Service;

/**
 * Содержит канонические имена полей админской формы фича-флагов.
 *
 * Используется сервисом и mapper-классами, чтобы не размазывать строковые
 * литералы по коду и сохранять единый контракт ошибок API.
 */
final class AdminFeatureFlagField
{
    public const string CODE = 'code';
    public const string NAME = 'name';
    public const string DESCRIPTION = 'description';
    public const string ENABLED = 'enabled';
    public const string AVAILABLE_IN_JS = 'availableInJs';
    public const string TAG_ID = 'tagId';
    public const string STRATEGIES = 'strategies';
    public const string ID = 'id';

    /**
     * Закрывает создание utility-класса со статическими константами.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
