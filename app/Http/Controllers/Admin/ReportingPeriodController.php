<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportingPeriodRequest;
use App\Models\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportingPeriodController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ReportingPeriod::class);

        $periods = ReportingPeriod::query()
            ->withCount('reports')
            ->orderByDesc('starts_on')
            ->paginate((int) config('siaplapor.pagination.per_page'))
            ->through(fn (ReportingPeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'submission_deadline' => $period->submission_deadline?->toDateString(),
                'is_active' => $period->is_active,
                'reports_count' => $period->reports_count,
            ]);

        return Inertia::render('admin/periods/index', ['periods' => $periods]);
    }

    public function store(ReportingPeriodRequest $request): RedirectResponse
    {
        $this->authorize('create', ReportingPeriod::class);

        $period = ReportingPeriod::create($request->safe()->only([
            'name', 'starts_on', 'ends_on', 'submission_deadline', 'is_active',
        ]));

        return back()->with('success', "Periode {$period->name} berhasil ditambahkan.");
    }

    /**
     * Periode tidak dihapus. Periode nonaktif mencegah pembuatan laporan baru,
     * tetapi laporan yang sudah ada tetap dapat direvisi (PRD bagian 6).
     */
    public function update(ReportingPeriodRequest $request, ReportingPeriod $period): RedirectResponse
    {
        $this->authorize('update', $period);

        $period->update($request->safe()->only([
            'name', 'starts_on', 'ends_on', 'submission_deadline', 'is_active',
        ]));

        return back()->with('success', "Periode {$period->name} berhasil diperbarui.");
    }
}
