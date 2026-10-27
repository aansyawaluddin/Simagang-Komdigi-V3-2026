<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Isi pesan dikirim apa adanya (teks mentah). Klien wajib meng-escape HTML saat
 * merender markdown. Pesan yang dihapus tampil sebagai placeholder tanpa isi.
 *
 * @mixin \App\Models\Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deleted = $this->trashed();

        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'parent_id' => $this->parent_id,
            'body' => $deleted ? null : $this->body,
            'is_deleted' => $deleted,
            'is_pinned' => ! $deleted && $this->is_pinned,
            'edited_at' => $deleted ? null : $this->edited_at,
            'created_at' => $this->created_at,
            // whenLoaded() mengembalikan null mentah jika relasinya kosong, jadi dicek eksplisit agar penulis terhapus tetap punya nama.
            'user' => $this->when($this->resource->relationLoaded('user'), fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name ?? 'Pengguna dihapus',
            ]),
            'mentions' => $deleted ? [] : $this->whenLoaded('mentions', fn () => $this->mentions->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])),
            'attachments' => $deleted ? [] : $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url' => route('workspace.attachments.download', $a),
            ])),
            'replies_count' => $this->whenCounted('replies'),
            'task_refs' => $this->resource->task_refs ?? [],
        ];
    }
}
