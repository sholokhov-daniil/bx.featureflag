import {Facade} from "./facade.ts";
import { Provider } from "./provider.ts";
import { Store } from "./store.ts";

const provider = new Provider({
    controller: 'sholokhov:featureflag.PublicFeatureFlag'
})

export const Feature = new Facade(provider, new Store);