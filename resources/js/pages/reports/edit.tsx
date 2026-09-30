import { AttachmentPanel } from '@/components/attachment-panel';
import {
    SelectField,
    TextAreaField,
    TextField,
} from '@/components/report-form-fields';
import { ReportPreview } from '@/components/report-preview';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AppLayout } from '@/layouts/app-layout';
import { MAX_LENGTHS } from '@/lib/report-limits';
import { cn } from '@/lib/utils';
import type { Option, PayloadInputField, ReportDetail } from '@/types';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    Check,
    ChevronLeft,
    ChevronRight,
    Save,
    Send,
} from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';

type Props = {
    report: ReportDetail;
    signer_capacities: Option[];
    attachment_limits: {
        max_files: number;
        max_size_kb: number;
        extensions: string[];
    };
};

const STEPS = [
    { key: 'pengawas', label: 'Data Pengawas' },
    { key: 'kegiatan', label: 'Kegiatan' },
    { key: 'hasil', label: 'Hasil' },
    { key: 'pengesahan', label: 'Pengesahan & Lampiran' },
] as const;

type StepKey = (typeof STEPS)[number]['key'];

/** Field yang ditinjau pada setiap tahap, untuk menandai tahap bermasalah. */
const STEP_FIELDS: Record<StepKey, PayloadInputField[]> = {
    pengawas: [
        'report_number',
        'supervisor_name',
        'supervisor_position',
        'assignment_number',
        'assignment_date',
        'supervisor_address',
    ],
    kegiatan: [
        'activity_name',
        'activity_form',
        'activity_purpose',
        'activity_target',
        'activity_start_date',
        'activity_end_date',
        'activity_start_time',
        'activity_end_time',
        'activity_location',
    ],
    hasil: ['findings'],
    pengesahan: [
        'signing_place',
        'signing_date',
        'signer_name',
        'signer_capacity',
    ],
};

