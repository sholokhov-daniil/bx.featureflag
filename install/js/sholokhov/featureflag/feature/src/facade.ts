import {
    FeatureCode,
    FeatureFlagCallback,
    FeatureInterface,
} from './types.ts'

export class Facade implements FeatureInterface {
    #store: Array<FeatureCode>;

    constructor(flags: FeatureCode[]) {
        this.#store = flags;
    }

    /**
     * Фича активна
     *
     * @param {FeatureCode} code
     */
    isEnabled(code: FeatureCode): boolean {
        return this.#store.includes(code);
    }

    /**
     * Фича не активна
     *
     * @param {FeatureCode} code
     */
    isDisabled(code: FeatureCode): boolean {
        return !this.isEnabled(code);
    }

    /**
     * Вызов callback в зависимости от статуса фичи
     *
     * @param {FeatureCode} code
     * @param {FeatureFlagCallback} enabled
     * @param {FeatureFlagCallback} disabled
     */
    async when(code: FeatureCode, enabled?: FeatureFlagCallback, disabled?: FeatureFlagCallback): Promise<boolean> {
        const active = this.isEnabled(code);

        if (active) {
            await enabled?.();
        } else {
            await disabled?.();
        }

        return active;
    }

    /**
     * Проверяет, активна ли хотя бы одна фича из списка
     * Возвращает true при первой найденной активной фиче.
     *
     * @param {FeatureCode[]} codes Список кодов фич
     */
    any(codes: FeatureCode[]): boolean {
        for (const name of codes) {
            if (this.isEnabled(name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, что все фичи из списка активны
     * Возвращает false при первой найденной отключенной фиче.
     *
     * @param {FeatureCode[]} codes
     */
    all(codes: FeatureCode[]): boolean {
        for (const name of codes) {
            if (this.isDisabled(name)) {
                return false;
            }
        }

        return true;
    }
}
