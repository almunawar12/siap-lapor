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
import { FileText, Info } from 'lucide-react';

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
        <Card>
            <CardHeader className="pb-2">
                <CardDescription>{title}</CardDescription>
                <CardTitle className="text-3xl tabular-nums">{value}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-xs text-muted-foreground">{hint}</p>
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
                    ? `${user.role_label} · ${user.district.name}`
                    : user?.role_label
            }
        >
            {periods.length > 0 ? (
                <Card>
                    <CardContent className="flex flex-wrap items-end gap-3 pt-6">
                        <div className="grid gap-2">
                            <Label htmlFor="period">Periode pelaporan</Label>
                            <select
                                id="period"
                                value={period ? String(period.id) : ''}
                                onChange={(event) =>
                                    router.get(
                                        '/dashboard',
                                        { period: event.target.value },
                                        { preserveState: true },
                                    )
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {periods.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {period?.submission_deadline ? (
                            <p className="text-sm text-muted-foreground">
                                Batas pengiriman:{' '}
                                {formatTanggal(period.submission_deadline)}
                            </p>
                        ) : null}
                        {period && !period.is_active ? (
                            <p className="text-sm text-muted-foreground">
                                Periode ini tidak aktif; laporan baru tidak
                                dapat dibuat.
                            </p>
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

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {status_cards.map((card) => (
                    <Card key={card.value}>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                <StatusBadge
                                    status={card.value}
                                    label={card.label}
                                />
                            </CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {card.total}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                ))}
            </div>

            {kabupaten ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Kecamatan aktif"
                        value={kabupaten.districts_active}
                        hint={`Dari ${kabupaten.districts_total} kecamatan terdaftar.`}
                    />
                    <StatCard
                        title="Kecamatan sudah mengirim"
                        value={kabupaten.districts_reported}
                        hint="Kecamatan dengan minimal satu laporan terkirim pada periode ini."
                    />
                    <StatCard
                        title="Kecamatan belum melapor"
                        value={kabupaten.districts_not_reported ?? '—'}
                        hint="Kecamatan aktif tanpa pengiriman pada periode terpilih. Bukan kewajiban satu laporan per kecamatan."
                    />
                    <StatCard
                        title="Akun kecamatan aktif"
                        value={kabupaten.kecamatan_accounts_active}
                        hint={`Dari ${kabupaten.kecamatan_accounts_total} akun kecamatan.`}
                    />
                </div>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Laporan Terbaru</CardTitle>
                    <CardDescription>
                        {reports_total.toLocaleString('id-ID')} laporan pada
                        cakupan dan periode ini.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {recent_reports.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed px-6 py-10 text-center">
                            <FileText
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <p className="text-sm text-muted-foreground">
                                Belum ada laporan pada periode ini.
                            </p>
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
                                    className="flex flex-wrap items-center gap-3 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">
                                            {report.report_number ??
                                                'LHP tanpa nomor'}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {report.district.name} ·{' '}
                                            {report.activity_name ??
                                                'Kegiatan belum diisi'}{' '}
                                            · {formatWaktu(report.updated_at)}
                                        </p>
                                    </div>
                                    <StatusBadge
                                        status={report.status}
                                        label={report.status_label}
                                    />
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={`/reports/${report.id}`}>
                                            Buka
                                        </Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            <Alert>
                <Info className="size-4" />
                <AlertTitle>Alur pemeriksaan belum aktif</AlertTitle>
                <AlertDescription>
                    Pemeriksaan, catatan revisi, persetujuan, dan cetak PDF akan
                    ditambahkan pada tahap berikutnya. Angka di atas dihitung
                    dari data nyata; tidak ada statistik contoh.
                </AlertDescription>
            </Alert>
        </AppLayout>
    );
}
