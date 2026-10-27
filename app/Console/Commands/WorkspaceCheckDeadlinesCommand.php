<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskDueSoonNotification;
use App\Notifications\TaskOverdueNotification;
use App\Services\TaskService;
use Illuminate\Console\Command;

class WorkspaceCheckDeadlinesCommand extends Command
{
    protected $signature = 'workspace:check-deadlines';

    protected $description = 'Tandai tugas Terlambat dan kirim pengingat H-1 deadline (masing-masing sekali per tugas)';

    public function handle(TaskService $tasks): int
    {
        $overdue = $this->markOverdue($tasks);
        $reminded = $this->sendReminders();

        $this->info("Ditandai terlambat: {$overdue}");
        $this->info("Pengingat H-1 terkirim: {$reminded}");

        return self::SUCCESS;
    }

    /** Lewat deadline dan belum Selesai: tandai sekali, beri tahu intern dan mentor pembuat. */
    private function markOverdue(TaskService $tasks): int
    {
        $count = 0;

        Task::query()
            ->whereNull('overdue_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', TaskStatus::Done->value)
            ->whereHas('workspace', fn ($q) => $q->where('is_archived', false))
            ->with(['assignees.user', 'creator'])
            ->chunkById(100, function ($batch) use ($tasks, &$count) {
                foreach ($batch as $task) {
                    $task->forceFill(['overdue_at' => now()])->save();
                    $tasks->record($task, null, 'overdue');

                    $recipients = $task->assignees->pluck('user')->push($task->creator)->filter()->unique('id');
                    $this->notifyAll($recipients, new TaskOverdueNotification($task));

                    $count++;
                }
            });

        return $count;
    }

    /** Deadline dalam 24 jam ke depan dan intern masih perlu mengerjakan (belum Review/Selesai). */
    private function sendReminders(): int
    {
        $count = 0;

        Task::query()
            ->whereNull('reminded_at')
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->whereIn('status', [TaskStatus::Todo->value, TaskStatus::InProgress->value, TaskStatus::Revision->value])
            ->whereHas('workspace', fn ($q) => $q->where('is_archived', false))
            ->with('assignees.user')
            ->chunkById(100, function ($batch) use (&$count) {
                foreach ($batch as $task) {
                    $task->forceFill(['reminded_at' => now()])->save();

                    $this->notifyAll($task->assignees->pluck('user')->filter()->unique('id'), new TaskDueSoonNotification($task));

                    $count++;
                }
            });

        return $count;
    }

    private function notifyAll($users, $notification): void
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
