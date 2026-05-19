<?php

use Bitrix\Main\Context;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$request = Context::getCurrent()->getRequest();
$lang = rawurlencode((string)($request->get('lang') ?: LANGUAGE_ID));

LocalRedirect('/bitrix/admin/sholokhov_featureflag_list.php?lang=' . $lang);
