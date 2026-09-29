<?php

namespace App\Http\Controllers;

use App\Actions\Reports\CreateReport;
use App\Actions\Reports\SaveDraft;
use App\Actions\Reports\SubmitReport;
use App\Enums\ReportStatus;
use App\Enums\SignerCapacity;
use App\Http\Requests\Reports\SaveDraftRequest;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Requests\Reports\SubmitReportRequest;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\ReportVersion;
use App\Support\ReportPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        $user = $request->user();
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status');
        $periodId = $request->integer('period');
        $districtId = $request->integer('district');

        $reports = Report::query()
            ->visibleTo($user)
            ->with(['district:id,code,name', 'period:id,name', 'currentVersion:id,report_id,version_number,payload'])
            // Pencarian nomor dan nama kegiatan. Nama kegiatan ada di payload
            // JSONB versi aktif; ILIKE biasa sudah cukup sampai EXPLAIN
            // membuktikan perlunya index khusus (ARCHITECTURE.md bagian 3).
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('report_number', 'ilike', "%{$search}%")
                    ->orWhereHas('currentVersion', fn ($version) => $version->whereRaw(
                        "payload->>'activity_name' ILIKE ?", ["%{$search}%"]
                    ))
            ))
            ->when(in_array($status, ReportStatus::values(), true),
                fn ($query) => $query->where('status', $status))
            ->when($periodId > 0, fn ($query) => $query->where('reporting_period_id', $periodId))
            // Filter kecamatan hanya bermakna bagi Admin Kabupaten; untuk akun
            // kecamatan, scopeVisibleTo sudah memaksa kecamatannya sendiri.
            ->when($user->isKabupaten() && $districtId > 0,
                fn ($query) => $query->where('district_id', $districtId))
            ->orderByDesc('updated_at')
            ->paginate((int) config('siaplapor.pagination.per_page'))
            ->withQueryString()
            ->through(fn (Report $report): array => ReportPresenter::listItem($report));

        return Inertia::render('reports/index', [
            'reports' => $reports,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'period' => $periodId > 0 ? $periodId : null,
                'district' => $districtId > 0 ? $districtId : null,
            ],
            'statuses' => $this->statusOptions(),
            'periods' => $this->periodOptions(),
            'districts' => $user->isKabupaten() ? $this->districtOptions() : null,
            'can_create' => $user->can('create', Report::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Report::class);

        return Inertia::render('reports/create', [
            'periods' => $this->activePeriodOptions(),
        ]);
    }

    public function store(StoreReportRequest $request, CreateReport $action): RedirectResponse
    {
        $this->authorize('create', Report::class);

        /** @var ReportingPeriod $period */
        $period = ReportingPeriod::query()->findOrFail($request->integer('reporting_period_id'));

        $report = $action->handle($request->user(), $period);

        return redirect()
            ->route('reports.edit', $report)
            ->with('success', 'Draf laporan dibuat. Lengkapi Formulir Model A lalu kirim.');
    }

    public function show(Request $request, Report $report): Response
    {
        $this->authorize('view', $report);

        return Inertia::render('reports/show', [
            'report' => ReportPresenter::detail($report, $request->user()),
            'attachment_limits' => $this->attachmentLimits(),
        ]);
    }

    public function edit(Request $request, Report $report): Response
    {
        $this->authorize('update', $report);

        return Inertia::render('reports/edit', [
            'report' => ReportPresenter::detail($report, $request->user()),
            'signer_capacities' => $this->signerCapacityOptions(),
            'attachment_limits' => $this->attachmentLimits(),
        ]);
    }

    /** @return array{max_files: int, max_size_kb: int, extensions: array<int, string>} */
    protected function attachmentLimits(): array
    {
        return [
            'max_files' => (int) config('siaplapor.attachments.max_files_per_version'),
            'max_size_kb' => (int) config('siaplapor.attachments.max_size_kb'),
            'extensions' => array_values((array) config('siaplapor.attachments.extensions')),
        ];
    }

    public function update(SaveDraftRequest $request, Report $report, SaveDraft $action): RedirectResponse
    {
        $this->authorize('update', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            values: $request->payload(),
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return back()->with('success', 'Draf tersimpan.');
    }

    public function submit(SubmitReportRequest $request, Report $report, SubmitReport $action): RedirectResponse
    {
        $this->authorize('submit', $report);

        $action->handle(
            actor: $request->user(),
            report: $report,
            expectedVersionId: $request->expectedVersionId(),
            expectedLockVersion: $request->expectedLockVersion(),
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Laporan berhasil dikirim dan tidak dapat diubah lagi pada versi ini.');
    }

    /**
     * Versi historis. Route binding nested memastikan versi benar-benar milik
     * laporan tersebut, lalu policy tetap dijalankan.
     */
    public function version(Request $request, Report $report, ReportVersion $version): Response
    {
        $this->authorize('view', $report);

        abort_unless($version->report_id === $report->id, 404);

        // Perbandingan opsional dengan versi lain dari laporan yang sama.
        $compareId = $request->integer('compare');
        $compare = $compareId > 0
            ? ReportVersion::query()
                ->where('report_id', $report->id)
                ->whereKey($compareId)
                ->first()
            : null;

        return Inertia::render('reports/version', [
            'report' => ReportPresenter::detail($report, $request->user()),
            'version' => ReportPresenter::version($version),
            'diff' => $compare === null
                ? null
                : ReportPresenter::diff($compare, $version),
            'compare_id' => $compare?->id,
        ]);
    }

    /** @return array<int, array{value: string, label: string}> */
    protected function statusOptions(): array
    {
        return array_map(
            fn (ReportStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            ReportStatus::cases(),
        );
    }

    /** @return array<int, array{value: string, label: string}> */
    protected function signerCapacityOptions(): array
    {
        return array_map(
            fn (SignerCapacity $capacity): array => ['value' => $capacity->value, 'label' => $capacity->label()],
            SignerCapacity::cases(),
        );
    }

    /** @return array<int, array{id: int, name: string}> */
    protected function periodOptions(): array
    {
        return ReportingPeriod::query()
            ->orderByDesc('starts_on')
            ->get(['id', 'name'])
            ->map(fn (ReportingPeriod $period): array => ['id' => $period->id, 'name' => $period->name])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, submission_deadline: string|null}> */
    protected function activePeriodOptions(): array
    {
        return ReportingPeriod::query()
            ->active()
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'submission_deadline'])
            ->map(fn (ReportingPeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'submission_deadline' => $period->submission_deadline?->toDateString(),
            ])
            ->all();
    }

    /** @return array<int, array{id: int, code: string, name: string}> */
    protected function districtOptions(): array
    {
        return District::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (District $district): array => [
                'id' => $district->id,
                'code' => $district->code,
                'name' => $district->name,
            ])
            ->all();
    }
}
