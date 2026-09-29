<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictRequest;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DistrictController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', District::class);

        $districts = District::query()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (District $district): array => [
                'id' => $district->id,
                'code' => $district->code,
                'name' => $district->name,
                'is_active' => $district->is_active,
                'users_count' => $district->users_count,
            ]);

        return Inertia::render('admin/districts/index', [
            'districts' => $districts,
        ]);
    }

    public function store(DistrictRequest $request): RedirectResponse
    {
        $this->authorize('create', District::class);

        $district = District::create($request->safe()->only(['code', 'name', 'is_active']));

        return back()->with('success', "Kecamatan {$district->name} berhasil ditambahkan.");
    }

    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        $this->authorize('update', $district);

        $district->update($request->safe()->only(['code', 'name', 'is_active']));

        return back()->with('success', "Kecamatan {$district->name} berhasil diperbarui.");
    }
}
