import {Provider} from "../@types/provider.ts";
import {Feature} from "../@types/feature.ts";
import {Store} from "../@types/store.ts";

export class Facade {
    #provider: Provider;
    #store: Store;

    constructor(provider: Provider, store: Store) {
        this.#provider = provider;
        this.#store = store;
    }

    /**
     * Фича активна
     *
     * @param {string} code
     */
    async isEnabled(code: string): Promise<boolean> {
        try {
            const flag = await this.get(code);
            return flag?.enabled;
        } catch (e) {
            console.error(e);
            return false;
        }
    }

    /**
     * Фича не активна
     *
     * @param {string} code
     */
    async isDisabled(code: string): Promise<boolean> {
        try {
            return !await this.isEnabled(code);
        } catch (e) {
            console.error(e);
            return true;
        }
    }

    /**
     * Вызов callback в зависимости от статуса фичи
     *
     * @param {string} code
     * @param {Function} enabled
     * @param {Function} disabled
     */
    async when(code: string, enabled: Function, disabled: Function): Promise<boolean>
    {
        const active = await this.isEnabled(code);
        active ? enabled() : disabled();

        return active;
    }

    /**
     * Возвращает информацию по фиче
     *
     * @param {string} code
     */
    async get(code: string): Promise<Feature|null> {
        const cached = this.#store.get(code);

        if (cached) {
            return cached;
        }

        const pending = this.#store.getPending(code);

        if (pending) {
            return pending;
        }

        const request = this.#load(code);

        this.#store.setPending(code, request);

        try {
            return await request;
        } finally {
            this.#store.deletePending(code);
        }
    }

    /**
     * Принудительно обновляет фичу
     *
     * @param {string} code
     */
    async refresh(code: string): Promise<Feature> {
        this.#store.invalidate(code);

        return this.get(code);
    }

    /**
     * Удаляет фичу из кеша
     *
     * @param {string} code
     */
    invalidate(code: string): void {
        this.#store.invalidate(code);
    }

    /**
     * Полностью очищает кеш
     */
    clear(): void {
        this.#store.clear();
    }

    /**
     * Запрос на получение фичи по API
     *
     * @param {string} code
     * @private
     */
    async #load(code: string): Promise<Feature|null> {
        let { data } = await this.#provider.send('get', { code });

        if (typeof data !== 'object' || Object.keys(data).length === 0) {
            data = null;
        }

        this.#store.set(code, data);

        return data;
    }
}