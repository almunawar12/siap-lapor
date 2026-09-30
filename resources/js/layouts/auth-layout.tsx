import { Brand } from '@/components/brand';
import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

type AuthLayoutProps = PropsWithChildren<{
    title: string;
    description?: string;
}>;

export function AuthLayout({ title, description, children }: AuthLayoutProps) {
    const { app } = usePage().props;

    return (
        <div className="flex min-h-dvh flex-col items-center justify-center gap-6 bg-background p-4 sm:p-8">
            <div className="w-full max-w-sm space-y-6">
                <Brand
                    name={app.name}
                    tagline={app.tagline}
                    className="justify-center"
                />

                <div className="rounded-lg border bg-card p-5 shadow-[0_8px_30px_oklch(0.2_0.03_250/0.08)] sm:p-7">
                    <div className="mb-6 space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description ? (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>

                    {children}
                </div>

                {app.institution_name ? (
                    <p className="text-center text-xs text-muted-foreground">
                        {app.institution_name}
                        {app.regency_name ? ` · ${app.regency_name}` : ''}
                    </p>
                ) : null}
            </div>
        </div>
    );
}
