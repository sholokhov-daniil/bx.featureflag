export type ProviderOptions  = {
    controller: string,
}

export interface Provider {
    send(action: string, data: object, method?: string): Promise<any>
}