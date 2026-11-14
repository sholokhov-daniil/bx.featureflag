<?php

namespace Sholokhov\Featureflag\Decoder;

/**
 * Декодирует JSON-конфигурацию стратегий
 */
class StrategyDecoder
{
    /**
     * @param string $data
     *
     * @return array
     */
    public function decode(string $data): array
    {
        $value = trim($data);
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = trim((string)($item['type'] ?? ''));
            $config = $item['config'] ?? [];

            if ($type === '' || !is_array($config)) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'config' => $config,
            ];
        }

        return $result;
    }
}