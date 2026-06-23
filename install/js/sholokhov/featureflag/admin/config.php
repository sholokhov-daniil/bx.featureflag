<?php

$lang = match(LANGUAGE_ID) {
    default => 'ru'
};

return [
    'css' => 'dist/app.css',
    'js' => 'dist/app.js',
    'rel' => ['main.core', 'main.core.ajax', 'calendar', 'date', 'ui.entity-selector'],
    'lang' => "./lang/$lang/config,php",
    'skip_core' => false,
];
