<?php

namespace App\Actions\Reports;

use App\Enums\AttachmentCategory;
use App\Exceptions\StaleReportException;
use App\Models\File as StoredFile;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Models\VersionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachFile
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Byte upload tidak atomik dengan transaksi PostgreSQL, jadi file ditulis
     * lebih dulu ke lokasi privat, lalu izin/status/lock diperiksa di dalam
     * transaksi. Bila transaksi gagal, file yang belum direferensikan dihapus
     * (ARCHITECTURE.md bagian 5).
     *
     * @throws StaleReportException
     * @throws ValidationException
     */
    public function handle(
        User $actor,
        Report $report,
        UploadedFile $upload,
        AttachmentCategory $category,
        ?string $description,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): VersionAttachment {
        $disk = config('siaplapor.attachments.disk');
        $path = sprintf(
            'attachments/%s/%s.%s',
            now()->format('Y/m'),
            Str::uuid()->toString(),
            $this->safeExtension($upload),
        );

        Storage::disk($disk)->putFileAs(dirname($path), $upload, basename($path));

        try {
            return DB::transaction(function () use (
                $actor,
                $report,
                $upload,
                $category,
                $description,
                $expectedVersionId,
                $expectedLockVersion,
                $disk,
                $path
            ): VersionAttachment {
                /** @var Report $locked */
                $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

                if ($locked->lock_version !== $expectedLockVersion) {
                    throw new StaleReportException;
                }

                if (! $locked->isEditable()) {
                    throw new StaleReportException(
                        'Lampiran tidak dapat diubah pada status '.$locked->status->label().'.'
                    );
                }

                if ($locked->current_version_id !== $expectedVersionId) {
                    throw new StaleReportException(
                        'Versi kerja sudah berganti. Muat ulang halaman sebelum mengunggah.'
                    );
                }

                /** @var ReportVersion $version */
                $version = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

                if (! $version->isEditable()) {
                    throw new StaleReportException('Versi ini sudah dikirim dan lampirannya tidak dapat diubah.');
                }

                $maxFiles = (int) config('siaplapor.attachments.max_files_per_version');

                if ($version->attachments()->count() >= $maxFiles) {
                    throw ValidationException::withMessages([
                        'file' => "Jumlah lampiran aktif maksimal {$maxFiles} berkas per versi.",
                    ]);
                }

                $file = StoredFile::create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $this->safeName($upload),
                    'mime_type' => (string) $upload->getMimeType(),
                    'size_bytes' => (int) $upload->getSize(),
                    'sha256' => (string) hash_file('sha256', $upload->getRealPath()),
                    'uploaded_by' => $actor->id,
                ]);

                $attachment = VersionAttachment::create([
                    'report_version_id' => $version->id,
                    'file_id' => $file->id,
                    'category' => $category->value,
                    'description' => $description,
                ]);

                $locked->lock_version = $locked->lock_version + 1;
                $locked->save();

                $this->log->handle(
                    report: $locked,
                    actor: $actor,
                    event: 'attachment_added',
                    version: $version,
                    metadata: [
                        'attachment_id' => $attachment->id,
                        'category' => $category->value,
                        'size_bytes' => $file->size_bytes,
                    ],
                );

                $report->refresh();

                return $attachment;
            });
        } catch (\Throwable $exception) {
            // File baru belum direferensikan baris apa pun, jadi aman dihapus.
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    protected function safeExtension(UploadedFile $upload): string
    {
        $allowed = (array) config('siaplapor.attachments.extensions');
        $extension = mb_strtolower((string) $upload->guessExtension());

        return in_array($extension, $allowed, true) ? $extension : 'bin';
    }

    protected function safeName(UploadedFile $upload): string
    {
        $name = $upload->getClientOriginalName();

        return mb_substr(preg_replace('/[^\p{L}\p{N}\.\-_ ]+/u', '_', $name) ?? 'lampiran', 0, 255);
    }
}
