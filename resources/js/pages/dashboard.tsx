import { OptionSelect } from '@/components/option-select';
import { StatusBadge, statusFill } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { AppLayout } from '@/layouts/app-layout';
import { formatTanggal, formatWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportListItem, ReportStatus } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, FilePlus2, FileText, Info } from 'lucide-react';

type StatusCard = { value: ReportStatus; label: string; total: number };

type KabupatenStats = {
    districts_total: number;
    districts_active: number;
    districts_reported: number;
    districts_not_reported: number | null;
    kecamatan_accounts_total: number;
    kecamatan_accounts_active: number;
};

type Props = {
    period: {
        id: number;
        name: string;
        is_active: boolean;
        submission_deadline: string | null;
    } | null;
    periods: { id: number; name: string }[];
    status_cards: StatusCard[];
    reports_total: number;
    recent_reports: ReportListItem[];
    kabupaten: KabupatenStats | null;
};

/**
 * Selisih hari kalender antara hari ini (waktu lokal) dan tanggal sipil
 * YYYY-MM-DD. Tanggal dibangun lokal agar tidak bergeser karena timezone.
 */
function daysUntil(date: string): number {
    const [y, m, d] = date.split('-').map(Number);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    return Math.round(
        (new Date(y, m - 1, d).getTime() - today.getTime()) / 86_400_000,
    );
}

function Countdown({ deadline }: { deadline: string }) {
    const days = daysUntil(deadline);
    const [value, unit] =
        days > 0
            ? [days, 'hari lagi']
            : days === 0
              ? ['Hari ini', 'batas terakhir']
              : [Math.abs(days), 'hari terlewat'];

    return (
        <div className="shrink-0 sm:text-right">
            <p className="text-sm text-primary-foreground/70">
                Batas kirim {formatTanggal(deadline)}
            </p>
            <p className="mt-1 flex items-baseline gap-2 sm:justify-end">
                <span
                    className={cn(
                        'text-4xl leading-none font-semibold tracking-tight tabular-nums sm:text-5xl',
                        days < 0 && 'text-orange-300',
                    )}
                >
                    {value}
                </span>
                <span className="text-sm text-primary-foreground/80">
                    {unit}
                </span>
            </p>
        </div>
    );
}

function StatusFlow({
    cards,
    total,
    periodId,
}: {
    cards: StatusCard[];
    total: number;
    periodId: number | null;
}) {
    return (
        <Card className="gap-5">
            <CardHeader>
                <CardTitle className="text-base">Alur status laporan</CardTitle>
                <CardDescription>
                    {total.toLocaleString('id-ID')} laporan pada cakupan dan
                    periode ini. Pilih tahap untuk membuka daftarnya.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
                <div
                    className="flex h-2.5 overflow-hidden rounded-full bg-muted"
                    aria-hidden="true"
                >
                    {total > 0
                        ? cards.map((card) =>
                              card.total > 0 ? (
                                  <span
                                      key={card.value}
                                      className={cn(
                                          'h-full border-r-2 border-card last:border-r-0',
                                          statusFill[card.value],
                                      )}
                                      style={{
                                          width: `${(card.total / total) * 100}%`,
                                      }}
                                  />
                              ) : null,
                          )
                        : null}
                </div>

                <ol className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:flex lg:items-stretch lg:gap-0">
                    {cards.map((card, index) => (
                        <li
                            key={card.value}
                            className="flex min-w-0 items-center lg:flex-1"
                        >
                            <Link
                                href={`/reports?status=${card.value}${periodId ? `&period=${periodId}` : ''}`}
                                className="group flex h-full w-full flex-col gap-2 rounded-md border border-transparent p-3 transition-colors hover:border-border hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <span className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span
                                        className={cn(
                                            'size-2.5 shrink-0 rounded-full',
                                            statusFill[card.value],
                                        )}
                                        aria-hidden="true"
                                    />
                                    <span className="truncate group-hover:text-foreground">
                                        {card.label}
                                    </span>
                                </span>
                                <span className="text-3xl font-semibold tabular-nums">
                                    {card.total}
                                </span>
                            </Link>
                            {index < cards.length - 1 ? (
                                <ChevronRight
                                    className="hidden size-4 shrink-0 text-muted-foreground/60 lg:block"
                                    aria-hidden="true"
                                />
                            ) : null}
                        </li>
                    ))}
                </ol>
            </CardContent>
        </Card>
    );
}

