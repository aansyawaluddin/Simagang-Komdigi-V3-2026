<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/** Menyimpan lampiran tugas, komentar, dan pesan di storage private. */
class AttachmentService
{
    public const DISK = 'local';

    private const MAX_FILES_PER_MINUTE = 10;
    private const IMAGE_MAX_SIDE = 1600;

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  bool  $compressImages  perkecil dan kompres gambar JPG/PNG (untuk lampiran chat)
     * @return array<int, Attachment>
     */
    public function store(Model $attachable, array $files, User $user, bool $compressImages = false): array
    {
        $this->enforceUploadLimit($user, count($files));

        $saved = [];

        foreach ($files as $file) {
            $path = $this->storeFile($file, $compressImages);

            $saved[] = Attachment::create([
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
                'user_id' => $user->id,
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime' => $file->getMimeType(),
                'size' => Storage::disk(self::DISK)->size($path),
            ]);
        }

        return $saved;
    }

    /** Maksimal 10 file per menit per user, dihitung per file bukan per request. */
    private function enforceUploadLimit(User $user, int $count): void
    {
        if ($count === 0) {
            return;
        }

        $key = 'workspace-upload:' . $user->id;

        if (RateLimiter::attempts($key) + $count > self::MAX_FILES_PER_MINUTE) {
            throw new ThrottleRequestsException(
                'Terlalu banyak unggahan. Maksimal ' . self::MAX_FILES_PER_MINUTE . ' file per menit.',
                null,
                ['Retry-After' => RateLimiter::availableIn($key)]
            );
        }

        for ($i = 0; $i < $count; $i++) {
            RateLimiter::hit($key, 60);
        }
    }

    private function storeFile(UploadedFile $file, bool $compressImages): string
    {
        $dir = 'workspace/attachments/' . now()->format('Y/m');
        $mime = $file->getMimeType();

        if ($compressImages && in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $image = (new ImageManager(new Driver()))
                ->read($file->getRealPath())
                ->scaleDown(self::IMAGE_MAX_SIDE, self::IMAGE_MAX_SIDE);

            $isPng = $mime === 'image/png';
            $path = $dir . '/' . Str::random(40) . ($isPng ? '.png' : '.jpg');

            Storage::disk(self::DISK)->put($path, (string) ($isPng ? $image->toPng() : $image->toJpeg(80)));

            return $path;
        }

        return $file->store($dir, self::DISK);
    }
}
