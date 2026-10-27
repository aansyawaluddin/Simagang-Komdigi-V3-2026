<?php

namespace App\Services;

use App\Models\Intern;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCommentedNotification;
use App\Notifications\TaskCommentMentionNotification;
use App\Notifications\TaskReviewedNotification;
use App\Notifications\TaskReviewRequestedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notifikasi in-app untuk event tugas. Kegagalan kirim tidak boleh menggagalkan aksi utama.
 * Dipanggil setelah transaksi selesai supaya tidak ada notifikasi untuk perubahan yang di-rollback.
 */
class TaskNotifier
{
    /** @param  array<int, int>  $internIds */
    public function assigned(Task $task, array $internIds, User $actor): void
    {
        $this->send($this->internUsers($internIds)->reject(fn (User $u) => $u->id === $actor->id), new TaskAssignedNotification($task, $actor));
    }

    /** Tugas masuk Review: beri tahu mentor/admin pembuat tugas. */
    public function reviewRequested(Task $task, User $actor): void
    {
        $creator = $task->creator;

        if ($creator && $creator->id !== $actor->id) {
            $this->send(collect([$creator]), new TaskReviewRequestedNotification($task, $actor));
        }
    }

    /** Tugas Selesai atau Revisi: beri tahu intern yang mengerjakan. */
    public function reviewed(Task $task, User $actor): void
    {
        $this->send(
            $this->assigneeUsers($task)->reject(fn (User $u) => $u->id === $actor->id),
            new TaskReviewedNotification($task)
        );
    }

    /**
     * Komentar baru: pembuat tugas dan semua assignee, kecuali penulis dan yang sudah
     * mendapat notifikasi mention.
     *
     * @param  array<int, int>  $mentionedIds
     */
    public function commented(Task $task, TaskComment $comment, array $mentionedIds = []): void
    {
        $recipients = $this->assigneeUsers($task)
            ->push($task->creator)
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === $comment->user_id || in_array($u->id, $mentionedIds, true));

        $this->send($recipients, new TaskCommentedNotification($task, $comment));
    }

    /** @param  array<int, int>  $userIds */
    public function mentionedInComment(Task $task, TaskComment $comment, array $userIds): void
    {
        $this->send(User::whereIn('id', $userIds)->get(), new TaskCommentMentionNotification($task, $comment));
    }

    /** @return Collection<int, User> */
    private function assigneeUsers(Task $task): Collection
    {
        return $task->assignees()->with('user')->get()->pluck('user')->filter()->unique('id');
    }

    /**
     * @param  array<int, int>  $internIds
     * @return Collection<int, User>
     */
    private function internUsers(array $internIds): Collection
    {
        return Intern::with('user')->whereIn('id', $internIds)->get()->pluck('user')->filter()->unique('id');
    }

    /** @param  Collection<int, User>  $users */
    private function send(Collection $users, Notification $notification): void
    {
        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
