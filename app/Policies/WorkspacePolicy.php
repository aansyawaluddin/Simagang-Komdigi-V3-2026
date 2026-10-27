<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\ChecksWorkspaceAccess;

class WorkspacePolicy
{
    use ChecksWorkspaceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canUseWorkspace($user);
    }

    /** Admin: semua tim. Mentor dan intern: workspace tempat mereka menjadi anggota aktif. */
    public function view(User $user, Workspace $workspace): bool
    {
        if ($this->isWorkspaceAdmin($user)) {
            return true;
        }

        return $this->canUseWorkspace($user) && $this->isMember($user, $workspace);
    }

    public function create(User $user): bool
    {
        return $this->isWorkspaceAdmin($user);
    }

    public function archive(User $user, Workspace $workspace): bool
    {
        return $this->isWorkspaceAdmin($user);
    }
}
