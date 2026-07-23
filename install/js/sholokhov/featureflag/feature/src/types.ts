export type FeatureFlagCallback = () => void | Promise<void>;
export type FeatureCode = string;

export interface FeatureInterface
{
    isEnabled(code: FeatureCode): boolean;
    isDisabled(code: FeatureCode): boolean;
    when(code: FeatureCode, enabled?: FeatureFlagCallback, disabled?: FeatureFlagCallback): Promise<boolean>;
    any(codes: FeatureCode[]): boolean;
    all(codes: FeatureCode[]): boolean;
}