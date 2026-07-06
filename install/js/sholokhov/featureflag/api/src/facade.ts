import {Provider} from "../@types/provider.ts";
import {Feature} from "../@types/feature.ts";
import {Store} from "../@types/store.ts";

type FeatureFlagLogger = Pick<Console, 'error' | 'warn'>;

type FeatureFlagErrorContext = {
    method: string;
    code?: string;
    codes?: string[];
}

type FeatureFlagErrorHandler = (error: unknown, context: FeatureFlagErrorContext) => void;

type FeatureFlagCallback = () => void | Promise<void>;

type FacadeOptions = {
    logger?: FeatureFlagLogger | null;
    onError?: FeatureFlagErrorHandler;
    defaultValueOnError?: boolean;
}

type FeatureEnabledBulk = Record<string, boolean>;

export class Facade {
    #provider: Provider;
    #store: Store;
    #logger: FacadeOptions['logger'];
    #onError: FacadeOptions['onError'];
    #defaultValueOnError: boolean;

    constructor(provider: Provider, store: Store, options: FacadeOptions = {}) {
        this.#provider = provider;
        this.#store = store;
        this.#logger = console;
        this.#defaultValueOnError = false;
        this.configure(options);
    }

    /**
     * Обновляет настройки обработки ошибок.
     *
     * @param {FacadeOptions} options Настройки фасада.
     */
    configure(options: FacadeOptions = {}): void {
        if ('logger' in options) {
            this.#logger = options.logger;
        }

        if ('onError' in options) {
            this.#onError = options.onError;
        }

        if ('defaultValueOnError' in options) {
            this.#defaultValueOnError = options.defaultValueOnError ?? false;
        }
    }

    /**
     * Фича активна
     *
     * @param {string} code
     */
    async isEnabled(code: string): Promise<boolean> {
        try {
            const flag = await this.get(code);
            return flag?.enabled === true;
        } catch (error) {
            this.#handleError(error, { method: 'isEnabled', code });
            return this.#defaultValueOnError;
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
        } catch (error) {
            this.#handleError(error, { method: 'isDisabled', code });
            return !this.#defaultValueOnError;
        }
    }

    /**
     * Вызов callback в зависимости от статуса фичи
     *
     * @param {string} code
     * @param {FeatureFlagCallback} enabled
     * @param {FeatureFlagCallback} disabled
     */
    async when(
        code: string,
        enabled?: FeatureFlagCallback,
        disabled?: FeatureFlagCallback,
    ): Promise<boolean> {
        const active = await this.isEnabled(code);

        if (active) {
            await enabled?.();
        } else {
            await disabled?.();
        }

        return active;
    }

    /**
     * Возвращает информацию по фиче
     *
     * @param {string} code
     */
    async get(code: string): Promise<Feature | null> {
        const normalizedCode = this.#normalizeCode(code);

        if (this.#store.has(normalizedCode)) {
            return this.#store.get(normalizedCode);
        }

        const pending = this.#store.getPending(normalizedCode);

        if (pending) {
            return pending;
        }

        const request = this.#load(normalizedCode);

        this.#store.setPending(normalizedCode, request);

        try {
            return await request;
        } finally {
            this.#store.deletePending(normalizedCode);
        }
    }

    /**
     * Возвращает информацию по списку фич.
     *
     * @param {string[]} codes Коды фича-флагов.
     */
    async getBulk(codes: string[]): Promise<Record<string, Feature | null>> {
        const normalizedCodes = this.#normalizeCodes(codes);
        const result: Record<string, Feature | null> = {};
        const codesToLoad: string[] = [];
        const pendingEntries: Array<[string, Promise<Feature | null>]> = [];

        normalizedCodes.forEach((code) => {
            if (this.#store.has(code)) {
                result[code] = this.#store.get(code);
                return;
            }

            const pending = this.#store.getPending(code);

            if (pending) {
                pendingEntries.push([code, pending]);
                return;
            }

            codesToLoad.push(code);
        });

        if (codesToLoad.length > 0) {
            const loaded = await this.#loadBulk(codesToLoad);
            Object.assign(result, loaded);
        }

        if (pendingEntries.length > 0) {
            const pendingResults = await Promise.all(
                pendingEntries.map(async ([code, pending]): Promise<[string, Feature | null]> => {
                    const feature = await pending;
                    return [code, feature];
                }),
            );

            pendingResults.forEach(([code, feature]) => {
                result[code] = feature;
            });
        }

        return result;
    }

    /**
     * Возвращает статусы активности по списку фич.
     *
     * @param {string[]} codes Коды фича-флагов.
     */
    async isEnabledBulk(codes: string[]): Promise<FeatureEnabledBulk> {
        try {
            const features = await this.getBulk(codes);
            const result: FeatureEnabledBulk = {};

            Object.entries(features).forEach(([code, feature]) => {
                result[code] = feature?.enabled === true;
            });

            return result;
        } catch (error) {
            const normalizedCodes = this.#safeNormalizeCodes(codes);
            this.#handleError(error, { method: 'isEnabledBulk', codes: normalizedCodes });

            return normalizedCodes.reduce<FeatureEnabledBulk>((result, code) => {
                result[code] = this.#defaultValueOnError;
                return result;
            }, {});
        }
    }

