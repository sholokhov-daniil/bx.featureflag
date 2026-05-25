<?php

namespace Sholokhov\Featureflag\Field;

use Closure;

/**
 * Базовый каркас описания свойства стратегии
 */
class AbstractField implements FieldInterface
{
    /**
     * Символьный код свойства
     *
     * @var string
     */
    protected readonly string $code;

    /**
     * Название свойства
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Поле является обязательным
     *
     * @var bool
     */
    protected bool $required = false;

    /**
     * Тип данных свойства
     *
     * @var FieldType
     */
    protected FieldType $type = FieldType::Text;

    /**
     * Нормализатор данных
     *
     * @var Closure|null
     */
    protected ?Closure $normalizer = null;

    /**
     * Денормализатор данных
     *
     * @var Closure|null
     */
    protected ?Closure $denormalizer = null;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Возвращает символьный код свойства
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
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
        return $this->name;
    }

    /**
     * Указывает название свойства
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Поле является обязательным
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Поле является обязательным
     *
     * @param bool $required
     * @return $this
     */
    public function setRequired(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Преобразование свойства в справочник
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'label' => $this->getName(),
            'type' => $this->getType()->value,
            'required' => $this->isRequired(),
        ];
    }

    /**
     * Указание нормализатора данных - перед сохранением данных
     *
     * @param Closure $normalizer
     * @return self
     */
    public function setNormalizer(Closure $normalizer): self
    {
        $this->normalizer = $normalizer;
        return $this;
    }

    /**
     * Указание денормализатор данных - как прочитать данные
     *
     * @param Closure $denormalizer
     * @return self
     */
    public function setDenormalizer(Closure $denormalizer): self
    {
        $this->denormalizer = $denormalizer;
        return $this;
    }
}