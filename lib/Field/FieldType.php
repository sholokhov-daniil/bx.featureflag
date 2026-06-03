<?php

namespace Sholokhov\Featureflag\Field;

enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case EntitySelector = 'entity-selector';
}