import { cn } from '@/lib/utils';
import { ShieldCheck } from 'lucide-react';

type BrandProps = {
    name: string;
    tagline?: string;
    className?: string;
    inverse?: boolean;
};

export function Brand({
    name,
    tagline,
    className,
    inverse = false,
}: BrandProps) {
    return (
        <div className={cn('flex items-center gap-3', className)}>
            <span
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-md',
                    inverse
                        ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                        : 'bg-primary text-primary-foreground',
                )}
            >
                <ShieldCheck className="size-5" aria-hidden="true" />
            </span>
            <span className="min-w-0">
                <span
                    className={cn(
                        'block truncate text-sm font-semibold tracking-tight',
                        inverse && 'text-sidebar-foreground',
                    )}
                >
                    {name}
                </span>
                {tagline ? (
                    <span
                        className={cn(
                            'block truncate text-xs',
                            inverse
                                ? 'text-sidebar-foreground/70'
                                : 'text-muted-foreground',
                        )}
                    >
                        {tagline}
                    </span>
                ) : null}
            </span>
        </div>
    );
}
