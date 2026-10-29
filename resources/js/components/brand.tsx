import { cn } from '@/lib/utils';

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
            <img
                src="/logo.png"
                alt=""
                className="size-12 shrink-0 rounded-md bg-white object-contain"
            />
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
                                ? 'text-sidebar-foreground/80'
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
