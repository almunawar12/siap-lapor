import { Badge } from '@/components/ui/badge';
import type { ReportStatus } from '@/types';

/**
 * Status selalu tampil sebagai teks, tidak hanya warna (PRD bagian 8).
 */
const variants: Record<ReportStatus, string> = {
    draft: 'bg-muted text-muted-foreground border-transparent',
    submitted:
        'bg-blue-100 text-blue-900 border-transparent dark:bg-blue-950 dark:text-blue-100',
    under_review:
        'bg-amber-100 text-amber-900 border-transparent dark:bg-amber-950 dark:text-amber-100',
    revision_required:
        'bg-orange-100 text-orange-900 border-transparent dark:bg-orange-950 dark:text-orange-100',
    approved:
        'bg-emerald-100 text-emerald-900 border-transparent dark:bg-emerald-950 dark:text-emerald-100',
};

/** Warna isian untuk bar/penanda status; selalu didampingi label teks. */
export const statusFill: Record<ReportStatus, string> = {
    draft: 'bg-slate-400 dark:bg-slate-500',
    submitted: 'bg-blue-600 dark:bg-blue-400',
    under_review: 'bg-amber-500 dark:bg-amber-400',
    revision_required: 'bg-orange-600 dark:bg-orange-400',
    approved: 'bg-emerald-600 dark:bg-emerald-400',
};

export function StatusBadge({
    status,
    label,
}: {
    status: ReportStatus;
    label: string;
}) {
    return <Badge className={variants[status]}>{label}</Badge>;
}
