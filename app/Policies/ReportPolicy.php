<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

/**
 * Admin Kecamatan hanya menyentuh laporan kecamatannya sendiri; Admin Kabupaten
 * melihat semua tetapi tidak pernah mengubah substansi laporan (PRD bagian 4).
 *
 * Policy menjawab "apakah peran ini berhak sama sekali". Kesesuaian status,
 * kepemilikan pemeriksaan, dan konkurensi diperiksa di dalam action agar klik
 * ganda menghasilkan hasil idempoten atau konflik terkontrol, bukan 403.
 */
class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isKabupaten() || $this->ownsDistrict($user, $report);
    }

    public function create(User $user): bool
    {
        return $user->isKecamatan();
    }

    public function update(User $user, Report $report): bool
    {
        return $this->ownsDistrict($user, $report) && $report->isEditable();
    }

    public function submit(User $user, Report $report): bool
    {
        return $this->ownsDistrict($user, $report);
    }

    public function manageAttachments(User $user, Report $report): bool
    {
        return $this->update($user, $report);
    }

    /**
     * Menulis tanggapan atas catatan revisi adalah hak kecamatan pemilik.
     */
    public function respond(User $user, Report $report): bool
    {
        return $this->ownsDistrict($user, $report);
    }

    /**
     * Seluruh tindakan pemeriksaan adalah hak Admin Kabupaten. Siapa pemeriksa
     * aktifnya diperiksa di action, bukan di sini.
     */
    public function review(User $user, Report $report): bool
    {
        return $user->isKabupaten();
    }

    public function reopen(User $user, Report $report): bool
    {
        return $user->isKabupaten() && $report->status === ReportStatus::Approved;
    }

    protected function ownsDistrict(User $user, Report $report): bool
    {
        return $user->isKecamatan()
            && $user->district_id !== null
            && $user->district_id === $report->district_id;
    }
}
