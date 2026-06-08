import {Facade} from "./facade.ts";
import { Provider } from "./provider.ts";

const provider = new Provider({
    controller: 'sholokhov:featureflag.PublicFeatureFlag'
})

export const API = new Facade(provider);