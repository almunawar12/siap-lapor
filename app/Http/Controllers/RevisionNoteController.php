<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ManageNotes;
use App\Enums\NoteStatus;
use App\Http\Requests\Reports\NoteDecisionRequest;
use App\Http\Requests\Reports\StoreNoteRequest;
use App\Http\Requests\Reports\StoreResponseRequest;
use App\Models\Report;
use App\Models\RevisionNote;
use Illuminate\Http\RedirectResponse;

class RevisionNoteController extends Controller
{
    public function __construct(private readonly ManageNotes $notes) {}

    public function store(StoreNoteRequest $request, Report $report): RedirectResponse
    {
        $this->authorize('review', $report);

        $this->notes->addNote(
            actor: $request->user(),
            report: $report,
            body: (string) $request->validated('body'),
            fieldKey: $request->fieldKey(),
            attachmentId: $request->attachmentId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Catatan revisi ditambahkan.');
    }

    public function respond(
        StoreResponseRequest $request,
        Report $report,
        RevisionNote $note,
    ): RedirectResponse {
        $this->assertBelongsToReport($report, $note);
        $this->authorize('respond', $report);

        $this->notes->addResponse(
            actor: $request->user(),
            report: $report,
            note: $note,
            body: (string) $request->validated('body'),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with(
            'success',
            'Tanggapan tersimpan. Catatan tetap terbuka sampai Admin Kabupaten menandainya selesai.'
        );
    }

    public function resolve(NoteDecisionRequest $request, Report $report, RevisionNote $note): RedirectResponse
    {
        return $this->decide($request, $report, $note, NoteStatus::Resolved, 'Catatan ditandai selesai.');
    }

    public function reopen(NoteDecisionRequest $request, Report $report, RevisionNote $note): RedirectResponse
    {
        return $this->decide($request, $report, $note, NoteStatus::Open, 'Catatan dibuka kembali.');
    }

    protected function decide(
        NoteDecisionRequest $request,
        Report $report,
        RevisionNote $note,
        NoteStatus $target,
        string $message,
    ): RedirectResponse {
        $this->assertBelongsToReport($report, $note);
        $this->authorize('review', $report);

        $this->notes->decideNote(
            actor: $request->user(),
            report: $report,
            note: $note,
            target: $target,
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', $message);
    }

    /**
     * Catatan harus benar-benar milik laporan pada URL, melalui review → versi.
     */
    protected function assertBelongsToReport(Report $report, RevisionNote $note): void
    {
        $note->loadMissing('review.version');

        abort_unless($note->review->version->report_id === $report->id, 404);
    }
}
