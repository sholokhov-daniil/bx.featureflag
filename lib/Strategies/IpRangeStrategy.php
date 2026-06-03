<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Context;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\IpNormalizer;
use Sholokhov\Featureflag\Field\TextField;
use Sholokhov\Featureflag\Field\Validator\IpAddressValidator;

/**
 * Стратегия доступа по диапазону IP-адресов.
 */
final class IpRangeStrategy extends AbstractStrategy
{
    /**
     * Возвращает код стратегии.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'ip_range';
    }

    /**
     * Возвращает название стратегии для UI.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Диапазон IP';
    }

    /**
     * Возвращает описание стратегии для UI.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Включает флаг для IP-адресов внутри указанного диапазона.';
    }

    /**
     * Возвращает схему полей формы.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return [
            (new TextField('from'))
                ->setName('IP от')
                ->setPlaceholder('10.0.0.1')
                ->setRequired(true, 'Укажите корректный начальный IP-адрес.')
                ->setNormalizer(static fn(mixed $value): string => IpNormalizer::address($value))
                ->setDenormalizer(static fn(mixed $value): string => (string)($value ?? ''))
                ->addValidator(new IpAddressValidator('Укажите корректный начальный IP-адрес.'))
                ->setRegexMask('[^0-9A-Fa-f:.]'),

            (new TextField('to'))
                ->setName('IP до')
                ->setPlaceholder('255.255.255.255')
                ->setRequired(true, 'Укажите корректный конечный IP-адрес.')
                ->setNormalizer(static fn(mixed $value): string => IpNormalizer::address($value))
                ->setDenormalizer(static fn(mixed $value): string => (string)($value ?? ''))
                ->addValidator(new IpAddressValidator('Укажите корректный конечный IP-адрес.'))
                ->setRegexMask('[^0-9A-Fa-f:.]'),
        ];
    }

    /**
     * Проверяет границы уже нормализованного IP-диапазона.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    protected function validateNormalizedConfig(array $config): Result
    {
        $from = (string)($config['from'] ?? '');
        $to = (string)($config['to'] ?? '');

        if (!IpNormalizer::isSameVersion($from, $to)) {
            return $this->error('Начальный и конечный IP должны быть одной версии.');
        }

        if (IpNormalizer::compare($from, $to) > 0) {
            return $this->error('Начальный IP не должен быть больше конечного.');
        }

        return new Result();
    }

    /**
     * Проверяет, входит ли IP текущего запроса в диапазон.
     *
     * @param string $featureCode Код фича-флага
     * @param array<string, mixed> $config Конфигурация стратегии
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool
    {
        $currentIp = IpNormalizer::canonical($this->getCurrentIp());
        $from = IpNormalizer::canonical(trim((string)($config['from'] ?? '')));
        $to = IpNormalizer::canonical(trim((string)($config['to'] ?? '')));

        if ($currentIp === null || $from === null || $to === null) {
            return false;
        }

        if (!IpNormalizer::isSameVersion($currentIp, $from) || !IpNormalizer::isSameVersion($currentIp, $to)) {
            return false;
        }

        return IpNormalizer::compare($currentIp, $from) >= 0
            && IpNormalizer::compare($currentIp, $to) <= 0;
    }

    /**
     * Возвращает IP-адрес текущего запроса.
     *
     * @return string
     */
    private function getCurrentIp(): string
    {
        try {
            $request = Context::getCurrent()->getRequest();
            if (method_exists($request, 'getRemoteAddress')) {
                return trim((string)$request->getRemoteAddress());
            }
        } catch (\Throwable) {
        }

        return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}
