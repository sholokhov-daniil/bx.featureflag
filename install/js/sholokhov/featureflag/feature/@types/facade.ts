declare module 'sholokhov.featureflag.feature'
{
    export type FeatureCode = string;
    export type FeatureFlagCallback = () => void | Promise<void>;

    export class Feature
    {
        isEnabled(code: FeatureCode): boolean;
        isDisabled(code: FeatureCode): boolean;
        when(code: FeatureCode, enabled?: FeatureFlagCallback, disabled?: FeatureFlagCallback): Promise<boolean>;
        any(...codes: FeatureCode[]): boolean;
        all(...codes: FeatureCode[]): boolean;
    }
}
