<?php

namespace Sholokhov\Featureflag\Field;

class TextareaField extends TextField
{
    /**
     * Тип данных свойства
     *
     * @var FieldType
     */
    protected FieldType $type = FieldType::Textarea;
}