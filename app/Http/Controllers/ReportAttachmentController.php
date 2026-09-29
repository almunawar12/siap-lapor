<?php

namespace App\Http\Controllers;

use App\Actions\Reports\AttachFile;
use App\Actions\Reports\DetachFile;
use App\Http\Requests\Reports\StoreAttachmentRequest;
use App\Models\Report;
use App\Models\VersionAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Report $report, AttachFile $action): RedirectResponse
    {
        $this->authorize('manageAttachments', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            upload: $request->file('file'),
            category: $request->category(),
            description: $request->validated('description'),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Lampiran berhasil diunggah.');
    }

    public function destroy(
        Request $request,
        Report $report,
        VersionAttachment $attachment,
        DetachFile $action,
    ): RedirectResponse {
        $this->assertBelongsToReport($report, $attachment);
        $this->authorize('delete', $attachment);

        $action->handle(
            actor: $request->user(),
            report: $report,
            attachment: $attachment,
            expectedLockVersion: (int) $request->integer('lock_version'),
        );

        return back()->with('success', 'Lampiran dilepas dari versi kerja.');
    }

    /**
     * Unduhan selalu melewati policy laporan. Tidak ada URL publik, tidak ada
     * storage:link, dan berkas tidak pernah dirender sebagai markup aplikasi.
     */
    public function download(Report $report, VersionAttachment $attachment): StreamedResponse
    {
        $this->assertBelongsToReport($report, $attachment);
        $this->authorize('view', $attachment);

        $attachment->loadMissing('file');
        $file = $attachment->file;

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ],
        );
    }

    /**
     * Route binding nested harus membuktikan relasi induk-anak; ID yang sulit
     * ditebak bukan pengganti otorisasi (ARCHITECTURE.md bagian 6).
     */
    protected function assertBelongsToReport(Report $report, VersionAttachment $attachment): void
    {
        $attachment->loadMissing('version');

        abort_unless($attachment->version->report_id === $report->id, 404);
    }
}
