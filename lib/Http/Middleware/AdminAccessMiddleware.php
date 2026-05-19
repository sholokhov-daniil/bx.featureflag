<?php

namespace Sholokhov\Featureflag\Http\Middleware;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Middleware-прослойка (Bitrix prefilter) для проверки прав администратора.
 */
final class AdminAccessMiddleware extends Base
{
    private const ERROR_ACCESS_DENIED = 'access_denied';

    /**
     * @param Event $event
     * @return EventResult|null
     */
    public function onBeforeAction(Event $event): ?EventResult
    {
        global $USER;

        if ($USER instanceof \CUser && $USER->isAdmin()) {
            return null;
        }

        Context::getCurrent()->getResponse()->setStatus(403);
        $this->addError(new Error(
            (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ACCESS_DENIED'),
            self::ERROR_ACCESS_DENIED,
        ));

        return new EventResult(EventResult::ERROR, null, null, $this);
    }
}
