<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Context;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\TextField;

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
            new TextField('from')
                ->setName('IP от')
                ->setPlaceholder('10.0.0.1')
                ->setRequired()
                ->setMask('ipv4'),

            new TextField('to')
                ->setName('IP до')
                ->setPlaceholder('255.255.255.255')
                ->setRequired()
                ->setMask('ipv4'),
        ];
    }

    /**
     * Валидирует и нормализует границы IP-диапазона.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $from = trim((string)($config['from'] ?? ''));
        $to = trim((string)($config['to'] ?? ''));

        $from = $this->normalizeIp($from);
        if ($from === null) {
            return $this->error('Укажите корректный начальный IP-адрес.');
        }

        $to = $this->normalizeIp($to);
        if ($to === null) {
            return $this->error('Укажите корректный конечный IP-адрес.');
        }

        if (!$this->isSameIpVersion($from, $to)) {
            return $this->error('Начальный и конечный IP должны быть одной версии.');
        }

        if ($this->compareIp($from, $to) > 0) {
            return $this->error('Начальный IP не должен быть больше конечного.');
        }

        return $this->success([
            'from' => $from,
            'to' => $to,
        ]);
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
        $currentIp = $this->normalizeIp($this->getCurrentIp());
        $from = $this->normalizeIp(trim((string)($config['from'] ?? '')));
        $to = $this->normalizeIp(trim((string)($config['to'] ?? '')));

        if ($currentIp === null || $from === null || $to === null) {
            return false;
        }

        if (!$this->isSameIpVersion($currentIp, $from) || !$this->isSameIpVersion($currentIp, $to)) {
            return false;
        }

        return $this->compareIp($currentIp, $from) >= 0
            && $this->compareIp($currentIp, $to) <= 0;
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
     * Проверяет, что IP-адреса относятся к одной версии протокола.
     *
     * @param string $left Первый IP-адрес
     * @param string $right Второй IP-адрес
     * @return bool
     */
    private function isSameIpVersion(string $left, string $right): bool
    {
        return strlen((string)inet_pton($left)) === strlen((string)inet_pton($right));
    }

    /**
     * Сравнивает два IP-адреса в бинарном представлении.
     *
     * @param string $left Первый IP-адрес
     * @param string $right Второй IP-адрес
     * @return int
     */
    private function compareIp(string $left, string $right): int
    {
        return strcmp((string)inet_pton($left), (string)inet_pton($right));
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
