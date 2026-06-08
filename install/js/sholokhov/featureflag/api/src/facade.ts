import {Provider} from "../@types/provider.ts";
import {Feature} from "../@types/feature.ts";

export class Facade {
    #provider: Provider;

    constructor(provider: Provider) {
        this.#provider = provider;
    }

    /**
     * Фича активна
     *
     * @param {string} code
     */
    async isEnabled(code: string): Promise<boolean> {
        try {
            const flag = await this.get(code);
            return flag.enabled;
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
    async get(code: string): Promise<Feature> {
        const { data } = await this.#provider.send('get', { code });
        // TODO: Добавить кеширование
        return data;
    }
}