export type FeatureFlagLogger = Pick<Console, 'error' | 'warn'>;

export type FeatureFlagErrorContext = {
    method: string;
    code?: string;
    codes?: string[];
}

export type FeatureFlagErrorHandler = (error: unknown, context: FeatureFlagErrorContext) => void;

export type FeatureFlagCallback = () => void | Promise<void>;

export type FacadeOptions = {
    logger?: FeatureFlagLogger | null;
    onError?: FeatureFlagErrorHandler;
    defaultValueOnError?: boolean;
}

export type FeatureEnabledBulk = Record<string, boolean>;
