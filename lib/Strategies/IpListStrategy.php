<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Context;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\TextareaField;

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
     * @return array<int, array<string, mixed>>
     */
    public function getFields(): array
    {
        return [
            new TextareaField('ips')
                ->setName('IP-адреса')
                ->setPlaceholder('127.0.0.1, 10.0.0.5')
                ->setRequired()
                ->setMask('ipv4_list')
        ];
    }

    /**
     * Валидирует и нормализует список IP-адресов.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $ips = $this->splitValues($config['ips'] ?? []);
        if ($ips === []) {
            return $this->error('Укажите хотя бы один IP-адрес.');
        }

        $normalizedIps = [];
        foreach ($ips as $ip) {
            $normalizedIp = $this->normalizeIp($ip);
            if ($normalizedIp === null) {
                return $this->error("Некорректный IP-адрес: {$ip}");
            }

            $normalizedIps[$normalizedIp] = $normalizedIp;
        }

        return $this->success([
            'ips' => array_values($normalizedIps),
        ]);
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
        $currentIp = $this->normalizeIp($this->getCurrentIp());
        if ($currentIp === null) {
            return false;
        }

        $ips = [];
        foreach ($this->splitValues($config['ips'] ?? []) as $ip) {
            $normalizedIp = $this->normalizeIp($ip);
            if ($normalizedIp !== null) {
                $ips[] = $normalizedIp;
            }
        }

        return in_array($currentIp, $ips, true);
    }

    /**
     * Приводит IP-адрес к каноническому виду.
     *
     * @param string $ip IP-адрес
     * @return string|null
     */
    private function normalizeIp(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $normalized = inet_ntop($packed);
        return $normalized === false ? null : $normalized;
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
