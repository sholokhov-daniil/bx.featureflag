<?php

namespace Sholokhov\Featureflag\Http\Middleware;

use Bitrix\Main\Context;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Sholokhov\Featureflag\Permission\PermissionInterface;

Loc::loadMessages(__FILE__);

/**
 * Middleware-прослойка (Bitrix prefilter) для проверки прав на изменение.
 */
final class WriteAccessMiddleware extends Base
{
    private const ERROR_ACCESS_DENIED = 'access_denied';
    private PermissionInterface $permission;

    public function __construct(PermissionInterface $permission)
    {
        $this->permission = $permission;
        parent::__construct();
    }

    /**
     * @param Event $event
     * @return EventResult|null
     */
    public function onBeforeAction(Event $event): ?EventResult
    {
        Debug::dumpToFile($this->permission->hasFullAccess());
        if ($this->permission->hasFullAccess()) {
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