    /**
     * Предварительно загружает список фич в кеш.
     *
     * @param {string[]} codes Коды фича-флагов.
     */
    async preloadBulk(codes: string[]): Promise<Record<string, Feature | null>> {
        return this.getBulk(codes);
    }

    /**
     * Принудительно обновляет фичу
     *
     * @param {string} code
     */
    async refresh(code: string): Promise<Feature | null> {
        const normalizedCode = this.#normalizeCode(code);
        this.#store.invalidate(normalizedCode);

        return this.get(normalizedCode);
    }

    /**
     * Удаляет фичу из кеша
     *
     * @param {string} code
     */
    invalidate(code: string): void {
        const normalizedCode = this.#normalizeCode(code);
        this.#store.invalidate(normalizedCode);
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
    async #load(code: string): Promise<Feature | null> {
        const { data } = await this.#provider.send('get', { code });
        const feature = this.#normalizeFeature(data);

        this.#store.set(code, feature);

        return feature;
    }

    /**
     * Запрос на получение списка фич по API.
     *
     * @param {string[]} codes Коды фича-флагов.
     * @private
     */
    async #loadBulk(codes: string[]): Promise<Record<string, Feature | null>> {
        const request = this.#provider.send('getBulk', { codes })
            .then(({ data }) => this.#normalizeFeatureBulk(data, codes));

        codes.forEach((code) => {
            const pending = request.then((features) => features[code] ?? null);
            pending.catch(() => {});
            this.#store.setPending(code, pending);
        });

        try {
            const features = await request;

            codes.forEach((code) => {
                this.#store.set(code, features[code] ?? null);
            });

            return features;
        } finally {
            codes.forEach((code) => this.#store.deletePending(code));
        }
    }

    /**
     * Нормализует код фича-флага.
     *
     * @param {string} code Код фича-флага.
     * @private
     */
    #normalizeCode(code: string): string {
        if (typeof code !== 'string') {
            throw new TypeError('Feature flag code must be a string.');
        }

        const normalizedCode = code.trim();

        if (normalizedCode === '') {
            throw new TypeError('Feature flag code must not be empty.');
        }

        return normalizedCode;
    }

    /**
     * Нормализует список кодов фича-флагов.
     *
     * @param {string[]} codes Коды фича-флагов.
     * @private
     */
    #normalizeCodes(codes: string[]): string[] {
        if (!Array.isArray(codes)) {
            throw new TypeError('Feature flag codes must be an array.');
        }

        const normalizedCodes = codes.map((code) => this.#normalizeCode(code));
        return [...new Set(normalizedCodes)];
    }

    /**
     * Безопасно нормализует список кодов для error fallback.
     *
     * @param {string[]} codes Коды фича-флагов.
     * @private
     */
    #safeNormalizeCodes(codes: string[]): string[] {
        try {
            return this.#normalizeCodes(codes);
        } catch {
            return [];
        }
    }

    /**
     * Нормализует данные фичи из ответа backend.
     *
     * @param {unknown} data Ответ backend.
     * @private
     */
    #normalizeFeature(data: unknown): Feature | null {
        if (data === null || data === undefined) {
            return null;
        }

        if (typeof data !== 'object' || Array.isArray(data)) {
            return null;
        }

        if (!('enabled' in data) || typeof data.enabled !== 'boolean') {
            return null;
        }

        return {
            enabled: data.enabled,
        };
    }

    /**
     * Нормализует bulk-ответ backend.
     *
     * @param {unknown} data Ответ backend.
     * @param {string[]} codes Запрошенные коды фича-флагов.
     * @private
     */
    #normalizeFeatureBulk(data: unknown, codes: string[]): Record<string, Feature | null> {
        const result: Record<string, Feature | null> = {};

        codes.forEach((code) => {
            result[code] = null;
        });

        if (data === null || data === undefined || typeof data !== 'object' || Array.isArray(data)) {
            return result;
        }

        codes.forEach((code) => {
            const featureEntry = Object.entries(data).find(([featureCode]) => featureCode === code);
            result[code] = this.#normalizeFeature(featureEntry?.[1]);
        });

        return result;
    }

    /**
     * Обрабатывает ошибки публичного API.
     *
     * @param {unknown} error Ошибка.
     * @param {FeatureFlagErrorContext} context Контекст ошибки.
     * @private
     */
    #handleError(error: unknown, context: FeatureFlagErrorContext): void {
        this.#onError?.(error, context);
        this.#logger?.error(error);
    }
}
