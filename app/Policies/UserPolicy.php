<?php

namespace App\Policies;

use App\Models\User;

/**
 * Hanya Admin Kabupaten yang mengelola akun. Admin Kecamatan tidak memiliki
 * akses apa pun ke pengelolaan akun, termasuk akunnya sendiri melalui modul ini
 * (profil pribadi memakai route terpisah).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function create(User $user): bool
    {
        return $user->isKabupaten();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isKabupaten() && $target->isKecamatan();
    }

    /**
     * Admin Kabupaten tidak boleh menonaktifkan akunnya sendiri.
     */
    public function toggleActive(User $user, User $target): bool
    {
        return $user->isKabupaten()
            && $target->isKecamatan()
            && $user->id !== $target->id;
    }
}
