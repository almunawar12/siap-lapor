import { FieldError } from '@/components/field-error';
import { OptionSelect } from '@/components/option-select';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { formatWaktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReportDetail, RevisionNote } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    MessageSquarePlus,
    RotateCcw,
} from 'lucide-react';
import { useState, type FormEvent } from 'react';

const FIELD_OPTIONS: { value: string; label: string }[] = [
    { value: '', label: 'Catatan umum (tanpa field tertentu)' },
    { value: 'report_number', label: 'Nomor LHP' },
    { value: 'supervisor_name', label: 'Nama pelaksana tugas pengawasan' },
    { value: 'supervisor_position', label: 'Jabatan' },
    { value: 'assignment_number', label: 'Nomor surat perintah tugas' },
    { value: 'assignment_date', label: 'Tanggal surat perintah tugas' },
    { value: 'supervisor_address', label: 'Alamat' },
    { value: 'activity_name', label: 'Kegiatan' },
    { value: 'activity_form', label: 'Bentuk' },
    { value: 'activity_purpose', label: 'Tujuan' },
    { value: 'activity_target', label: 'Sasaran' },
    { value: 'activity_start_date', label: 'Tanggal mulai' },
    { value: 'activity_end_date', label: 'Tanggal selesai' },
    { value: 'activity_start_time', label: 'Jam mulai' },
    { value: 'activity_end_time', label: 'Jam selesai' },
    { value: 'activity_location', label: 'Tempat' },
    { value: 'findings', label: 'Uraian singkat hasil pengawasan' },
    { value: 'signing_place', label: 'Tempat penandatanganan' },
    { value: 'signing_date', label: 'Tanggal penandatanganan' },
    { value: 'signer_name', label: 'Nama penandatangan' },
    { value: 'signer_capacity', label: 'Kedudukan penandatangan' },
];

function NoteTarget({ note }: { note: RevisionNote }) {
    if (note.field_label) {
        return <Badge variant="outline">Field: {note.field_label}</Badge>;
    }

    if (note.attachment_name) {
        return (
            <Badge variant="outline">Lampiran: {note.attachment_name}</Badge>
        );
    }

    return <Badge variant="outline">Catatan umum</Badge>;
}

function ResponseForm({
    report,
    note,
}: {
    report: ReportDetail;
    note: RevisionNote;
}) {
    const form = useForm({ lock_version: report.lock_version, body: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            lock_version: report.lock_version,
        }));
        form.post(`/reports/${report.id}/notes/${note.id}/responses`, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    }

    return (
        <form onSubmit={submit} className="grid gap-2">
            <Label htmlFor={`response-${note.id}`} className="text-xs">
                Tulis tanggapan
            </Label>
            <textarea
                id={`response-${note.id}`}
                rows={3}
                maxLength={5000}
                value={form.data.body}
                aria-invalid={Boolean(form.errors.body)}
                onChange={(event) => form.setData('body', event.target.value)}
                className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            />
            <FieldError message={form.errors.body} />
            <div>
                <Button
                    type="submit"
                    size="sm"
                    variant="secondary"
                    disabled={form.processing || form.data.body.trim() === ''}
                >
                    Kirim Tanggapan
                </Button>
            </div>
        </form>
    );
}

