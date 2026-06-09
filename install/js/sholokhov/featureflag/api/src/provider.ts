import { ProviderOptions } from "../@types/provider.ts";

export class Provider {
    #options: ProviderOptions;

    constructor(options: ProviderOptions) {
        this.#options = options;
    }

    async send(action: string, data: object, method: string = 'POST'): Promise<any> {
        return await BX.ajax.runAction(
            this.#options.controller + `.${action}`,
            {
                method,
                data: data
            }
        )
    }
}