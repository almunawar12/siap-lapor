<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\ReportVersion;
use App\Models\User;
use App\Support\ReportPayload;
use Illuminate\Support\Facades\DB;

class CreateReport
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Kecamatan ditetapkan dari akun, bukan dari payload. Laporan, versi 1, dan
     * pointer current dibuat dalam satu transaksi (ARCHITECTURE.md bagian 4).
     */
    public function handle(User $actor, ReportingPeriod $period): Report
    {
        return DB::transaction(function () use ($actor, $period): Report {
            $report = new Report;
            $report->district_id = (int) $actor->district_id;
            $report->reporting_period_id = $period->id;
            $report->created_by = $actor->id;
            $report->status = ReportStatus::Draft;
            $report->lock_version = 0;
            $report->save();

            $version = new ReportVersion;
            $version->report_id = $report->id;
            $version->version_number = 1;
            $version->payload = ReportPayload::empty();
            $version->schema_version = 1;
            $version->created_by = $actor->id;
            $version->save();

            $report->current_version_id = $version->id;
            $report->save();

            $this->log->handle(
                report: $report,
                actor: $actor,
                event: 'report_created',
                toStatus: ReportStatus::Draft,
                version: $version,
            );

            return $report;
        });
    }
}
