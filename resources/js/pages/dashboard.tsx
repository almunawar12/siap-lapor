import { OptionSelect } from '@/components/option-select';
import { StatusBadge, statusFill } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

// Tanggal sipil dibangun lokal agar tenggat tidak bergeser karena timezone.
function daysUntil(date: string): number {
    const [y, m, d] = date.split('-').map(Number);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    return Math.round(
        (new Date(y, m - 1, d).getTime() - today.getTime()) / 86_400_000,
    );
}

function Deadline({ date }: { date: string }) {
    const days = daysUntil(date);
    return (
        <div className="text-sm sm:border-l sm:pl-6">
            <p className="text-muted-foreground">Batas pengiriman</p>
            <p className="mt-1 font-medium">{formatTanggal(date)}</p>
            <p
                className={cn(
                    'mt-1 text-xs text-muted-foreground',
                    days <= 0 && 'font-medium text-primary',
                )}
            >
                {days > 0
                    ? `${days} hari lagi`
                    : days === 0
                      ? 'Batas terakhir hari ini'
                      : `${Math.abs(days)} hari terlewat`}
            </p>
        </div>
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
        <Card className="gap-5 shadow-none">
            <CardHeader>
                <CardTitle className="text-base">Cakupan kecamatan</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="flex items-baseline gap-2">
                    <span className="text-4xl font-semibold tracking-tight tabular-nums">
                        {stats.districts_reported}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        / {stats.districts_active} aktif
                    </span>
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    Kecamatan sudah melapor
                </p>
                <div
                    role="progressbar"
                    aria-label="Kecamatan sudah mengirim"
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={percent}
                    className="mt-5 h-1.5 overflow-hidden rounded-full bg-muted"
                >
                    <div
                        className="h-full bg-foreground"
                        style={{ width: `${percent}%` }}
                    />
                </div>
                <p className="mt-2 text-right text-xs text-muted-foreground tabular-nums">
                    {percent}% tercakup
                </p>
                <dl className="mt-5 space-y-3 border-t pt-5 text-sm">
                    <div className="flex justify-between gap-3">
                        <dt className="text-muted-foreground">Belum melapor</dt>
                        <dd className="font-medium tabular-nums">
                            {stats.districts_not_reported ?? '—'}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-3">
                        <dt className="text-muted-foreground">Akun aktif</dt>
                        <dd className="font-medium tabular-nums">
                            {stats.kecamatan_accounts_active} /{' '}
                            {stats.kecamatan_accounts_total}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-3">
                        <dt className="text-muted-foreground">
                            Kecamatan terdaftar
                        </dt>
                        <dd className="font-medium tabular-nums">
                            {stats.districts_total}
                        </dd>
                    </div>
                </dl>
                <p className="mt-5 border-t pt-4 text-xs leading-relaxed text-muted-foreground">
                    Dihitung dari kecamatan aktif yang mengirim minimal satu
                    laporan pada periode ini. Satu kecamatan dapat mengirim
                    beberapa LHP.
                </p>
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
    const reportUrl = `/reports${period ? `?period=${period.id}` : ''}`;
    return (
        <AppLayout
            title="Dashboard"
            description={
                user?.district
                    ? `Ringkasan pelaporan Kecamatan ${user.district.name}.`
                    : 'Pantau pelaporan dan pemeriksaan LHP setiap periode.'
            }
            actions={
                canCreate ? (
                    <Button asChild className="min-h-11">
                        <Link href="/reports/create">
                            <FilePlus2 className="size-4" />
                            Buat LHP baru
                        </Link>
                    </Button>
                ) : undefined
            }
        >
            {periods.length > 0 ? (
                <section
                    aria-label="Periode pelaporan"
                    className="flex flex-col gap-5 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                >
                    <div className="min-w-0 space-y-2 sm:w-80">
                        <Label
                            htmlFor="period"
                            className="text-xs text-muted-foreground"
                        >
                            Periode pelaporan
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
                            className="bg-card font-medium"
                        />
                    </div>
                    <div className="flex flex-wrap items-center gap-x-6 gap-y-3">
                        <p className="text-sm text-muted-foreground">
                            {period?.is_active
                                ? 'Periode aktif'
                                : 'Periode tidak aktif'}
                        </p>
                        {period?.is_active && period.submission_deadline ? (
                            <Deadline date={period.submission_deadline} />
                        ) : null}
                    </div>
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

            <section
                aria-labelledby="status-heading"
                className="space-y-4 py-1"
            >
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="status-heading" className="text-base font-semibold">
                        Ringkasan laporan
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground tabular-nums">
                            {reports_total.toLocaleString('id-ID')}
                        </span>{' '}
                        laporan pada periode ini
                    </p>
                </div>
                <ol className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                    {status_cards.map((card) => (
                        <li key={card.value}>
                            <Link
                                href={`/reports?status=${card.value}${period ? `&period=${period.id}` : ''}`}
                                className="group flex h-full flex-col rounded-lg border bg-card p-4 transition-colors hover:border-input focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:p-5"
                            >
                                <span className="flex items-center gap-2 text-xs text-muted-foreground sm:text-sm">
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'size-2 shrink-0 rounded-full',
                                            statusFill[card.value],
                                        )}
                                    />
                                    {card.label}
                                </span>
                                <span className="mt-4 text-3xl font-semibold tracking-tight tabular-nums">
                                    {card.total.toLocaleString('id-ID')}
                                </span>
                                <span className="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                                    Lihat laporan
                                    <ChevronRight
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                </span>
                            </Link>
                        </li>
                    ))}
                </ol>
            </section>

            <div
                className={cn(
                    'grid items-start gap-5',
                    kabupaten && 'xl:grid-cols-[minmax(0,1fr)_19rem]',
                )}
            >
                <Card className="min-w-0 gap-0 overflow-hidden shadow-none">
                    <CardHeader className="flex flex-row items-center justify-between gap-3 border-b pb-4 sm:pb-5">
                        <div>
                            <CardTitle className="text-base">
                                Laporan terbaru
                            </CardTitle>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Diurutkan berdasarkan pembaruan terakhir.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="ghost"
                            className="min-h-11 shrink-0 text-xs"
                        >
                            <Link href={reportUrl}>Lihat semua</Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="px-0 sm:px-0">
                        {recent_reports.length === 0 ? (
                            <div className="flex flex-col items-start gap-4 px-5 py-12 sm:px-6">
                                <FileText
                                    className="size-7 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <p className="text-sm font-medium">
                                        Belum ada laporan pada periode ini
                                    </p>
                                    <p className="mt-1 max-w-sm text-sm leading-relaxed text-muted-foreground">
                                        {canCreate
                                            ? 'Mulai dengan membuat LHP baru. Draf yang disimpan akan tampil di sini.'
                                            : 'Laporan akan tampil setelah dibuat oleh admin kecamatan.'}
                                    </p>
                                </div>
                                {canCreate ? (
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="min-h-11"
                                    >
                                        <Link href="/reports/create">
                                            Buat LHP baru
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        ) : (
                            <ul className="divide-y">
                                {recent_reports.map((report) => (
                                    <li key={report.id}>
                                        <Link
                                            href={`/reports/${report.id}`}
                                            className="group flex flex-col gap-3 px-4 py-4 transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset sm:px-6"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <p className="min-w-0 text-sm font-semibold break-words">
                                                    {report.report_number ??
                                                        'LHP tanpa nomor'}
                                                </p>
                                                <StatusBadge
                                                    status={report.status}
                                                    label={report.status_label}
                                                />
                                            </div>
                                            <p className="text-sm leading-relaxed break-words text-muted-foreground">
                                                {report.activity_name ??
                                                    'Kegiatan belum diisi'}
                                            </p>
                                            <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                                <span>
                                                    {report.district.name}
                                                </span>
                                                <span>
                                                    Diperbarui{' '}
                                                    {formatWaktu(
                                                        report.updated_at,
                                                    )}
                                                </span>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
                {kabupaten ? <Coverage stats={kabupaten} /> : null}
            </div>
        </AppLayout>
    );
}
