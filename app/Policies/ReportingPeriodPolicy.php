<?php

namespace App\Policies;

use App\Models\ReportingPeriod;
use App\Models\User;

class ReportingPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function create(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function update(User $user, ReportingPeriod $period): bool
    {
        return $user->isKabupaten();
    }
}
