<?php

use App\Models\District;
use App\Models\User;
use Tests\ReportTestHelpers as H;

it('merender dashboard kabupaten dengan angka dari data nyata', function (): void {
    District::factory()->count(2)->create();
    District::factory()->inactive()->create();
    H::period();

    $this->actingAs(H::kabupaten())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('kabupaten.districts_total', 3)
            ->where('kabupaten.districts_active', 2)
            ->where('kabupaten.kecamatan_accounts_total', 0)
            ->where('reports_total', 0)
            ->where('recent_reports', [])
        );
});

it('tidak mengirim statistik kabupaten ke akun kecamatan', function (): void {
    $this->actingAs(H::kecamatan())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('kabupaten', null));
});

it('menghitung status laporan dashboard hanya dalam cakupan pengguna', function (): void {
    $period = H::period();
    $userA = H::kecamatan();
    $userB = H::kecamatan();

    H::draft($userA, $period);
    H::draft($userB, $period);
    H::draft($userB, $period);

    $this->actingAs($userA)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('reports_total', 1));

    $this->actingAs(H::kabupaten())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('reports_total', 3));
});

it('merender halaman profil', function (): void {
    $this->actingAs(H::kabupaten())
        ->get('/profil')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('profile'));
});

it('merender halaman data kecamatan, periode, dan form akun baru', function (): void {
    $district = District::factory()->create();
    $admin = H::kabupaten();

    $this->actingAs($admin)->get('/admin/districts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/districts/index'));

    $this->actingAs($admin)->get('/admin/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/periods/index'));

    $this->actingAs($admin)->get('/admin/users/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/create')
            ->where('districts.0.id', $district->id)
        );
});

it('merender daftar, form, dan detail laporan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->get('/reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/index')->where('can_create', true));

    $this->actingAs($user)->get('/reports/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/create'));

    $this->actingAs($user)->get("/reports/{$report->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/edit')
            ->where('report.capabilities.update', true)
        );

    $this->actingAs($user)->get("/reports/{$report->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/show'));

    $this->actingAs($user)->get("/reports/{$report->id}/versions/{$report->current_version_id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/version'));
});

it('tidak mengirim kemampuan mengubah kepada admin kabupaten', function (): void {
    $report = H::draft(H::kecamatan());

    $this->actingAs(H::kabupaten())
        ->get("/reports/{$report->id}")
        ->assertInertia(fn ($page) => $page
            ->where('report.capabilities.update', false)
            ->where('report.capabilities.submit', false)
        );
});

it('tidak membocorkan hash kata sandi pada props yang dibagikan', function (): void {
    $this->actingAs(User::factory()->kabupaten()->create())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->missing('auth.user.password'));
});
