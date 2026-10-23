<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\TimeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSanitizeAttendancePhotosController extends Controller
{
    private const SETTING_KEY = 'attendance_photo_sanitized_at';
    private const SANITIZE_INTERVAL_MONTHS = 3;

    public function __invoke(): RedirectResponse
    {
        if (! Schema::hasTable('system_settings')) {
            return back()->with('error', 'Tabel system_settings belum tersedia. Jalankan php artisan migrate terlebih dahulu.');
        }

        $now = TimeService::nowWita();
        $status = self::status();
        $nextAllowedAt = $status['next_allowed_at'];

        if (! $status['can_run']) {
            return back()->with(
                'warning',
                'Sanitasi file belum bisa dijalankan. Tombol akan aktif kembali pada ' .
                $nextAllowedAt->locale('id')->translatedFormat('d F Y H:i') . ' WITA.'
            );
        }

        if ($status['candidate_count'] === 0) {
            return back()->with(
                'warning',
                'Belum ada foto absensi yang berumur lebih dari 3 bulan, sehingga sanitasi manual belum perlu dijalankan.'
            );
        }

        $exitCode = Artisan::call('attendance:sanitize-photos', [
            '--months' => self::SANITIZE_INTERVAL_MONTHS,
            '--force' => true,
        ]);

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return back()->with('error', 'Sanitasi file gagal dijalankan. ' . $output);
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => self::SETTING_KEY],
            [
                'value' => $now->toDateTimeString(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with(
            'success',
            'Sanitasi file foto absensi berhasil dijalankan. Tombol akan aktif kembali setelah 3 bulan.'
        );
    }

    public static function status(): array
    {
        $now = TimeService::nowWita();

        $cutoffDate = $now->copy()
            ->subMonthsNoOverflow(self::SANITIZE_INTERVAL_MONTHS)
            ->toDateString();

        $lastRunAt = null;

        if (Schema::hasTable('system_settings')) {
            $value = DB::table('system_settings')
                ->where('key', self::SETTING_KEY)
                ->value('value');

            $lastRunAt = $value ? Carbon::parse($value, 'Asia/Makassar') : null;
        }

        $nextAllowedAt = $lastRunAt
            ? $lastRunAt->copy()->addMonthsNoOverflow(self::SANITIZE_INTERVAL_MONTHS)
            : null;

        $canRunByInterval = ! $nextAllowedAt || $now->gte($nextAllowedAt);

        $candidateCount = Attendance::query()
            ->whereDate('date', '<=', $cutoffDate)
            ->where(function ($query) {
                $query->whereNotNull('photo_path')
                    ->orWhereNotNull('photo_checkout');
            })
            ->count();

        return [
            'can_run' => $canRunByInterval,
            'last_run_at' => $lastRunAt,
            'next_allowed_at' => $nextAllowedAt,
            'candidate_count' => $candidateCount,
            'cutoff_date' => $cutoffDate,
            'remaining_days' => $nextAllowedAt && $now->lt($nextAllowedAt)
                ? (int) $now->diffInDays($nextAllowedAt)
                : 0,
        ];
    }
}