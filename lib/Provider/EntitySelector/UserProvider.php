<?php

namespace Sholokhov\Featureflag\Provider\EntitySelector;

use Bitrix\Main\UserTable;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

/**
 * Простой provider пользователей без интеграций Bitrix24.
 */
class UserProvider extends BaseProvider
{
    public const string ENTITY_ID = 'sholokhov.featureflag.user';
    private const int DEFAULT_LIMIT = 50;
    private const int MAX_LIMIT = 100;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct();
        $this->prepareOptions($options);
    }

    /**
     * Проверяет, можно ли использовать provider.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // TODO: Добавить проверку, чтобы у пользователя был доступ к модулю
        return isset($GLOBALS['USER'])
            && $GLOBALS['USER'] instanceof \CUser
            && $GLOBALS['USER']->isAuthorized();
    }

    /**
     * Возвращает элементы по ID.
     *
     * @param array<int, int|string> $ids
     * @return Item[]
     */
    public function getItems(array $ids): array
    {
        $userIds = $this->prepareUserIds($ids);
        if ($userIds === []) {
            return [];
        }

        return $this->makeItems($this->getUsers([
            'ids' => $userIds,
            'activeUsers' => null,
            'limit' => null,
        ]));
    }

    /**
     * Возвращает выбранные элементы по ID.
     *
     * @param array<int, int|string> $ids
     * @return Item[]
     */
    public function getSelectedItems(array $ids): array
    {
        return $this->getItems($ids);
    }

    /**
     * Заполняет диалог начальными пользователями.
     *
     * @param Dialog $dialog
     * @return void
     */
    public function fillDialog(Dialog $dialog): void
    {
        if (!$this->getOption('fillDialog', true)) {
            return;
        }

        $dialog->loadPreselectedItems();
        foreach ($dialog->getItemCollection() as $item) {
            $dialog->addRecentItem($item);
        }

        $users = $this->getUsers([
            'activeUsers' => true,
            'limit' => $this->getOption('limit', self::DEFAULT_LIMIT),
        ]);

        $dialog->addRecentItems($this->makeItems($users));
    }

    /**
     * Выполняет поиск пользователей.
     *
     * @param SearchQuery $searchQuery
     * @param Dialog $dialog
     * @return void
     */
    public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
    {
        $query = trim($searchQuery->getQuery());
        if ($query === '') {
            return;
        }

        $limit = $this->getOption('searchLimit', self::MAX_LIMIT);
        $users = $this->getUsers([
            'activeUsers' => true,
            'search' => $query,
            'limit' => $limit,
        ]);

        if (count($users) >= $limit) {
            $searchQuery->setCacheable(false);
        }

        $dialog->addItems($this->makeItems($users));
    }

    /**
     * Возвращает пользователей по фильтру.
     *
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    private function getUsers(array $options = []): array
    {
        $filter = $this->getUserFilter($options);

        $parameters = [
            'select' => [
                'ID',
                'ACTIVE',
                'NAME',
                'LAST_NAME',
                'SECOND_NAME',
                'LOGIN',
                'EMAIL',
                'PERSONAL_PHOTO',
                'WORK_POSITION',
            ],
            'filter' => $filter,
            'order' => [
                'LAST_NAME' => 'ASC',
                'NAME' => 'ASC',
                'ID' => 'ASC',
            ],
        ];

        $limit = $options['limit'] ?? $this->getOption('limit', self::DEFAULT_LIMIT);
        if (is_int($limit) && $limit > 0) {
            $parameters['limit'] = min($limit, self::MAX_LIMIT);
        }

        $users = UserTable::getList($parameters)->fetchAll();
        if (!is_array($users)) {
            return [];
        }

        $ids = $this->prepareUserIds($options['ids'] ?? []);
        if (count($ids) > 1) {
            $sort = array_flip($ids);
            usort($users, static fn(array $first, array $second): int => (
                ($sort[(int)$first['ID']] ?? PHP_INT_MAX) <=> ($sort[(int)$second['ID']] ?? PHP_INT_MAX)
            ));
        }

        return $users;
    }

    /**
     * Формирует фильтр пользователей.
     *
     * @param array<string, mixed> $options
     * @return array<int|string, mixed>
     */
    private function getUserFilter(array $options): array
    {
        $filter = [];

        $activeUsers = array_key_exists('activeUsers', $options)
            ? $options['activeUsers']
            : $this->getOption('activeUsers', true);

        if (is_bool($activeUsers)) {
            $filter['=ACTIVE'] = $activeUsers ? 'Y' : 'N';
        }

        $ids = $this->prepareUserIds($options['ids'] ?? []);
        if ($ids !== []) {
            $filter['=ID'] = $ids;
        }

        if ($this->getOption('onlyWithEmail', false)) {
            $filter['!EMAIL'] = false;
        }

        $search = trim((string)($options['search'] ?? ''));
        if ($search !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                '%NAME' => $search,
                '%LAST_NAME' => $search,
                '%SECOND_NAME' => $search,
                '%LOGIN' => $search,
                '%EMAIL' => $search,
            ];
        }

        return $filter;
    }

    /**
     * Создаёт элементы selector'а из ORM-строк пользователей.
     *
     * @param array<int, array<string, mixed>> $users
     * @return Item[]
     */
    private function makeItems(array $users): array
    {
        return array_map(fn(array $user): Item => $this->makeItem($user), $users);
    }

    /**
     * Создаёт элемент selector'а из ORM-строки пользователя.
     *
     * @param array<string, mixed> $user
     * @return Item
     */
    private function makeItem(array $user): Item
    {
        $id = (int)($user['ID'] ?? 0);
        $title = $this->formatUserName($user);
        $email = trim((string)($user['EMAIL'] ?? ''));
        $position = trim((string)($user['WORK_POSITION'] ?? ''));

        return new Item([
            'id' => $id,
            'entityId' => static::ENTITY_ID,
            'entityType' => ((string)($user['ACTIVE'] ?? 'N')) === 'Y' ? 'user' : 'inactive',
            'title' => $title,
            'subtitle' => $position !== '' ? $position : $email,
            'avatar' => $this->makeUserAvatar((int)($user['PERSONAL_PHOTO'] ?? 0)),
            'tabs' => static::ENTITY_ID,
            'customData' => [
                'id' => $id,
                'name' => (string)($user['NAME'] ?? ''),
                'lastName' => (string)($user['LAST_NAME'] ?? ''),
                'secondName' => (string)($user['SECOND_NAME'] ?? ''),
                'login' => (string)($user['LOGIN'] ?? ''),
                'email' => $email,
                'position' => $position,
            ],
        ]);
    }

    /**
     * Форматирует имя пользователя.
     *
     * @param array<string, mixed> $user
     * @return string
     */
    private function formatUserName(array $user): string
    {
        $title = \CUser::FormatName(
            \CSite::GetNameFormat(false),
            [
                'NAME' => (string)($user['NAME'] ?? ''),
                'LAST_NAME' => (string)($user['LAST_NAME'] ?? ''),
                'SECOND_NAME' => (string)($user['SECOND_NAME'] ?? ''),
                'LOGIN' => (string)($user['LOGIN'] ?? ''),
                'EMAIL' => (string)($user['EMAIL'] ?? ''),
            ],
            true,
            false,
        );

        return $title !== '' ? $title : sprintf('Пользователь #%d', (int)($user['ID'] ?? 0));
    }

    /**
     * Создаёт путь к аватару пользователя.
     *
     * @param int $fileId ID файла аватара.
     * @return string|null
     */
    private function makeUserAvatar(int $fileId): ?string
    {
        if ($fileId <= 0) {
            return null;
        }

        $avatar = \CFile::ResizeImageGet(
            $fileId,
            ['width' => 100, 'height' => 100],
            BX_RESIZE_IMAGE_EXACT,
            false,
        );

        return is_array($avatar) && !empty($avatar['src']) ? (string)$avatar['src'] : null;
    }

    /**
     * Нормализует список ID пользователей.
     *
     * @param mixed $items
     * @return int[]
     */
    private function prepareUserIds(mixed $items): array
    {
        $items = is_array($items) ? $items : [$items];
        $ids = [];

        foreach ($items as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Подготавливает options provider'а.
     *
     * @param array<string, mixed> $options
     * @return void
     */
    private function prepareOptions(array $options): void
    {
        $this->options = [
            'activeUsers' => true,
            'fillDialog' => true,
            'limit' => self::DEFAULT_LIMIT,
            'onlyWithEmail' => false,
            'searchLimit' => self::MAX_LIMIT,
        ];

        foreach (['activeUsers', 'fillDialog', 'onlyWithEmail'] as $option) {
            if (isset($options[$option]) && is_bool($options[$option])) {
                $this->options[$option] = $options[$option];
            }
        }

        foreach (['limit', 'searchLimit'] as $option) {
            if (isset($options[$option]) && is_int($options[$option]) && $options[$option] > 0) {
                $this->options[$option] = min($options[$option], self::MAX_LIMIT);
            }
        }
    }
}
