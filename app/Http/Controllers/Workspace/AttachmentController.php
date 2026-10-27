<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** PDF, DOC/DOCX, XLS/XLSX, PNG/JPG, ZIP, maksimal 10 MB per file. */
    public const FILE_RULES = ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip'];

    public function __construct(private TaskService $tasks)
    {
    }

    /** Unggah lampiran ke tugas. */
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => self::FILE_RULES,
        ]);

        $saved = $this->tasks->storeAttachments($task, $request->file('files'), $request->user());

        $this->tasks->record($task, $request->user(), 'attachment_added', [
            'files' => array_map(fn (Attachment $a) => $a->original_name, $saved),
        ]);

        return response()->json([
            'data' => array_map(fn (Attachment $a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url' => route('workspace.attachments.download', $a),
            ], $saved),
        ], 201);
    }

    /** Unduh lampiran dari storage private; hanya yang berhak melihat induknya. */
    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
