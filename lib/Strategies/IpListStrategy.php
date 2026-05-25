<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Context;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\IpNormalizer;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
use Sholokhov\Featureflag\Field\TextareaField;
use Sholokhov\Featureflag\Field\Validator\IpAddressListValidator;

/**
 * Стратегия доступа по списку IP-адресов.
 */
final class IpListStrategy extends AbstractStrategy
{
    /**
     * Возвращает код стратегии.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'ip_list';
    }

    /**
     * Возвращает название стратегии для UI.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Определённые IP';
    }

    /**
     * Возвращает описание стратегии для UI.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Включает флаг только для перечисленных IP-адресов.';
    }

    /**
     * Возвращает схему полей формы.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return [
            (new TextareaField('ips'))
                ->setName('IP-адреса')
                ->setPlaceholder('127.0.0.1, 10.0.0.5')
                ->setRequired(true, 'Укажите хотя бы один IP-адрес.')
                ->setNormalizer(static fn(mixed $value): array => IpNormalizer::addressList($value))
                ->setDenormalizer(static fn(mixed $value): string => ListNormalizer::denormalize($value))
                ->addValidator(new IpAddressListValidator())
                ->setRegexMask('[^0-9A-Fa-f:.,;\s]')
        ];
    }

    /**
     * Проверяет текущий IP пользователя по сохранённому списку.
     *
     * @param string $featureCode Код фича-флага
     * @param array<string, mixed> $config Конфигурация стратегии
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool
    {
        $currentIp = IpNormalizer::canonical($this->getCurrentIp());
        if ($currentIp === null) {
            return false;
        }

        $ips = IpNormalizer::addressList($config['ips'] ?? []);

        return in_array($currentIp, $ips, true);
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
