<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Support\ReportPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Semua angka dihitung dari data nyata dan dibatasi cakupan wilayah
     * pengguna. Tidak ada statistik statis.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $periodId = $request->integer('period');

        $period = $periodId > 0
            ? ReportingPeriod::query()->find($periodId)
            : ReportingPeriod::query()->active()->orderByDesc('starts_on')->first();

        $scoped = Report::query()
            ->visibleTo($user)
            ->when($period !== null, fn ($query) => $query->where('reporting_period_id', $period->id));

        $byStatus = (clone $scoped)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCards = array_map(
            fn (ReportStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'total' => (int) ($byStatus[$status->value] ?? 0),
            ],
            ReportStatus::cases(),
        );

        $recent = (clone $scoped)
            ->with(['district:id,code,name', 'period:id,name', 'currentVersion:id,report_id,version_number,payload'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Report $report): array => ReportPresenter::listItem($report))
            ->all();

        return Inertia::render('dashboard', [
            'period' => $period === null ? null : [
                'id' => $period->id,
                'name' => $period->name,
                'is_active' => $period->is_active,
                'submission_deadline' => $period->submission_deadline?->toDateString(),
            ],
            'periods' => ReportingPeriod::query()
                ->orderByDesc('starts_on')
                ->get(['id', 'name'])
                ->map(fn (ReportingPeriod $p): array => ['id' => $p->id, 'name' => $p->name])
                ->all(),
            'status_cards' => $statusCards,
            'reports_total' => (int) $byStatus->sum(),
            'recent_reports' => $recent,
            'kabupaten' => $user->isKabupaten() ? $this->kabupatenStats($period) : null,
        ]);
    }

    /**
     * "Belum melapor" berarti kecamatan aktif tanpa pengiriman pada periode
     * terpilih, bukan kewajiban satu laporan per kecamatan (PRD bagian 8).
     *
     * @return array<string, mixed>
     */
    protected function kabupatenStats(?ReportingPeriod $period): array
    {
        $activeDistricts = District::query()->active();

        $submittedDistrictIds = $period === null
            ? collect()
            : Report::query()
                ->where('reporting_period_id', $period->id)
                ->whereNotNull('first_submitted_at')
                ->distinct()
                ->pluck('district_id');

        return [
            'districts_total' => District::query()->count(),
            'districts_active' => (clone $activeDistricts)->count(),
            'districts_reported' => $submittedDistrictIds->count(),
            'districts_not_reported' => $period === null
                ? null
                : (clone $activeDistricts)->whereNotIn('id', $submittedDistrictIds)->count(),
            'kecamatan_accounts_total' => User::query()->kecamatan()->count(),
            'kecamatan_accounts_active' => User::query()->kecamatan()->where('is_active', true)->count(),
        ];
    }
}
