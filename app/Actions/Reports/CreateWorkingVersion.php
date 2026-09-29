<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Models\VersionAttachment;

/**
 * Menyalin versi saat ini menjadi versi kerja N+1. Dipakai oleh
 * ReturnForRevision dan ReopenApproved.
 *
 * Link lampiran disalin tanpa menyalin byte: baris `files` bersifat immutable
 * dan dapat dirujuk beberapa versi sekaligus (ARCHITECTURE.md bagian 5).
 *
 * Harus dipanggil di dalam transaksi yang sudah mengunci baris laporan.
 */
class CreateWorkingVersion
{
    public function handle(User $actor, Report $report, ReportVersion $source): ReportVersion
    {
        $next = new ReportVersion;
        $next->report_id = $report->id;
        $next->version_number = $source->version_number + 1;
        $next->payload = $source->payload;
        $next->schema_version = $source->schema_version;
        $next->created_by = $actor->id;
        $next->save();

        $source->loadMissing('attachments');

        foreach ($source->attachments as $attachment) {
            VersionAttachment::create([
                'report_version_id' => $next->id,
                'file_id' => $attachment->file_id,
                'category' => $attachment->category->value,
                'description' => $attachment->description,
            ]);
        }

        return $next;
    }
}