function NoteCard({
    report,
    note,
}: {
    report: ReportDetail;
    note: RevisionNote;
}) {
    const open = note.status === 'open';
    const answeredOnThisVersion = note.responses.some(
        (response) => response.version_id === report.current_version?.id,
    );

    return (
        <li
            className={cn(
                'space-y-3 rounded-md border p-3',
                open ? 'border-orange-300 dark:border-orange-900' : '',
            )}
        >
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant={open ? 'default' : 'secondary'}>
                    {note.status_label}
                </Badge>
                <NoteTarget note={note} />
                <span className="text-xs text-muted-foreground">
                    Siklus versi {note.version_number} ·{' '}
                    {formatWaktu(note.created_at)}
                </span>
            </div>

            <p className="text-sm whitespace-pre-line">{note.body}</p>

            {note.resolved_by ? (
                <p className="text-xs text-muted-foreground">
                    Ditandai selesai oleh {note.resolved_by} ·{' '}
                    {formatWaktu(note.resolved_at)}
                </p>
            ) : null}

            {note.responses.length > 0 ? (
                <ul className="space-y-2 border-l-2 pl-3">
                    {note.responses.map((response) => (
                        <li key={response.id}>
                            <p className="text-xs text-muted-foreground">
                                {response.author_name} ·{' '}
                                {formatWaktu(response.created_at)}
                            </p>
                            <p className="text-sm whitespace-pre-line">
                                {response.body}
                            </p>
                        </li>
                    ))}
                </ul>
            ) : null}

            {open && report.capabilities.respond ? (
                <>
                    {answeredOnThisVersion ? (
                        <p className="text-xs text-muted-foreground">
                            Sudah ditanggapi pada versi kerja ini. Catatan tetap
                            terbuka sampai Admin Kabupaten menandainya selesai.
                        </p>
                    ) : (
                        <p className="text-xs text-orange-700 dark:text-orange-300">
                            Belum ditanggapi pada versi ini. Tanggapan wajib
                            sebelum laporan dikirim ulang.
                        </p>
                    )}
                    <ResponseForm report={report} note={note} />
                </>
            ) : null}

            {report.capabilities.decide_note ? (
                <Button
                    size="sm"
                    variant={open ? 'secondary' : 'outline'}
                    onClick={() =>
                        router.post(
                            `/reports/${report.id}/notes/${note.id}/${open ? 'resolve' : 'reopen'}`,
                            { lock_version: report.lock_version },
                            { preserveScroll: true },
                        )
                    }
                >
                    {open ? (
                        <>
                            <CheckCircle2 className="size-4" />
                            Tandai Selesai
                        </>
                    ) : (
                        <>
                            <RotateCcw className="size-4" />
                            Buka Kembali
                        </>
                    )}
                </Button>
            ) : null}
        </li>
    );
}

function AddNoteForm({ report }: { report: ReportDetail }) {
    const form = useForm({
        lock_version: report.lock_version,
        body: '',
        field_key: '',
        attachment_id: '',
    });

    const attachments = report.current_version?.attachments ?? [];

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            lock_version: report.lock_version,
            field_key: values.field_key === '' ? null : values.field_key,
            attachment_id:
                values.attachment_id === '' ? null : values.attachment_id,
        }));
        form.post(`/reports/${report.id}/notes`, {
            preserveScroll: true,
            onSuccess: () => form.reset('body', 'field_key', 'attachment_id'),
        });
    }

    return (
        <form onSubmit={submit} className="grid gap-3">
            <div className="grid gap-2">
                <Label htmlFor="note_field">Kaitkan dengan</Label>
                <OptionSelect
                    id="note_field"
                    value={form.data.field_key}
                    onValueChange={(value) => {
                        form.setData('field_key', value);
                        if (value !== '') {
                            form.setData('attachment_id', '');
                        }
                    }}
                    options={FIELD_OPTIONS}
                />
                <FieldError message={form.errors.field_key} />
            </div>

            {attachments.length > 0 && form.data.field_key === '' ? (
                <div className="grid gap-2">
                    <Label htmlFor="note_attachment">
                        Atau lampiran tertentu (opsional)
                    </Label>
                    <OptionSelect
                        id="note_attachment"
                        value={form.data.attachment_id}
                        onValueChange={(value) =>
                            form.setData('attachment_id', value)
                        }
                        options={[
                            { value: '', label: 'Tidak terkait lampiran' },
                            ...attachments.map((attachment) => ({
                                value: attachment.id,
                                label: attachment.original_name,
                            })),
                        ]}
                    />
                    <FieldError message={form.errors.attachment_id} />
                </div>
            ) : null}

            <div className="grid gap-2">
                <Label htmlFor="note_body">Isi catatan</Label>
                <textarea
                    id="note_body"
                    rows={3}
                    maxLength={5000}
                    value={form.data.body}
                    aria-invalid={Boolean(form.errors.body)}
                    onChange={(event) =>
                        form.setData('body', event.target.value)
                    }
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                />
                <FieldError message={form.errors.body} />
            </div>

            <div>
                <Button
                    type="submit"
                    size="sm"
                    disabled={form.processing || form.data.body.trim() === ''}
                >
                    <MessageSquarePlus className="size-4" />
                    Tambah Catatan
                </Button>
            </div>
        </form>
    );
}

