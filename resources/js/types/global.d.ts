import type { AppIdentity, Auth, Flash } from '@/types';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            app: AppIdentity;
            auth: Auth;
            flash: Flash;
            errors: Record<string, string>;
            unread_notifications: number;
            [key: string]: unknown;
        };
    }
}
