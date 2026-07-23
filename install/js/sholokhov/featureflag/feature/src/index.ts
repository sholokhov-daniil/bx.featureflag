import { Extension } from 'main.core';
import { Facade } from "./facade.ts";
import type {
    FeatureCode,
    FeatureInterface,
} from './types.ts';

const flags = Extension
    .getSettings('sholokhov.featureflag.feature')
    .get('flags')
    ?? [];

export const Feature: FeatureInterface = new Facade(flags);

export type {
    FeatureCode,
    FeatureFlagCallback,
    FeatureInterface,
} from './types.ts';