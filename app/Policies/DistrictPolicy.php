<?php

namespace App\Policies;

use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function create(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function update(User $user, District $district): bool
    {
        return $user->isKabupaten();
    }
}
