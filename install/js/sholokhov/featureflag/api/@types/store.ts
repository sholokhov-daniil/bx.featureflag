import { Feature } from './feature.ts';

export type StoreItem = {
    feature: Feature;
    expiredAt: number;
}

export type StoreOptions = {
    ttl?: number
}

export interface Store {
    /**
     * Возвращает фича-флаг из кеша
     *
     * @param {string} code
     */
    get(code: string): Feature | null;

    /**
     * Сохраняет фича-флаг в кеш.
     *
     * @param {string} code Код фича-флага.
     * @param {Feature} feature Данные фича-флага.
     */
    set(code: string, feature: Feature): void;

    /**
     * Возвращает активный запрос для указанного флага.
     *
     * @param {string} code Код фича-флага.
     * @returns {Promise<Feature>|null}
     */
    getPending(code: string): Promise<Feature> | null;

    /**
     * Регистрирует активный запрос для указанного флага.
     *
     * @param {string} code Код фича-флага.
     * @param {Promise<Feature>} promise Promise загрузки данных.
     */
    setPending(code: string, promise: Promise<Feature>): void;

    /**
     * Удаляет активный запрос.
     *
     * @param {string} code Код фича-флага.
     */
    deletePending(code: string): void;

    /**
     * Удаляет флаг из кеша и списка активных запросов.
     *
     * @param {string} code Код фича-флага.
     */
    invalidate(code: string): void;

    /**
     * Полностью очищает хранилище.
     */
    clear(): void;
}