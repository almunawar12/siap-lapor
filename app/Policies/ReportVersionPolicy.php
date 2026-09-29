<?php

namespace App\Policies;

use App\Models\ReportVersion;
use App\Models\User;

class ReportVersionPolicy
{
    /**
     * Otorisasi versi selalu diturunkan dari laporan induknya. ID yang sulit
     * ditebak bukan pengganti otorisasi (ARCHITECTURE.md bagian 6).
     */
    public function view(User $user, ReportVersion $version): bool
    {
        return $user->can('view', $version->report);
    }
}