/** Dialog yang mewajibkan alasan: ambil alih dan buka kembali. */
function ReasonDialog({
    open,
    onOpenChange,
    title,
    description,
    action,
    report,
    confirmLabel,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    title: string;
    description: string;
    action: string;
    report: ReportDetail;
    confirmLabel: string;
}) {
    const form = useForm({
        current_version_id: report.current_version?.id ?? 0,
        lock_version: report.lock_version,
        reason: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            current_version_id: report.current_version?.id ?? 0,
            lock_version: report.lock_version,
        }));
        form.post(action, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('reason');
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2 py-4">
                        <Label htmlFor="reason">Alasan (wajib)</Label>
                        <textarea
                            id="reason"
                            rows={3}
                            maxLength={2000}
                            value={form.data.reason}
                            aria-invalid={Boolean(form.errors.reason)}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                            className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        />
                        <FieldError message={form.errors.reason} />
                        <p className="text-xs text-muted-foreground">
                            Alasan tercatat permanen pada riwayat aktivitas.
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {confirmLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Dialog keputusan dengan catatan umum opsional: kembalikan dan setujui. */
function DecisionDialog({
    open,
    onOpenChange,
    title,
    description,
    action,
    report,
    confirmLabel,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    title: string;
    description: string;
    action: string;
    report: ReportDetail;
    confirmLabel: string;
}) {
    const form = useForm({
        current_version_id: report.current_version?.id ?? 0,
        lock_version: report.lock_version,
        general_note: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            current_version_id: report.current_version?.id ?? 0,
            lock_version: report.lock_version,
        }));
        form.post(action, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('general_note');
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2 py-4">
                        <Label htmlFor="general_note">
                            Catatan umum (opsional)
                        </Label>
                        <textarea
                            id="general_note"
                            rows={3}
                            maxLength={5000}
                            value={form.data.general_note}
                            onChange={(event) =>
                                form.setData('general_note', event.target.value)
                            }
                            className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        />
                        <FieldError message={form.errors.general_note} />
                        {/* Penolakan karena catatan terbuka tampil pada alert
                            di ReviewPanel, karena berasal dari error bag
                            bersama dan bukan field form ini. */}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {confirmLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function ReviewPanel({ report }: { report: ReportDetail }) {
    const pageErrors = usePage().props.errors;
    const [takeover, setTakeover] = useState(false);
    const [reopen, setReopen] = useState(false);
    const [returning, setReturning] = useState(false);
    const [approving, setApproving] = useState(false);

    const c = report.capabilities;
    const versionId = report.current_version?.id ?? 0;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    Pemeriksaan dan Catatan Revisi
                </CardTitle>
                <CardDescription>
                    {report.open_notes_count > 0
                        ? `${report.open_notes_count} catatan masih terbuka.`
                        : 'Tidak ada catatan terbuka.'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {pageErrors.conflict ? (
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Tindakan tidak dapat diproses</AlertTitle>
                        <AlertDescription>
                            {pageErrors.conflict}
                        </AlertDescription>
                    </Alert>
                ) : null}

                {pageErrors.notes ? (
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Keputusan ditolak</AlertTitle>
                        <AlertDescription>{pageErrors.notes}</AlertDescription>
                    </Alert>
                ) : null}

                {report.active_review ? (
                    <p className="text-sm">
                        Pemeriksa aktif:{' '}
                        <strong>{report.active_review.reviewer_name}</strong>
                        {report.active_review.is_mine ? ' (Anda)' : ''} · sejak{' '}
                        {formatWaktu(report.active_review.started_at)}
                    </p>
                ) : null}

                <div className="flex flex-wrap gap-2">
                    {c.start_review ? (
                        <Button
                            size="sm"
                            onClick={() =>
                                router.post(
                                    `/reports/${report.id}/review/start`,
                                    {
                                        current_version_id: versionId,
                                        lock_version: report.lock_version,
                                    },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Mulai Pemeriksaan
                        </Button>
                    ) : null}

                    {c.takeover_review ? (
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => setTakeover(true)}
                        >
                            Ambil Alih Pemeriksaan
                        </Button>
                    ) : null}

                    {c.return_for_revision ? (
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => setReturning(true)}
                        >
                            Kembalikan untuk Revisi
                        </Button>
                    ) : null}

                    {c.approve ? (
                        <Button size="sm" onClick={() => setApproving(true)}>
                            Setujui Laporan
                        </Button>
                    ) : null}

                    {c.reopen ? (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setReopen(true)}
                        >
                            Buka Kembali
                        </Button>
                    ) : null}
                </div>

                {c.add_note && report.open_notes_count === 0 ? (
                    <Alert>
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Belum ada catatan terbuka</AlertTitle>
                        <AlertDescription>
                            Pengembalian memerlukan setidaknya satu catatan yang
                            belum selesai. Tambahkan catatan di bawah.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {c.add_note ? (
                    <>
                        <Separator />
                        <AddNoteForm report={report} />
                    </>
                ) : null}

                <Separator />

                {report.notes.length === 0 ? (
                    <p className="rounded-lg border border-dashed px-6 py-8 text-center text-sm text-muted-foreground">
                        Belum ada catatan revisi pada laporan ini.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {report.notes.map((note) => (
                            <NoteCard
                                key={note.id}
                                report={report}
                                note={note}
                            />
                        ))}
                    </ul>
                )}

                {report.reviews.length > 0 ? (
                    <>
                        <Separator />
                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold">
                                Riwayat Siklus Pemeriksaan
                            </h3>
                            <ul className="space-y-2 text-sm">
                                {report.reviews.map((review) => (
                                    <li
                                        key={review.id}
                                        className="rounded-md border p-3"
                                    >
                                        <p>
                                            Versi {review.version_number} ·{' '}
                                            {review.reviewer_name} ·{' '}
                                            <Badge variant="secondary">
                                                {review.status_label}
                                            </Badge>
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Mulai{' '}
                                            {formatWaktu(review.started_at)}
                                            {review.decided_at
                                                ? ` · diputuskan ${formatWaktu(review.decided_at)}`
                                                : ''}
                                        </p>
                                        {review.general_note ? (
                                            <p className="mt-2 text-sm whitespace-pre-line">
                                                {review.general_note}
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </>
                ) : null}
            </CardContent>

            <ReasonDialog
                open={takeover}
                onOpenChange={setTakeover}
                title="Ambil alih pemeriksaan?"
                description={`Pemeriksaan saat ini dipegang ${report.active_review?.reviewer_name ?? '-'}. Alasan wajib dan tercatat.`}
                action={`/reports/${report.id}/review/takeover`}
                report={report}
                confirmLabel="Ambil Alih"
            />

            <ReasonDialog
                open={reopen}
                onOpenChange={setReopen}
                title="Buka kembali laporan yang sudah disetujui?"
                description="Laporan akan kembali ke status Perlu Revisi dan keluar dari hitungan disetujui. Persetujuan lama tetap tersimpan pada riwayat versi."
                action={`/reports/${report.id}/reopen`}
                report={report}
                confirmLabel="Buka Kembali"
            />

            <DecisionDialog
                open={returning}
                onOpenChange={setReturning}
                title="Kembalikan laporan untuk revisi?"
                description="Versi yang diperiksa tetap tersimpan permanen. Satu versi kerja baru akan dibuat untuk kecamatan."
                action={`/reports/${report.id}/review/return`}
                report={report}
                confirmLabel="Kembalikan"
            />

            <DecisionDialog
                open={approving}
                onOpenChange={setApproving}
                title="Setujui laporan ini?"
                description="Persetujuan aplikasi bukan tanda tangan elektronik. Pastikan seluruh catatan revisi sudah selesai."
                action={`/reports/${report.id}/review/approve`}
                report={report}
                confirmLabel="Setujui"
            />
        </Card>
    );
}
