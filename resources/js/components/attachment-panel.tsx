import { FieldError } from '@/components/field-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatUkuran } from '@/lib/format';
import type { ReportDetail, ReportVersion } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Paperclip, Trash2 } from 'lucide-react';
import { useRef, type FormEvent } from 'react';

const CATEGORIES = [
    { value: 'surat_tugas', label: 'Surat Perintah Tugas' },
    { value: 'dokumentasi', label: 'Dokumentasi Kegiatan' },
    { value: 'lainnya', label: 'Lampiran Lain' },
];

export function AttachmentPanel({
    report,
    version,
    limits,
}: {
    report: ReportDetail;
    version: ReportVersion;
    limits: { max_files: number; max_size_kb: number; extensions: string[] };
}) {
    const fileInput = useRef<HTMLInputElement>(null);

    const form = useForm<{
        current_version_id: number;
        lock_version: number;
        category: string;
        description: string;
        file: File | null;
    }>({
        current_version_id: version.id,
        lock_version: report.lock_version,
        category: 'dokumentasi',
        description: '',
        file: null,
    });

    const full = version.attachments.length >= limits.max_files;

    function upload(event: FormEvent) {
        event.preventDefault();
        form.transform((values) => ({
            ...values,
            lock_version: report.lock_version,
            current_version_id: version.id,
        }));
        form.post(`/reports/${report.id}/attachments`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('description', 'file');
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    }

    function detach(attachmentId: number) {
        router.delete(`/reports/${report.id}/attachments/${attachmentId}`, {
            data: { lock_version: report.lock_version },
            preserveScroll: true,
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Lampiran Pendukung</CardTitle>
                <CardDescription>
                    Lampiran bersifat opsional. Maksimal {limits.max_files}{' '}
                    berkas per versi, {(limits.max_size_kb / 1024).toFixed(0)}{' '}
                    MB per berkas, format {limits.extensions.join(', ')}. Berkas
                    disimpan privat dan hanya dapat diunduh lewat aplikasi.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {report.capabilities.manage_attachments ? (
                    <form
                        onSubmit={upload}
                        className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="attachment_category">
                                Kategori
                            </Label>
                            <select
                                id="attachment_category"
                                value={form.data.category}
                                onChange={(event) =>
                                    form.setData('category', event.target.value)
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {CATEGORIES.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <FieldError message={form.errors.category} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="attachment_description">
                                Keterangan (opsional)
                            </Label>
                            <Input
                                id="attachment_description"
                                maxLength={500}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                            <FieldError message={form.errors.description} />
                        </div>

                        <div className="grid gap-2 sm:col-span-3">
                            <Label htmlFor="attachment_file">Berkas</Label>
                            <Input
                                id="attachment_file"
                                ref={fileInput}
                                type="file"
                                accept={limits.extensions
                                    .map((ext) => `.${ext}`)
                                    .join(',')}
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <FieldError message={form.errors.file} />
                        </div>

                        <div className="sm:col-span-3">
                            <Button
                                type="submit"
                                variant="secondary"
                                disabled={
                                    form.processing ||
                                    full ||
                                    form.data.file === null
                                }
                            >
                                <Paperclip className="size-4" />
                                {form.processing
                                    ? `Mengunggah ${form.progress?.percentage ?? 0}%`
                                    : 'Unggah Lampiran'}
                            </Button>
                            {full ? (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Batas jumlah lampiran versi ini sudah
                                    tercapai.
                                </p>
                            ) : null}
                        </div>
                    </form>
                ) : null}

                {version.attachments.length === 0 ? (
                    <p className="rounded-lg border border-dashed px-6 py-8 text-center text-sm text-muted-foreground">
                        Belum ada lampiran pada versi ini.
                    </p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {version.attachments.map((attachment) => (
                            <li
                                key={attachment.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {attachment.original_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {attachment.category_label} ·{' '}
                                        {formatUkuran(attachment.size_bytes)}
                                        {attachment.description
                                            ? ` · ${attachment.description}`
                                            : ''}
                                    </p>
                                </div>
                                <Button asChild variant="outline" size="sm">
                                    <a
                                        href={`/reports/${report.id}/attachments/${attachment.id}/download`}
                                    >
                                        Unduh
                                    </a>
                                </Button>
                                {report.capabilities.manage_attachments ? (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => detach(attachment.id)}
                                    >
                                        <Trash2 className="size-4" />
                                        Lepas
                                    </Button>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
