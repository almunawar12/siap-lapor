import { cn } from '@/lib/utils';
import { ShieldCheck } from 'lucide-react';

type BrandProps = {
    name: string;
    tagline?: string;
    className?: string;
};

export function Brand({ name, tagline, className }: BrandProps) {
    return (
        <div className={cn('flex items-center gap-3', className)}>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <ShieldCheck className="size-5" aria-hidden="true" />
            </span>
            <span className="min-w-0">
                <span className="block truncate text-sm font-semibold tracking-tight">
                    {name}
                </span>
                {tagline ? (
                    <span className="block truncate text-xs text-muted-foreground">
                        {tagline}
                    </span>
                ) : null}
            </span>
        </div>
    );
}
