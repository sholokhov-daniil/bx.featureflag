<?php

namespace Sholokhov\Featureflag\Field;

use Bitrix\Main\Error;

/**
 * Базовый каркас описания свойства стратегии
 */
class AbstractField implements FieldInterface
{
    use NormalizerAwareTrait,
        DenormalizerAwareTrait,
        ValidatorAwareTrait;

    /**
     * Данные свойства
     *
     * @var array
     */
    protected array $container = [];

    /**
     * Сообщение об ошибке для обязательного поля
     *
     * @var string
     */
    protected string $requiredMessage = '';

    /**
     * Тип данных свойства
     *
     * @var FieldType
     */
    protected FieldType $type = FieldType::Text;

    public function __construct(string $code)
    {
        $this->container['code'] = $code;
        $this->container['type'] = $this->type->value;
        $this->configuration();
    }

    /**
     * Преобразование свойства в справочник
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->container;
    }

    /**
     * Возвращает символьный код свойства
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->container['code'];
    }

    /**
     * Возвращает тип данных свойства
     *
     * @return FieldType
     */
    public function getType(): FieldType
    {
        return $this->type;
    }

    /**
     * Возвращает название свойства
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->container['label'] ?? $this->container['name'] ?? '';
    }

    /**
     * Поле является обязательным
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->container['required'] ?? false;
    }

    /**
     * Значение является множественным
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->container['multiple'] ?? false;
    }

    /**
     * Указывает название свойства
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->container['label'] = $name;
        $this->container['name'] = $name;
        return $this;
    }

    /**
     * Поле является обязательным
     *
     * @param bool $required
     * @param string $message
     * @return $this
     */
    public function setRequired(bool $required = true, string $message = ''): self
    {
        $this->container['required'] = $required;
        $this->requiredMessage = $message;

        return $this;
    }

    /**
     * Возвращает флаг: множественное значение или нет
     *
     * @param bool $multiple
     * @return $this
     */
    public function setMultiple(bool $multiple): self
    {
        $this->container['multiple'] = $multiple;
        return $this;
    }

    /**
     * Значение считается пустым для required-проверки
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmptyValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }

    /**
     * Создаёт ошибку свойства с привязкой к блоку стратегий
     *
     * @param string $message Текст ошибки
     * @return Error
     */
    protected function createError(string $message): Error
    {
        return new Error($message, 'strategies.' . $this->getCode() . '.required', [
            'field' => 'strategies',
        ]);
    }

    /**
     * Конфигурация свойства
     *
     * @return void
     */
    protected function configuration(): void
    {
    }
}
