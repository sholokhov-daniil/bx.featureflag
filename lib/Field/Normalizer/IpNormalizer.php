<?php

namespace Sholokhov\Featureflag\Field\Normalizer;

/**
 * Нормализация IP-адресов к каноническому виду.
 */
final class IpNormalizer
{
    /**
     * Нормализует одиночный IP-адрес.
     *
     * Если значение не является IP-адресом, возвращает очищенную исходную
     * строку, чтобы последующая валидация могла показать её в ошибке.
     *
     * @param mixed $value
     * @return string
     */
    public static function address(mixed $value): string
    {
        $ip = trim((string)$value);
        return self::canonical($ip) ?? $ip;
    }

    /**
     * Нормализует список IP-адресов.
     *
     * @param mixed $value
     * @return string[]
     */
    public static function addressList(mixed $value): array
    {
        $result = [];

        foreach (ListNormalizer::strings($value) as $ip) {
            $normalizedIp = self::canonical($ip) ?? $ip;
            $result[$normalizedIp] = $normalizedIp;
        }

        return array_values($result);
    }

    /**
     * Приводит IP-адрес к каноническому виду.
     *
     * @param string $ip IP-адрес
     * @return string|null
     */
    public static function canonical(string $ip): ?string
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
    public static function isSameVersion(string $left, string $right): bool
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
    public static function compare(string $left, string $right): int
    {
        return strcmp((string)inet_pton($left), (string)inet_pton($right));
    }

    /**
     * Закрывает создание utility-класса.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
