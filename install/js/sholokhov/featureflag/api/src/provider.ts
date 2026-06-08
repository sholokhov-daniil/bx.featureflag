import { ProviderOptions } from "../@types/provider.ts";

export class Provider {
    #options: ProviderOptions;

    constructor(options: ProviderOptions) {
        this.#options = options;
    }

    async send(action: string, data: object, method: string = 'GET'): Promise<any> {
        console.log(data);

        return await BX.ajax.runAction(
            this.#options.controller + `.${action}`,
            {
                method,
                json: data
            }
        )
    }
}