import { Feature } from "../@types/feature.ts";
import {StoreItem, Store as StoreInterface, StoreOptions} from "../@types/store.ts";

/**
 * Хранилище фича-флагов.
 *
 * Выполняет кеширование данных в памяти браузера,
 * контролирует время жизни записей и предотвращает
 * дублирование параллельных запросов к серверу.
 *
 * @author Daniil S.
 */
export class Store implements StoreInterface {
    /**
     * Время жизни записи в кеше (мс)
     */
    #ttl: number;

    /**
     * Кеш загруженных фича-флагов
     */
    #cache: Map<string, StoreItem> = new Map();

    /**
     * Активные запросы к серверу
     */
    #pending: Map<string, Promise<Feature>> = new Map();

    /**
     * @param {StoreOptions} options Параметры хранилища.
     */
    constructor(options: StoreOptions = {}) {
        this.#ttl = options.ttl ?? 60_000;
    }

    /**
     * Возвращает фича-флаг из кеша.
     *
     * Если срок жизни записи истек, запись будет удалена
     * и будет возвращено null.
     *
     * @param {string} code Код фича-флага.
     * @returns {Feature|null}
     */
    get(code: string): Feature | null {
        const item = this.#cache.get(code);

        if (!item) {
            return null;
        }

        if (item.expiredAt <= Date.now()) {
            this.#cache.delete(code);
            return null;
        }

        return item.feature;
    }

    /**
     * Проверка наличия кеша
     *
     * @param {string} code
     */
    has(code: string): boolean {
        const item = this.#cache.get(code);

        if (!item) {
            return false;
        }

        if (item.expiredAt <= Date.now()) {
            this.#cache.delete(code);
            return false;
        }

        return true;
    }

    /**
     * Сохраняет фича-флаг в кеш.
     *
     * @param {string} code Код фича-флага.
     * @param {Feature} feature Данные фича-флага.
     */
    set(code: string, feature: Feature): void {
        this.#cache.set(code, {
            feature,
            expiredAt: Date.now() + this.#ttl,
        });
    }

    /**
     * Возвращает активный запрос для указанного флага.
     *
     * @param {string} code Код фича-флага.
     * @returns {Promise<Feature>|null}
     */
    getPending(code: string): Promise<Feature> | null {
        return this.#pending.get(code) ?? null;
    }

    /**
     * Регистрирует активный запрос для указанного флага.
     *
     * @param {string} code Код фича-флага.
     * @param {Promise<Feature>} promise Promise загрузки данных.
     */
    setPending(code: string, promise: Promise<Feature>): void {
        this.#pending.set(code, promise);
    }

    /**
     * Удаляет активный запрос.
     *
     * @param {string} code Код фича-флага.
     */
    deletePending(code: string): void {
        this.#pending.delete(code);
    }

    /**
     * Удаляет флаг из кеша и списка активных запросов.
     *
     * @param {string} code Код фича-флага.
     */
    invalidate(code: string): void {
        this.#cache.delete(code);
        this.#pending.delete(code);
    }

    /**
     * Полностью очищает хранилище.
     */
    clear(): void {
        this.#cache.clear();
        this.#pending.clear();
    }
}