export default function ReportEdit({
    report,
    signer_capacities,
    attachment_limits,
}: Props) {
    const version = report.current_version;
    const [step, setStep] = useState<StepKey>('pengawas');
    const [previewOpen, setPreviewOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const stepIndex = STEPS.findIndex((item) => item.key === step);

    const initial = useMemo(
        () => ({
            current_version_id: version?.id ?? 0,
            lock_version: report.lock_version,
            report_number: version?.payload.report_number ?? '',
            supervisor_name: version?.payload.supervisor_name ?? '',
            supervisor_position: version?.payload.supervisor_position ?? '',
            assignment_number: version?.payload.assignment_number ?? '',
            assignment_date: version?.payload.assignment_date ?? '',
            supervisor_address: version?.payload.supervisor_address ?? '',
            activity_name: version?.payload.activity_name ?? '',
            activity_form: version?.payload.activity_form ?? '',
            activity_purpose: version?.payload.activity_purpose ?? '',
            activity_target: version?.payload.activity_target ?? '',
            activity_start_date: version?.payload.activity_start_date ?? '',
            activity_end_date: version?.payload.activity_end_date ?? '',
            activity_start_time: version?.payload.activity_start_time ?? '',
            activity_end_time: version?.payload.activity_end_time ?? '',
            activity_location: version?.payload.activity_location ?? '',
            findings: version?.payload.findings ?? '',
            signing_place: version?.payload.signing_place ?? '',
            signing_date: version?.payload.signing_date ?? '',
            signer_name: version?.payload.signer_name ?? '',
            signer_capacity: version?.payload.signer_capacity ?? '',
        }),
        [report.lock_version, version],
    );

    const form = useForm(initial);
    const { data, setData, errors, processing, isDirty } = form;

    // Konflik penyimpanan datang dari error bag bersama, bukan dari field form.
    const conflict = usePage().props.errors.conflict;

    // Peringatkan saat meninggalkan halaman dengan perubahan belum disimpan.
    useEffect(() => {
        if (!isDirty) {
            return;
        }

        const warn = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, [isDirty]);

    function saveDraft(event?: FormEvent) {
        event?.preventDefault();
        form.transform((values) => ({
            ...values,
            lock_version: report.lock_version,
            current_version_id: version?.id ?? 0,
        }));
        form.patch(`/reports/${report.id}`, { preserveScroll: true });
    }

    function submitReport() {
        setConfirmOpen(false);
        setSubmitting(true);
        router.post(
            `/reports/${report.id}/submit`,
            {
                current_version_id: version?.id ?? 0,
                lock_version: report.lock_version,
            },
            { onFinish: () => setSubmitting(false) },
        );
    }

    const stepHasError = (key: StepKey) =>
        STEP_FIELDS[key].some((field) => Boolean(errors[field]));

    if (version === null) {
        return (
            <AppLayout title="Ubah Laporan">
                <Alert variant="destructive">
                    <AlertTriangle className="size-4" />
                    <AlertTitle>Versi kerja tidak ditemukan</AlertTitle>
                    <AlertDescription>
                        Laporan ini tidak memiliki versi kerja aktif. Hubungi
                        Admin Kabupaten.
                    </AlertDescription>
                </Alert>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            title="Formulir Model A"
            description={`${report.district.name} · ${report.period.name} · versi ${version.version_number}`}
            actions={
                <>
                    <StatusBadge
                        status={report.status}
                        label={report.status_label}
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setPreviewOpen(true)}
                    >
                        Pratinjau
                    </Button>
                    <Button asChild variant="ghost" size="sm">
                        <Link href={`/reports/${report.id}`}>Lihat Detail</Link>
                    </Button>
                </>
            }
        >
            {report.open_notes_count > 0 ? (
                <Alert>
                    <AlertTriangle className="size-4" />
                    <AlertTitle>
                        {report.open_notes_count} catatan revisi terbuka
                    </AlertTitle>
                    <AlertDescription>
                        Setiap catatan wajib ditanggapi pada versi ini sebelum
                        laporan dikirim ulang. Buka{' '}
                        <Link
                            href={`/reports/${report.id}`}
                            className="font-medium underline"
                        >
                            halaman detail
                        </Link>{' '}
                        untuk membaca dan menanggapi catatan.
                    </AlertDescription>
                </Alert>
            ) : null}

            {conflict ? (
                <Alert variant="destructive">
                    <AlertTriangle className="size-4" />
                    <AlertTitle>Penyimpanan bentrok</AlertTitle>
                    <AlertDescription>
                        {conflict} Isian Anda di layar ini masih utuh. Catat
                        perubahan Anda, muat ulang halaman, lalu terapkan
                        kembali.
                    </AlertDescription>
                </Alert>
            ) : null}

            <nav aria-label="Tahap formulir">
                <ol className="grid grid-cols-2 gap-2 lg:grid-cols-4">
                    {STEPS.map((item, index) => {
                        const active = step === item.key;
                        const hasError = stepHasError(item.key);
                        const completed = index < stepIndex && !hasError;

                        return (
                            <li key={item.key}>
                                <button
                                    type="button"
                                    aria-current={active ? 'step' : undefined}
                                    onClick={() => setStep(item.key)}
                                    className={cn(
                                        'flex min-h-14 w-full items-center gap-3 rounded-md border bg-card px-3 py-2 text-left text-sm transition-colors',
                                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                                        active &&
                                            'border-primary bg-primary/5 text-primary',
                                        !active &&
                                            'hover:border-primary/40 hover:bg-accent/40',
                                        hasError &&
                                            'border-destructive/60 text-destructive',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                                            active &&
                                                'border-primary bg-primary text-primary-foreground',
                                            completed &&
                                                'border-primary/25 bg-primary/10 text-primary',
                                            hasError &&
                                                'border-destructive/25 bg-destructive/10 text-destructive',
                                        )}
                                    >
                                        {hasError ? (
                                            <AlertCircle className="size-4" />
                                        ) : completed ? (
                                            <Check className="size-4" />
                                        ) : (
                                            index + 1
                                        )}
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block text-xs text-muted-foreground">
                                            Tahap {index + 1}
                                        </span>
                                        <span className="block leading-tight font-medium">
                                            {item.label}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
            </nav>

            <form onSubmit={saveDraft} className="space-y-6">
                {step === 'pengawas' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                I. Data Pengawas Pemilu
                            </CardTitle>
                            <CardDescription>
                                Nomor LHP diinput manual sesuai format resmi
                                instansi Anda; aplikasi tidak membuat nomor
                                otomatis.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                id="report_number"
                                label="Nomor LHP"
                                required
                                maxLength={MAX_LENGTHS.report_number}
                                value={data.report_number}
                                error={errors.report_number}
                                onChange={(v) => setData('report_number', v)}
                            />
                            <TextField
                                id="supervisor_name"
                                label="Nama pelaksana tugas pengawasan"
                                required
                                maxLength={MAX_LENGTHS.supervisor_name}
                                value={data.supervisor_name}
                                error={errors.supervisor_name}
                                onChange={(v) => setData('supervisor_name', v)}
                            />
                            <TextField
                                id="supervisor_position"
                                label="Jabatan"
                                required
                                maxLength={MAX_LENGTHS.supervisor_position}
                                value={data.supervisor_position}
                                error={errors.supervisor_position}
                                onChange={(v) =>
                                    setData('supervisor_position', v)
                                }
                            />
                            <TextField
                                id="assignment_number"
                                label="Nomor surat perintah tugas"
                                required
                                maxLength={MAX_LENGTHS.assignment_number}
                                value={data.assignment_number}
                                error={errors.assignment_number}
                                onChange={(v) =>
                                    setData('assignment_number', v)
                                }
                            />
                            <TextField
                                id="assignment_date"
                                label="Tanggal surat perintah tugas"
                                type="date"
                                required
                                value={data.assignment_date}
                                error={errors.assignment_date}
                                onChange={(v) => setData('assignment_date', v)}
                            />
                            <TextAreaField
                                id="supervisor_address"
                                label="Alamat"
                                required
                                rows={3}
                                maxLength={MAX_LENGTHS.supervisor_address}
                                value={data.supervisor_address}
                                error={errors.supervisor_address}
                                onChange={(v) =>
                                    setData('supervisor_address', v)
                                }
                            />
                        </CardContent>
                    </Card>
                ) : null}

                {step === 'kegiatan' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                II. Kegiatan Pengawasan
                            </CardTitle>
                            <CardDescription>
                                Tanggal selesai dan jam bersifat opsional.
                                Tanggal selesai tidak boleh sebelum tanggal
                                mulai.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                id="activity_name"
                                label="Kegiatan"
                                required
                                className="sm:col-span-2"
                                maxLength={MAX_LENGTHS.activity_name}
                                value={data.activity_name}
                                error={errors.activity_name}
                                onChange={(v) => setData('activity_name', v)}
                            />
                            <TextAreaField
                                id="activity_form"
                                label="Bentuk"
                                required
                                rows={3}
                                maxLength={MAX_LENGTHS.activity_form}
                                value={data.activity_form}
                                error={errors.activity_form}
                                onChange={(v) => setData('activity_form', v)}
                            />
                            <TextAreaField
                                id="activity_purpose"
                                label="Tujuan"
                                required
                                rows={3}
                                maxLength={MAX_LENGTHS.activity_purpose}
                                value={data.activity_purpose}
                                error={errors.activity_purpose}
                                onChange={(v) => setData('activity_purpose', v)}
                            />
                            <TextAreaField
                                id="activity_target"
                                label="Sasaran"
                                required
                                rows={3}
                                maxLength={MAX_LENGTHS.activity_target}
                                value={data.activity_target}
                                error={errors.activity_target}
                                onChange={(v) => setData('activity_target', v)}
                            />
                            <TextAreaField
                                id="activity_location"
                                label="Tempat"
                                required
                                rows={3}
                                maxLength={MAX_LENGTHS.activity_location}
                                value={data.activity_location}
                                error={errors.activity_location}
                                onChange={(v) =>
                                    setData('activity_location', v)
                                }
                            />
                            <TextField
                                id="activity_start_date"
                                label="Tanggal mulai"
                                type="date"
                                required
                                value={data.activity_start_date}
                                error={errors.activity_start_date}
                                onChange={(v) =>
                                    setData('activity_start_date', v)
                                }
                            />
                            <TextField
                                id="activity_end_date"
                                label="Tanggal selesai (opsional)"
                                type="date"
                                value={data.activity_end_date}
                                error={errors.activity_end_date}
                                onChange={(v) =>
                                    setData('activity_end_date', v)
                                }
                            />
                            <TextField
                                id="activity_start_time"
                                label="Jam mulai (opsional)"
                                type="time"
                                value={data.activity_start_time}
                                error={errors.activity_start_time}
                                onChange={(v) =>
                                    setData('activity_start_time', v)
                                }
                            />
                            <TextField
                                id="activity_end_time"
                                label="Jam selesai (opsional)"
                                type="time"
                                value={data.activity_end_time}
                                error={errors.activity_end_time}
                                onChange={(v) =>
                                    setData('activity_end_time', v)
                                }
                            />
                        </CardContent>
                    </Card>
                ) : null}

                {step === 'hasil' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                III. Hasil Pengawasan
                            </CardTitle>
                            <CardDescription>
                                Teks biasa, bukan format kaya. Paragraf
                                dipertahankan apa adanya pada PDF.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <TextAreaField
                                id="findings"
                                label="Uraian singkat hasil pengawasan"
                                required
                                rows={16}
                                maxLength={MAX_LENGTHS.findings}
                                value={data.findings}
                                error={errors.findings}
                                onChange={(v) => setData('findings', v)}
                            />
                        </CardContent>
                    </Card>
                ) : null}

                {step === 'pengesahan' ? (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Pengesahan
                                </CardTitle>
                                <CardDescription>
                                    Nama penandatangan disimpan sebagai snapshot
                                    versi ini dan tidak mengikuti perubahan
                                    profil akun. Persetujuan aplikasi bukan
                                    tanda tangan elektronik.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    id="signing_place"
                                    label="Tempat penandatanganan"
                                    required
                                    maxLength={MAX_LENGTHS.signing_place}
                                    value={data.signing_place}
                                    error={errors.signing_place}
                                    onChange={(v) =>
                                        setData('signing_place', v)
                                    }
                                />
                                <TextField
                                    id="signing_date"
                                    label="Tanggal penandatanganan"
                                    type="date"
                                    required
                                    value={data.signing_date}
                                    error={errors.signing_date}
                                    onChange={(v) => setData('signing_date', v)}
                                />
                                <TextField
                                    id="signer_name"
                                    label="Nama penandatangan"
                                    required
                                    maxLength={MAX_LENGTHS.signer_name}
                                    value={data.signer_name}
                                    error={errors.signer_name}
                                    onChange={(v) => setData('signer_name', v)}
                                />
                                <SelectField
                                    id="signer_capacity"
                                    label="Kedudukan penandatangan"
                                    required
                                    options={signer_capacities}
                                    value={data.signer_capacity}
                                    error={errors.signer_capacity}
                                    onChange={(v) =>
                                        setData('signer_capacity', v)
                                    }
                                />
                            </CardContent>
                        </Card>

                        <AttachmentPanel
                            report={report}
                            version={version}
                            limits={attachment_limits}
                        />
                    </>
                ) : null}

                <div className="safe-bottom sticky bottom-0 -mx-4 border-t bg-background/95 px-4 pt-3 backdrop-blur-sm sm:-mx-6 sm:px-6">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex gap-2">
                            {stepIndex > 0 ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="flex-1 sm:flex-none"
                                    onClick={() =>
                                        setStep(STEPS[stepIndex - 1].key)
                                    }
                                >
                                    <ChevronLeft className="size-4" />
                                    Sebelumnya
                                </Button>
                            ) : null}
                            {stepIndex < STEPS.length - 1 ? (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    className="flex-1 sm:flex-none"
                                    onClick={() =>
                                        setStep(STEPS[stepIndex + 1].key)
                                    }
                                >
                                    Berikutnya
                                    <ChevronRight className="size-4" />
                                </Button>
                            ) : null}
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1 sm:flex-none"
                            >
                                <Save className="size-4" />
                                {processing ? 'Menyimpan...' : 'Simpan Draf'}
                            </Button>
                            {stepIndex === STEPS.length - 1 ? (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    className="flex-1 sm:flex-none"
                                    disabled={submitting || isDirty}
                                    onClick={() => setConfirmOpen(true)}
                                >
                                    <Send className="size-4" />
                                    Kirim
                                </Button>
                            ) : null}
                        </div>
                    </div>
                    {isDirty ? (
                        <p className="mt-2 text-xs text-muted-foreground">
                            Ada perubahan belum disimpan. Simpan draf sebelum
                            mengirim laporan.
                        </p>
                    ) : null}
                </div>
            </form>

            <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Pratinjau Formulir Model A</DialogTitle>
                        <DialogDescription>
                            Pratinjau memakai isian yang tersimpan pada versi
                            kerja, bukan perubahan yang belum disimpan.
                        </DialogDescription>
                    </DialogHeader>
                    <ReportPreview report={report} version={version} />
                </DialogContent>
            </Dialog>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kirim laporan ini?</DialogTitle>
                        <DialogDescription>
                            Setelah dikirim, versi {version.version_number}{' '}
                            tidak dapat diubah lagi, termasuk lampirannya.
                            Revisi hanya mungkin bila Admin Kabupaten
                            mengembalikan laporan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={submitReport} disabled={submitting}>
                            Ya, kirim sekarang
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