function Coverage({ stats }: { stats: KabupatenStats }) {
    const percent =
        stats.districts_active > 0
            ? Math.round(
                  (stats.districts_reported / stats.districts_active) * 100,
              )
            : 0;

    return (
        <Card className="gap-5">
            <CardHeader>
                <CardTitle className="text-base">
                    Cakupan pelaporan kecamatan
                </CardTitle>
                <CardDescription>
                    Kecamatan aktif yang sudah mengirim minimal satu laporan
                    pada periode ini.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
                <div>
                    <p className="flex items-baseline gap-2">
                        <span className="text-4xl font-semibold tabular-nums">
                            {stats.districts_reported}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            dari {stats.districts_active} kecamatan aktif
                        </span>
                    </p>
                    <div
                        role="progressbar"
                        aria-label="Kecamatan sudah mengirim"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent}
                        className="mt-3 h-2.5 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            className="h-full rounded-full bg-primary"
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground tabular-nums">
                        {percent}% tercakup
                    </p>
                </div>

                <dl className="grid grid-cols-2 gap-4 border-t pt-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Belum melapor</dt>
                        <dd className="mt-1 text-xl font-semibold tabular-nums">
                            {stats.districts_not_reported ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Akun kecamatan aktif
                        </dt>
                        <dd className="mt-1 text-xl font-semibold tabular-nums">
                            {stats.kecamatan_accounts_active}
                            <span className="ml-1 text-sm font-normal text-muted-foreground">
                                / {stats.kecamatan_accounts_total}
                            </span>
                        </dd>
                    </div>
                    <div className="col-span-2 text-xs text-muted-foreground">
                        {stats.districts_total} kecamatan terdaftar,{' '}
                        {stats.districts_active} aktif.
                    </div>
                </dl>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    period,
    periods,
    status_cards,
    reports_total,
    recent_reports,
    kabupaten,
}: Props) {
    const user = usePage().props.auth.user;
    const canCreate = !user?.is_kabupaten && period?.is_active;

    return (
        <AppLayout
            title="Dashboard"
            description={
                user?.district
                    ? `${user.role_label} / ${user.district.name}`
                    : user?.role_label
            }
        >
            {periods.length > 0 ? (
                <section
                    aria-label="Periode pelaporan"
                    className="rounded-xl bg-primary p-5 text-primary-foreground shadow-sm sm:p-6"
                >
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <div className="grid min-w-0 gap-2">
                            <Label
                                htmlFor="period"
                                className="text-sm font-normal text-primary-foreground/70"
                            >
                                Periode yang sedang dilihat
                            </Label>
                            <OptionSelect
                                id="period"
                                value={period ? String(period.id) : ''}
                                onValueChange={(value) =>
                                    router.get(
                                        '/dashboard',
                                        { period: value },
                                        { preserveState: true },
                                    )
                                }
                                options={periods.map((item) => ({
                                    value: item.id,
                                    label: item.name,
                                }))}
                                tall
                                className="font-medium text-foreground sm:w-80"
                            />
                            {period && !period.is_active ? (
                                <p className="text-sm text-primary-foreground/70">
                                    Periode ini sudah tidak aktif.
                                </p>
                            ) : null}
                        </div>

                        {period?.is_active && period.submission_deadline ? (
                            <Countdown deadline={period.submission_deadline} />
                        ) : null}
                    </div>

                    {canCreate ? (
                        <div className="mt-6 border-t border-primary-foreground/15 pt-5">
                            <Button
                                asChild
                                variant="secondary"
                                className="w-full sm:w-auto"
                            >
                                <Link href="/reports/create">
                                    <FilePlus2 className="size-4" />
                                    Buat LHP baru
                                </Link>
                            </Button>
                        </div>
                    ) : null}
                </section>
            ) : (
                <Alert>
                    <Info className="size-4" />
                    <AlertTitle>Belum ada periode pelaporan</AlertTitle>
                    <AlertDescription>
                        {user?.is_kabupaten
                            ? 'Buat periode pelaporan terlebih dahulu pada menu Periode Pelaporan.'
                            : 'Admin Kabupaten belum membuka periode pelaporan.'}
                    </AlertDescription>
                </Alert>
            )}

            <div
                className={cn(
                    'grid gap-4',
                    kabupaten && 'xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]',
                )}
            >
                <StatusFlow
                    cards={status_cards}
                    total={reports_total}
                    periodId={period?.id ?? null}
                />
                {kabupaten ? <Coverage stats={kabupaten} /> : null}
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-3">
                    <CardTitle className="text-base">Laporan terbaru</CardTitle>
                    {recent_reports.length > 0 ? (
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={`/reports${period ? `?period=${period.id}` : ''}`}
                            >
                                Lihat semua
                            </Link>
                        </Button>
                    ) : null}
                </CardHeader>
                <CardContent>
                    {recent_reports.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-md border border-dashed px-6 py-10 text-center">
                            <FileText
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div>
                                <p className="text-sm font-medium">
                                    Belum ada laporan pada periode ini
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Laporan yang dibuat akan tampil di bagian
                                    ini.
                                </p>
                            </div>
                            {canCreate ? (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/reports/create">
                                        Buat LHP baru
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {recent_reports.map((report) => (
                                <li key={report.id} className="relative">
                                    <span
                                        className={cn(
                                            'absolute inset-y-0 left-0 w-1',
                                            statusFill[report.status],
                                        )}
                                        aria-hidden="true"
                                    />
                                    <Link
                                        href={`/reports/${report.id}`}
                                        className="flex flex-col gap-3 py-4 pr-4 pl-5 transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset sm:flex-row sm:items-center"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold">
                                                {report.report_number ??
                                                    'LHP tanpa nomor'}
                                            </p>
                                            <p className="mt-1 text-xs leading-relaxed text-muted-foreground sm:truncate">
                                                {report.district.name} /{' '}
                                                {report.activity_name ??
                                                    'Kegiatan belum diisi'}
                                            </p>
                                        </div>
                                        <div className="flex items-center justify-between gap-3 sm:justify-end">
                                            <span className="text-xs text-muted-foreground">
                                                {formatWaktu(report.updated_at)}
                                            </span>
                                            <StatusBadge
                                                status={report.status}
                                                label={report.status_label}
                                            />
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
