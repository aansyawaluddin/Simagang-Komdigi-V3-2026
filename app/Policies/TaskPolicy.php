<?php

namespace App\Policies;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\ChecksWorkspaceAccess;
use Illuminate\Support\Facades\DB;

class TaskPolicy
{
    use ChecksWorkspaceAccess;

    public function view(User $user, Task $task): bool
    {
        return $this->canViewWorkspace($user, $task->workspace);
    }

    /**
     * Admin dan mentor workspace boleh membuat tugas. Intern hanya boleh membuat
     * to-do pribadi di Space-nya sendiri.
     *
     * Pemakaian: $user->can('create', [Task::class, $workspace])
     */
    public function create(User $user, Workspace $workspace): bool
    {
        if ($workspace->is_archived) {
            return false;
        }

        if ($this->isWorkspaceAdmin($user) || $this->isWorkspaceMentor($user, $workspace)) {
            return true;
        }

        return $this->isOwnSpace($user, $workspace) && $this->canWrite($user, $workspace);
    }

    public function update(User $user, Task $task): bool
    {
        $workspace = $task->workspace;

        if ($workspace->is_archived) {
            return false;
        }

        if ($this->isWorkspaceAdmin($user) || $this->isWorkspaceMentor($user, $workspace)) {
            return true;
        }

        // Intern hanya mengubah to-do pribadi yang ia buat sendiri.
        return $this->isOwnSpace($user, $workspace)
            && $task->created_by === $user->id
            && $this->canWrite($user, $workspace);
    }

    /** Soft delete: admin, atau mentor untuk tugas buatannya. Intern tidak boleh. */
    public function delete(User $user, Task $task): bool
    {
        if ($this->isWorkspaceAdmin($user)) {
            return true;
        }

        return $this->isWorkspaceMentor($user, $task->workspace)
            && $task->created_by === $user->id;
    }

    /**
     * Pindah status / posisi di board.
     * Admin dan mentor bebas. Intern hanya pada tugas yang di-assign ke dia, dan hanya
     * sampai Review: Selesai dan Revisi hanya lewat review mentor.
     *
     * Pemakaian: $user->can('updateStatus', [$task, TaskStatus::Review])
     */
    public function updateStatus(User $user, Task $task, TaskStatus $to): bool
    {
        $workspace = $task->workspace;

        if ($workspace->is_archived) {
            return false;
        }

        if ($this->isWorkspaceAdmin($user) || $this->isWorkspaceMentor($user, $workspace)) {
            return true;
        }

        if (! $user->isIntern() || ! $this->canWrite($user, $workspace) || ! $this->isAssignee($user, $task)) {
            return false;
        }

        // Dari Review dan Selesai hanya mentor yang memutuskan.
        if (! in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Revision], true)) {
            return false;
        }

        $allowedTargets = [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Review];
        if (! in_array($to, $allowedTargets, true)) {
            return false;
        }

        // Revisi kembali ke In Progress, bukan loncat ke Review atau To Do.
        if ($task->status === TaskStatus::Revision && $to !== TaskStatus::InProgress) {
            return false;
        }

        return true;
    }

    /** Menandai Selesai atau Revisi: hanya admin dan mentor workspace. Status Review dicek di controller. */
    public function review(User $user, Task $task): bool
    {
        $workspace = $task->workspace;

        if ($workspace->is_archived) {
            return false;
        }

        return $this->isWorkspaceAdmin($user) || $this->isWorkspaceMentor($user, $workspace);
    }

    /** Komentar tugas: semua anggota yang boleh menulis di workspace. */
    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task) && $this->canWrite($user, $task->workspace);
    }

    private function canViewWorkspace(User $user, Workspace $workspace): bool
    {
        if ($this->isWorkspaceAdmin($user)) {
            return true;
        }

        return $this->canUseWorkspace($user) && $this->isMember($user, $workspace);
    }

    private function isAssignee(User $user, Task $task): bool
    {
        $internId = optional($user->intern)->id;

        return $internId
            && DB::table('task_assignees')
                ->where('task_id', $task->id)
                ->where('intern_id', $internId)
                ->exists();
    }
}
