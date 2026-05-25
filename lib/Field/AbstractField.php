<?php

namespace Sholokhov\Featureflag\Field;

use Closure;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\Validator\FieldValidatorInterface;

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

    /**
     * Валидаторы значения свойства
     *
     * @var FieldValidatorInterface[]
     */
    protected array $validators = [];

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
    public function setRequired(bool $required = true, string $message = ''): self
    {
        $this->required = $required;
        $this->requiredMessage = $message;
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
     * Нормализация значения перед валидацией и сохранением
     *
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue(mixed $value): mixed
    {
        if ($this->normalizer === null) {
            return $value;
        }

        return ($this->normalizer)($value, $this);
    }

    /**
     * Денормализация значения перед отдачей в UI
     *
     * @param mixed $value
     * @return mixed
     */
    public function denormalizeValue(mixed $value): mixed
    {
        if ($this->denormalizer === null) {
            return $value;
        }

        return ($this->denormalizer)($value, $this);
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

    /**
     * Добавление валидатора значения свойства
     *
     * @param FieldValidatorInterface $validator
     * @return self
     */
    public function addValidator(FieldValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Валидация значения на основе конфигурации свойства
     *
     * @param mixed $value
     * @return Result
     */
    public function validateValue(mixed $value): Result
    {
        $result = new Result();

        if ($this->isRequired() && $this->isEmptyValue($value)) {
            $result->addError($this->createError(
                $this->requiredMessage ?: sprintf('Заполните поле "%s".', $this->getName())
            ));
        }

        foreach ($this->validators as $validator) {
            $validateResult = $validator->validate($value, $this);
            if (!$validateResult->isSuccess()) {
                $result->addErrors($validateResult->getErrors());
            }
        }

        return $result;
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
}
