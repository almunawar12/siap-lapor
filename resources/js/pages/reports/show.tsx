import { AttachmentPanel } from '@/components/attachment-panel';
import { ReviewPanel } from '@/components/review-panel';
import { ReportPreview } from '@/components/report-preview';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AppLayout } from '@/layouts/app-layout';
import { formatTanggal, formatWaktu } from '@/lib/format';
import type { ReportDetail } from '@/types';
import { Link } from '@inertiajs/react';
import { AlertTriangle, Clock, Download, Pencil } from 'lucide-react';

function Meta({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

type Props = {
    report: ReportDetail;
    attachment_limits: {
        max_files: number;
        max_size_kb: number;
        extensions: string[];
    };
};

export default function ReportShow({ report, attachment_limits }: Props) {
    const version = report.current_version;

    return (
        <AppLayout
            title={report.report_number ?? 'LHP tanpa nomor'}
            description={`${report.district.name} · ${report.period.name}`}
            actions={
                <>
                    <StatusBadge
                        status={report.status}
                        label={report.status_label}
                    />
                    {report.capabilities.update ? (
                        <Button asChild size="sm">
                            <Link href={`/reports/${report.id}/edit`}>
                                <Pencil className="size-4" />
                                Ubah Laporan
                            </Link>
                        </Button>
                    ) : null}
                    {version ? (
                        <Button asChild variant="secondary" size="sm">
                            <a
                                href={`/reports/${report.id}/versions/${version.id}/pdf`}
                            >
                                <Download className="size-4" />
                                Unduh PDF
                            </a>
                        </Button>
                    ) : null}
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/reports">Daftar LHP</Link>
                    </Button>
                </>
            }
        >
            {report.is_late ? (
                <Alert>
                    <Clock className="size-4" />
                    <AlertTitle>Dikirim setelah batas waktu</AlertTitle>
                    <AlertDescription>
                        Penanda terlambat tidak mengubah status alur laporan.
                    </AlertDescription>
                </Alert>
            ) : null}

            {!report.period.is_active ? (
                <Alert>
                    <AlertTriangle className="size-4" />
                    <AlertTitle>Periode sudah tidak aktif</AlertTitle>
                    <AlertDescription>
                        Laporan yang sudah ada tetap dapat direvisi, tetapi
                        laporan baru tidak dapat dibuat pada periode ini.
                    </AlertDescription>
                </Alert>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Informasi Laporan
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Meta label="Kecamatan" value={report.district.name} />
                        <Meta label="Periode" value={report.period.name} />
                        <Meta label="Dibuat oleh" value={report.created_by} />
                        <Meta
                            label="Versi aktif"
                            value={
                                version
                                    ? `Versi ${version.version_number}`
                                    : '-'
                            }
                        />
                        <Meta
                            label="Pertama dikirim"
                            value={formatWaktu(report.first_submitted_at)}
                        />
                        <Meta
                            label="Batas pengiriman periode"
                            value={formatTanggal(
                                report.period.submission_deadline,
                            )}
                        />
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Riwayat Versi</CardTitle>
                    <CardDescription>
                        Versi yang sudah dikirim tidak pernah diubah.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul className="divide-y rounded-md border">
                        {report.versions.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium">
                                        Versi {item.version_number}
                                        {item.is_current ? (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                Versi aktif
                                            </Badge>
                                        ) : null}
                                        {item.is_approved ? (
                                            <Badge className="ml-2">
                                                Disetujui
                                            </Badge>
                                        ) : null}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {item.submitted_at
                                            ? `Dikirim ${formatWaktu(item.submitted_at)}`
                                            : 'Belum dikirim (versi kerja)'}
                                    </p>
                                </div>
                                <Button asChild variant="outline" size="sm">
                                    <Link
                                        href={`/reports/${report.id}/versions/${item.id}`}
                                    >
                                        Lihat
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost" size="sm">
                                    <a
                                        href={`/reports/${report.id}/versions/${item.id}/pdf`}
                                    >
                                        PDF
                                    </a>
                                </Button>
                            </li>
                        ))}
                    </ul>
                </CardContent>
            </Card>

            <ReviewPanel report={report} />

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Riwayat Aktivitas
                    </CardTitle>
                    <CardDescription>
                        Catatan append-only: tidak diubah dan tidak dihapus.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {report.timeline.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada aktivitas.
                        </p>
                    ) : (
                        <ol className="space-y-2">
                            {report.timeline.map((entry) => (
                                <li key={entry.id} className="text-sm">
                                    <p>
                                        <span className="font-medium">
                                            {entry.event_label}
                                        </span>
                                        {entry.actor_name
                                            ? ` oleh ${entry.actor_name}`
                                            : ''}
                                        <span className="text-muted-foreground">
                                            {' · '}
                                            {formatWaktu(entry.created_at)}
                                        </span>
                                    </p>
                                    {entry.reason ? (
                                        <p className="text-xs whitespace-pre-line text-muted-foreground">
                                            Alasan: {entry.reason}
                                        </p>
                                    ) : null}
                                </li>
                            ))}
                        </ol>
                    )}
                </CardContent>
            </Card>

            {version ? (
                <>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Isi Formulir Model A
                            </CardTitle>
                            <CardDescription>
                                Versi {version.version_number}
                                {version.submitted_at === null
                                    ? ', masih berupa versi kerja.'
                                    : ', sudah dikirim.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ReportPreview report={report} version={version} />
                        </CardContent>
                    </Card>

                    <AttachmentPanel
                        report={report}
                        version={version}
                        limits={attachment_limits}
                    />
                </>
            ) : null}
        </AppLayout>
    );
}
