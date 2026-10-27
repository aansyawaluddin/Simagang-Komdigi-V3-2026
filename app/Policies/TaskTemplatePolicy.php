<?php

namespace App\Policies;

use App\Models\TaskTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspaceAccess;

class TaskTemplatePolicy
{
    use ChecksWorkspaceAccess;

    public function viewAny(User $user): bool
    {
        return $this->isWorkspaceAdmin($user) || $user->isMentor();
    }

    /** Admin: semua. Mentor: template global dan template timnya. */
    public function view(User $user, TaskTemplate $template): bool
    {
        if ($this->isWorkspaceAdmin($user)) {
            return true;
        }

        return $user->isMentor()
            && ($template->team_id === null || $template->team_id === $this->mentorTeamId($user));
    }

    public function create(User $user): bool
    {
        return $this->isWorkspaceAdmin($user) || ($user->isMentor() && $this->mentorTeamId($user) !== null);
    }

    /** Mentor hanya mengelola template timnya sendiri; template global khusus admin. */
    public function update(User $user, TaskTemplate $template): bool
    {
        if ($this->isWorkspaceAdmin($user)) {
            return true;
        }

        return $user->isMentor()
            && $template->team_id !== null
            && $template->team_id === $this->mentorTeamId($user);
    }

    public function delete(User $user, TaskTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    private function mentorTeamId(User $user): ?int
    {
        return optional($user->mentor)->team_id;
    }
}
