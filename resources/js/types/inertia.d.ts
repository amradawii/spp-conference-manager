import '@inertiajs/core';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        errorValueType: string;
        flashDataType: {
            toast?: string;
        };
        sharedPageProps: {
            editor: { name: string | null };
        };
    }
}
