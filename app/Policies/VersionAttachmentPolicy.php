<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VersionAttachment;

class VersionAttachmentPolicy
{
    public function view(User $user, VersionAttachment $attachment): bool
    {
        return $user->can('view', $attachment->version->report);
    }

    public function delete(User $user, VersionAttachment $attachment): bool
    {
        return $user->can('manageAttachments', $attachment->version->report);
    }
}
