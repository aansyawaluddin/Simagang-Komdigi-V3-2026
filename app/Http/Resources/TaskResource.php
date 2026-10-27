<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $checklist = $this->whenLoaded('checklistItems');
        $progress = null;
        if ($this->relationLoaded('checklistItems') && $this->checklistItems->isNotEmpty()) {
            $progress = (int) round($this->checklistItems->where('is_done', true)->count() / $this->checklistItems->count() * 100);
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'position' => $this->position,
            'start_at' => $this->start_at,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'is_overdue' => $this->status !== TaskStatus::Done && $this->due_at?->isPast() === true,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'result_link' => $this->result_link,
            'created_by' => $this->created_by,
            'checklist_progress' => $progress,
            'checklist' => $checklist,
            'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator?->id, 'name' => $this->creator?->name]),
            'assignees' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url' => route('workspace.attachments.download', $a),
            ])),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'user' => ['id' => $c->user?->id, 'name' => $c->user?->name],
                'created_at' => $c->created_at,
            ])),
            'activities' => $this->whenLoaded('activities', fn () => $this->activities->map(fn ($a) => [
                'action' => $a->action,
                'meta' => $a->meta,
                'user' => $a->user?->name,
                'created_at' => $a->created_at,
            ])),
        ];
    }
}
