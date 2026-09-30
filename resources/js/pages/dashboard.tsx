import { OptionSelect } from '@/components/option-select';
import { StatusBadge } from '@/components/status-badge';
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
import type { ReportListItem, ReportStatus } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    FilePlus2,
    FileText,
    Info,
} from 'lucide-react';

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

function StatCard({
    title,
    value,
    hint,
}: {
    title: string;
    value: number | string;
    hint: string;
}) {
    return (
        <Card className="gap-3">
            <CardHeader className="gap-1 pb-0">
                <CardDescription className="font-medium text-foreground">
                    {title}
                </CardDescription>
                <CardTitle className="text-2xl tabular-nums sm:text-3xl">
                    {value}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {hint}
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
                <Card className="border-primary/20">
                    <CardContent className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div className="flex min-w-0 gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                <CalendarDays
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <div className="grid min-w-0 gap-2">
                                <div>
                                    <h2 className="font-semibold">
                                        Periode yang sedang dilihat
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Semua ringkasan di bawah mengikuti
                                        periode ini.
                                    </p>
                                </div>
                                <Label htmlFor="period" className="sr-only">
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
                                    className="font-medium sm:w-72"
                                />
                                <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                    {period?.submission_deadline ? (
                                        <span>
                                            Batas kirim:{' '}
                                            <strong className="font-medium text-foreground">
                                                {formatTanggal(
                                                    period.submission_deadline,
                                                )}
                                            </strong>
                                        </span>
                                    ) : null}
                                    {period && !period.is_active ? (
                                        <span>Periode tidak aktif</span>
                                    ) : null}
                                </div>
                            </div>
                        </div>

                        {!user?.is_kabupaten && period?.is_active ? (
                            <Button asChild className="w-full sm:w-auto">
                                <Link href="/reports/create">
                                    <FilePlus2 className="size-4" />
                                    Buat LHP baru
                                </Link>
                            </Button>
                        ) : null}
                    </CardContent>
                </Card>
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

            <section aria-labelledby="status-heading" className="space-y-3">
                <div>
                    <h2 id="status-heading" className="text-base font-semibold">
                        Ringkasan status laporan
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Pilih status untuk membuka daftar laporan terkait.
                    </p>
                </div>
                <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    {status_cards.map((card) => (
                        <Link
                            key={card.value}
                            href={`/reports?status=${card.value}${period ? `&period=${period.id}` : ''}`}
                            className="rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <Card className="h-full gap-3 transition-colors hover:border-primary/40 hover:bg-accent/35">
                                <CardHeader className="gap-3 pb-0">
                                    <StatusBadge
                                        status={card.value}
                                        label={card.label}
                                    />
                                    <CardTitle className="text-2xl tabular-nums sm:text-3xl">
                                        {card.total}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>
            </section>

            {kabupaten ? (
                <section
                    aria-labelledby="coverage-heading"
                    className="space-y-3"
                >
                    <h2
                        id="coverage-heading"
                        className="text-base font-semibold"
                    >
                        Cakupan pelaporan kecamatan
                    </h2>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            title="Kecamatan aktif"
                            value={kabupaten.districts_active}
                            hint={`Dari ${kabupaten.districts_total} kecamatan terdaftar.`}
                        />
                        <StatCard
                            title="Kecamatan sudah mengirim"
                            value={kabupaten.districts_reported}
                            hint="Memiliki minimal satu laporan terkirim pada periode ini."
                        />
                        <StatCard
                            title="Kecamatan belum melapor"
                            value={kabupaten.districts_not_reported ?? '-'}
                            hint="Kecamatan aktif tanpa pengiriman pada periode terpilih."
                        />
                        <StatCard
                            title="Akun kecamatan aktif"
                            value={kabupaten.kecamatan_accounts_active}
                            hint={`Dari ${kabupaten.kecamatan_accounts_total} akun kecamatan.`}
                        />
                    </div>
                </section>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Laporan terbaru</CardTitle>
                    <CardDescription>
                        {reports_total.toLocaleString('id-ID')} laporan pada
                        cakupan dan periode ini.
                    </CardDescription>
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
                            {user?.is_kabupaten ? null : (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/reports/create">
                                        Buat LHP baru
                                    </Link>
                                </Button>
                            )}
                        </div>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {recent_reports.map((report) => (
                                <li
                                    key={report.id}
                                    className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold">
                                            {report.report_number ??
                                                'LHP tanpa nomor'}
                                        </p>
                                        <p className="mt-1 text-xs leading-relaxed text-muted-foreground sm:truncate">
                                            {report.district.name} /{' '}
                                            {report.activity_name ??
                                                'Kegiatan belum diisi'}{' '}
                                            / {formatWaktu(report.updated_at)}
                                        </p>
                                    </div>
                                    <div className="flex items-center justify-between gap-3 sm:justify-end">
                                        <StatusBadge
                                            status={report.status}
                                            label={report.status_label}
                                        />
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link
                                                href={`/reports/${report.id}`}
                                            >
                                                Buka
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
