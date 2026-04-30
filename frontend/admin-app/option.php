<?php

use Sholokhov\FrontBoot\Config;
use Sholokhov\FrontBoot\Locator\BaseFrameworkLocator;

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Инициализируем языковые файлы
Loc::loadMessages(__FILE__);

// Вспомогательный класс, для поиска всех js и css файлов (если компилируется в dist)
$locator = new BaseFrameworkLocator(__DIR__);

// Объект конфигурации
$extension = new Config;

// Полный путь до всех css файлов расширения
$extension->css = $locator->getCss('assets');

// Польный путь до всех js файлов расширения
$extension->js = $locator->getJs('assets');

// Полный путь до языкового файла расширения
$extension->lang = $locator->getLang();

// Пропустить инициализацию core.js
$extension->skipCore = false;

// Инициализировать после регистрации
$extension->autoload = false;

// Связанные расширения, которые должны инициализироваться до инициализации текущего расширения
$extension->rel = [
    'frontboot.core'
];

// Возвращаем объект конфигурации, для его регистрации
return $extension;