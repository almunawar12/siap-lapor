import { AttachmentPanel } from '@/components/attachment-panel';
import { OptionSelect } from '@/components/option-select';
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
import { Label } from '@/components/ui/label';
import { AppLayout } from '@/layouts/app-layout';
import { formatWaktu } from '@/lib/format';
import type { ReportDetail, ReportVersion, VersionDiff } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Download, History } from 'lucide-react';

type Props = {
    report: ReportDetail;
    version: ReportVersion;
    diff: VersionDiff | null;
    compare_id: number | null;
};

export default function ReportVersionPage({
    report,
    version,
    diff,
    compare_id,
}: Props) {
    const isCurrent = report.current_version?.id === version.id;
    const isApproved = report.approved_version_id === version.id;

    const others = report.versions.filter((item) => item.id !== version.id);

    return (
        <AppLayout
            title={`Versi ${version.version_number}`}
            description={`${report.district.name} · ${report.period.name}`}
            actions={
                <>
                    <StatusBadge
                        status={report.status}
                        label={report.status_label}
                    />
                    {isApproved ? <Badge>Persetujuan aktif</Badge> : null}
                    <Button asChild size="sm">
                        <a
                            href={`/reports/${report.id}/versions/${version.id}/pdf`}
                        >
                            <Download className="size-4" />
                            Unduh PDF
                        </a>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/reports/${report.id}`}>
                            Kembali ke Detail
                        </Link>
                    </Button>
                </>
            }
        >
            {!isCurrent ? (
                <Alert>
                    <History className="size-4" />
                    <AlertTitle>Versi historis</AlertTitle>
                    <AlertDescription>
                        Isi di bawah adalah snapshot versi{' '}
                        {version.version_number}, bukan versi kerja terkini.
                    </AlertDescription>
                </Alert>
            ) : null}

            <Card>
                <CardContent className="space-y-2 pt-6 text-sm">
                    <p>
                        Dibuat oleh {version.created_by} · Dikirim{' '}
                        {formatWaktu(version.submitted_at)}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {version.submitted_at === null
                            ? 'Versi ini masih dapat disunting.'
                            : 'Versi ini sudah dikirim dan bersifat permanen.'}
                    </p>
                </CardContent>
            </Card>

            {others.length > 0 ? (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Bandingkan Versi
                        </CardTitle>
                        <CardDescription>
                            Perbandingan menampilkan nilai sebelum dan sesudah
                            per field, serta perubahan daftar lampiran.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid max-w-sm gap-2">
                            <Label htmlFor="compare">
                                Bandingkan dengan versi
                            </Label>
                            <OptionSelect
                                id="compare"
                                value={compare_id ? String(compare_id) : ''}
                                onValueChange={(value) =>
                                    router.get(
                                        `/reports/${report.id}/versions/${version.id}`,
                                        value === '' ? {} : { compare: value },
                                        { preserveScroll: true },
                                    )
                                }
                                options={[
                                    { value: '', label: 'Tidak dibandingkan' },
                                    ...others.map((item) => ({
                                        value: item.id,
                                        label: `Versi ${item.version_number}${item.submitted_at ? '' : ' (versi kerja)'}`,
                                    })),
                                ]}
                            />
                        </div>

                        {diff ? (
                            <div className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    Versi {diff.from.version_number} → versi{' '}
                                    {diff.to.version_number}
                                </p>

                                {diff.fields.length === 0 ? (
                                    <p className="text-sm">
                                        Tidak ada perbedaan isian antara kedua
                                        versi.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {diff.fields.map((field) => (
                                            <li
                                                key={field.key}
                                                className="rounded-md border p-3"
                                            >
                                                <p className="text-sm font-medium">
                                                    {field.label}
                                                </p>
                                                <dl className="mt-2 grid gap-2 sm:grid-cols-2">
                                                    <div>
                                                        <dt className="text-xs text-muted-foreground">
                                                            Sebelum (versi{' '}
                                                            {
                                                                diff.from
                                                                    .version_number
                                                            }
                                                            )
                                                        </dt>
                                                        <dd className="text-sm whitespace-pre-line">
                                                            {field.before ??
                                                                '(kosong)'}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt className="text-xs text-muted-foreground">
                                                            Sesudah (versi{' '}
                                                            {
                                                                diff.to
                                                                    .version_number
                                                            }
                                                            )
                                                        </dt>
                                                        <dd className="text-sm whitespace-pre-line">
                                                            {field.after ??
                                                                '(kosong)'}
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                <div className="grid gap-2 text-sm sm:grid-cols-3">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Lampiran ditambahkan
                                        </p>
                                        <p>
                                            {diff.attachments.added.length === 0
                                                ? '-'
                                                : diff.attachments.added.join(
                                                      ', ',
                                                  )}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Lampiran dilepas
                                        </p>
                                        <p>
                                            {diff.attachments.removed.length ===
                                            0
                                                ? '-'
                                                : diff.attachments.removed.join(
                                                      ', ',
                                                  )}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Lampiran tetap
                                        </p>
                                        <p>
                                            {diff.attachments.unchanged
                                                .length === 0
                                                ? '-'
                                                : diff.attachments.unchanged.join(
                                                      ', ',
                                                  )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>
            ) : null}

            <Card>
                <CardContent className="pt-6">
                    <ReportPreview report={report} version={version} />
                </CardContent>
            </Card>

            <AttachmentPanel
                report={{
                    ...report,
                    capabilities: {
                        ...report.capabilities,
                        // Lampiran versi historis hanya dapat diunduh.
                        manage_attachments: false,
                    },
                }}
                version={version}
                limits={{ max_files: 0, max_size_kb: 0, extensions: [] }}
            />
        </AppLayout>
    );
}
