<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;

/**
 * Log append-only. Jangan mencatat password, token, byte file, atau seluruh
 * uraian hasil pengawasan (AGENTS.md).
 */
class RecordActivity
{
    /** @param array<string, mixed> $metadata */
    public function handle(
        Report $report,
        ?User $actor,
        string $event,
        ?ReportStatus $fromStatus = null,
        ?ReportStatus $toStatus = null,
        ?ReportVersion $version = null,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::create([
            'report_id' => $report->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus?->value,
            'report_version_id' => $version?->id,
            'metadata' => $metadata,
        ]);
    }
}
