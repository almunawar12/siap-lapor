import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

type AuthLayoutProps = PropsWithChildren<{
    title: string;
    description?: string;
}>;

// Kotak suara dari logo: heksagon tegak dengan kertas suara miring masuk ke celah.
function BallotMark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 400 440"
            className={className}
            aria-hidden="true"
            focusable="false"
        >
            <polygon
                points="200,70 380,170 380,350 200,440 20,350 20,170"
                className="fill-none stroke-secondary"
                strokeWidth="14"
                strokeLinejoin="round"
                opacity="0.45"
            />
            <path
                d="M200 160 L200 440"
                className="stroke-primary-foreground"
                strokeWidth="14"
                opacity="0.18"
            />
            <polygon
                points="170,0 280,0 230,150 120,150"
                className="fill-secondary"
            />
        </svg>
    );
}

export function AuthLayout({ title, description, children }: AuthLayoutProps) {
    const { app } = usePage().props;

    return (
        <div className="grid min-h-dvh bg-background lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <section className="relative overflow-hidden bg-primary px-6 py-8 text-primary-foreground sm:px-10 lg:flex lg:flex-col lg:justify-between lg:px-14 lg:py-14">
                <BallotMark className="pointer-events-none absolute -right-16 -bottom-24 hidden w-[min(34rem,90%)] lg:block" />

                <div className="relative max-w-md">
                    <p className="text-sm font-medium text-secondary">
                        {app.tagline}
                    </p>
                    <p className="mt-3 text-4xl leading-none font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {app.name}
                    </p>
                    <p className="mt-5 hidden max-w-sm text-base leading-relaxed text-primary-foreground/85 lg:block">
                        Laporan Hasil Pengawasan Pemutakhiran Data Pemilih
                        Berkelanjutan, disusun per kecamatan dan diperiksa
                        langsung oleh kabupaten.
                    </p>
                </div>

                {app.institution_name ? (
                    <div className="relative mt-6 hidden text-sm text-primary-foreground/85 lg:block">
                        <p className="font-semibold text-primary-foreground">
                            {app.institution_name}
                        </p>
                        {app.regency_name ? <p>{app.regency_name}</p> : null}
                    </div>
                ) : null}
            </section>

            <main className="flex items-center justify-center px-4 py-10 sm:px-8">
                <div className="w-full max-w-sm">
                    <img
                        src="/logo.png"
                        alt={app.institution_name || 'Logo instansi'}
                        className="mb-8 size-24 object-contain sm:size-28"
                    />

                    <div className="mb-6 space-y-1.5">
                        <h1 className="text-2xl font-semibold tracking-tight">
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
            </main>
        </div>
    );
}
