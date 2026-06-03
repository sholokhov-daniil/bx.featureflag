<?php

namespace Sholokhov\Featureflag\Provider\EntitySelector;

use Bitrix\Main\GroupTable;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

/**
 * Простой provider групп пользователей без интеграций Bitrix24.
 */
class UserGroupProvider extends BaseProvider
{
    public const string ENTITY_ID = 'sholokhov.featureflag.user.group';
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
        $groupIds = $this->prepareGroupIds($ids);
        if ($groupIds === []) {
            return [];
        }

        return $this->makeItems($this->getGroups([
            'ids' => $groupIds,
            'activeGroups' => null,
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
     * Заполняет диалог начальными группами.
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

        $groups = $this->getGroups([
            'activeGroups' => true,
            'limit' => $this->getOption('limit', self::DEFAULT_LIMIT),
        ]);

        $dialog->addRecentItems($this->makeItems($groups));
    }

    /**
     * Выполняет поиск групп пользователей.
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
        $groups = $this->getGroups([
            'activeGroups' => true,
            'search' => $query,
            'limit' => $limit,
        ]);

        if (count($groups) >= $limit) {
            $searchQuery->setCacheable(false);
        }

        $dialog->addItems($this->makeItems($groups));
    }

    /**
     * Возвращает группы по фильтру.
     *
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    private function getGroups(array $options = []): array
    {
        $parameters = [
            'select' => [
                'ID',
                'ACTIVE',
                'C_SORT',
                'IS_SYSTEM',
                'ANONYMOUS',
                'NAME',
                'DESCRIPTION',
                'STRING_ID',
            ],
            'filter' => $this->getGroupFilter($options),
            'order' => [
                'C_SORT' => 'ASC',
                'NAME' => 'ASC',
                'ID' => 'ASC',
            ],
        ];

        $limit = $options['limit'] ?? $this->getOption('limit', self::DEFAULT_LIMIT);
        if (is_int($limit) && $limit > 0) {
            $parameters['limit'] = min($limit, self::MAX_LIMIT);
        }

        $groups = GroupTable::getList($parameters)->fetchAll();
        if (!is_array($groups)) {
            return [];
        }

        $ids = $this->prepareGroupIds($options['ids'] ?? []);
        if (count($ids) > 1) {
            $sort = array_flip($ids);
            usort($groups, static fn(array $first, array $second): int => (
                ($sort[(int)$first['ID']] ?? PHP_INT_MAX) <=> ($sort[(int)$second['ID']] ?? PHP_INT_MAX)
            ));
        }

        return $groups;
    }

    /**
     * Формирует фильтр групп.
     *
     * @param array<string, mixed> $options
     * @return array<int|string, mixed>
     */
    private function getGroupFilter(array $options): array
    {
        $filter = [];

        $activeGroups = array_key_exists('activeGroups', $options)
            ? $options['activeGroups']
            : $this->getOption('activeGroups', true);

        if (is_bool($activeGroups)) {
            $filter['=ACTIVE'] = $activeGroups ? 'Y' : 'N';
        }

        if ($this->getOption('hideAnonymous', true)) {
            $filter['=ANONYMOUS'] = 'N';
        }

        $ids = $this->prepareGroupIds($options['ids'] ?? []);
        if ($ids !== []) {
            $filter['=ID'] = $ids;
        }

        $search = trim((string)($options['search'] ?? ''));
        if ($search !== '') {
            $filter[] = [
                'LOGIC' => 'OR',
                '%NAME' => $search,
                '%DESCRIPTION' => $search,
                '%STRING_ID' => $search,
            ];
        }

        return $filter;
    }

    /**
     * Создаёт элементы selector'а из ORM-строк групп.
     *
     * @param array<int, array<string, mixed>> $groups
     * @return Item[]
     */
    private function makeItems(array $groups): array
    {
        return array_map(fn(array $group): Item => $this->makeItem($group), $groups);
    }

    /**
     * Создаёт элемент selector'а из ORM-строки группы.
     *
     * @param array<string, mixed> $group
     * @return Item
     */
    private function makeItem(array $group): Item
    {
        $id = (int)($group['ID'] ?? 0);
        $name = trim((string)($group['NAME'] ?? ''));
        $stringId = trim((string)($group['STRING_ID'] ?? ''));
        $description = trim((string)($group['DESCRIPTION'] ?? ''));
        $isActive = ((string)($group['ACTIVE'] ?? 'N')) === 'Y';
        $isSystem = ((string)($group['IS_SYSTEM'] ?? 'N')) === 'Y';

        return new Item([
            'id' => $id,
            'entityId' => static::ENTITY_ID,
            'entityType' => $isActive ? 'group' : 'inactive',
            'title' => $name !== '' ? $name : sprintf('Группа #%d', $id),
            'subtitle' => $stringId !== '' ? $stringId : $description,
            'tabs' => static::ENTITY_ID,
            'customData' => [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'stringId' => $stringId,
                'sort' => (int)($group['C_SORT'] ?? 0),
                'active' => $isActive,
                'system' => $isSystem,
            ],
        ]);
    }

    /**
     * Нормализует список ID групп.
     *
     * @param mixed $items
     * @return int[]
     */
    private function prepareGroupIds(mixed $items): array
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
            'activeGroups' => true,
            'fillDialog' => true,
            'hideAnonymous' => true,
            'limit' => self::DEFAULT_LIMIT,
            'searchLimit' => self::MAX_LIMIT,
        ];

        foreach (['activeGroups', 'fillDialog', 'hideAnonymous'] as $option) {
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
