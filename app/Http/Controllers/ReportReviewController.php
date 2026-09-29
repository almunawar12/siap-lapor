<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ApproveReport;
use App\Actions\Reports\ReopenApproved;
use App\Actions\Reports\ReturnForRevision;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\TakeoverReview;
use App\Http\Requests\Reports\ReasonRequiredRequest;
use App\Http\Requests\Reports\ReviewActionRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;

/**
 * Tidak ada endpoint generik untuk mengubah status: setiap transisi punya route
 * dan action sendiri (ARCHITECTURE.md bagian 7).
 */
class ReportReviewController extends Controller
{
    public function start(ReviewActionRequest $request, Report $report, StartReview $action): RedirectResponse
    {
        $this->authorize('review', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Pemeriksaan dimulai. Anda menjadi pemeriksa aktif laporan ini.');
    }

    public function takeover(ReasonRequiredRequest $request, Report $report, TakeoverReview $action): RedirectResponse
    {
        $this->authorize('review', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            reason: $request->reason(),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Pemeriksaan diambil alih. Alasan tercatat pada riwayat.');
    }

    public function returnForRevision(
        ReviewActionRequest $request,
        Report $report,
        ReturnForRevision $action,
    ): RedirectResponse {
        $this->authorize('review', $report);

        $working = $action->handle(
            actor: $request->user(),
            report: $report,
            generalNote: $request->generalNote(),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with(
            'success',
            "Laporan dikembalikan untuk revisi. Versi kerja baru: versi {$working->version_number}."
        );
    }

    public function approve(ReviewActionRequest $request, Report $report, ApproveReport $action): RedirectResponse
    {
        $this->authorize('review', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            generalNote: $request->generalNote(),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Laporan disetujui.');
    }

    public function reopen(ReasonRequiredRequest $request, Report $report, ReopenApproved $action): RedirectResponse
    {
        $this->authorize('reopen', $report);

        $working = $action->handle(
            actor: $request->user(),
            report: $report,
            reason: $request->reason(),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with(
            'success',
            "Laporan dibuka kembali sebagai versi {$working->version_number}. Persetujuan lama tetap tersimpan pada riwayat."
        );
    }
}
