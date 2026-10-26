<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExtraDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedActivityPosts();
        $this->seedPengajuans();
        $this->seedMentorComments();
        $this->seedSharingSessions();
        $this->seedCertificates();
    }

    private function seedActivityPosts(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@simagang.com')->value('id');

        $posts = [
            ['Workshop Literasi Digital di KOMDIGI Makassar', 'artikel', null],
            ['Peserta Magang Ikuti Pelatihan Keamanan Siber', 'artikel', null],
            ['Kegiatan Sharing Session Peserta Magang', 'artikel', null],
            ['Profil Program Magang KOMDIGI Makassar', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ];

        foreach ($posts as $i => [$title, $type, $youtube]) {
            DB::table('activity_posts')->updateOrInsert(
                ['slug' => Str::slug($title)],
                [
                    'created_by' => $adminId,
                    'title' => $title,
                    'type' => $type,
                    'author_name' => 'Admin Simagang',
                    'editor_name' => 'Admin Simagang',
                    'excerpt' => 'Ringkasan singkat: ' . $title . '.',
                    'content' => $type === 'artikel'
                        ? '<p>Data contoh untuk keperluan pengembangan lokal. ' . $title . '.</p>'
                        : null,
                    'youtube_url' => $youtube,
                    'published_at' => now()->subDays(($i + 1) * 5)->toDateString(),
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedPengajuans(): void
    {
        if (DB::table('pengajuans')->exists()) {
            return;
        }

        $institusiIds = DB::table('institusis')->pluck('id')->take(4)->values();
        $lowonganId = DB::table('lowongans')->value('id');
        $statuses = ['pending', 'approved', 'rejected', 'revised'];

        foreach ($institusiIds as $i => $institusiId) {
            $status = $statuses[$i % count($statuses)];

            $pengajuanId = DB::table('pengajuans')->insertGetId([
                'institusi_id' => $institusiId,
                'lowongan_id' => $lowonganId,
                'surat_path' => 'surat-pengajuan/contoh-' . ($i + 1) . '.pdf',
                'status' => $status,
                'no_surat' => '00' . ($i + 1) . '/SURAT/2026',
                'nomor_surat_balasan' => $status === 'approved' ? '00' . ($i + 1) . '/BALASAN/2026' : null,
                'tujuan_surat' => 'Kepala KOMDIGI Makassar',
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->toDateString(),
                'keperluan' => 'Praktik kerja lapangan',
                'admin_note' => in_array($status, ['rejected', 'revised'], true) ? 'Catatan contoh dari admin.' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($n = 1; $n <= 2; $n++) {
                DB::table('pengajuan_details')->insert([
                    'pengajuan_id' => $pengajuanId,
                    'nama' => "Peserta Contoh {$i}-{$n}",
                    'email' => "peserta{$i}{$n}@contoh.test",
                    'no_telp' => '08123456' . str_pad((string) ($i * 10 + $n), 4, '0', STR_PAD_LEFT),
                    'jenis_kelamin' => $n % 2 ? 'L' : 'P',
                    'jurusan' => 'Teknik Informatika',
                    'soft_skill' => 'Komunikasi',
                    'hard_skill' => 'Pemrograman Web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedMentorComments(): void
    {
        if (DB::table('mentor_comments')->exists()) {
            return;
        }

        $mentorIds = DB::table('mentors')->pluck('id');
        if ($mentorIds->isEmpty()) {
            return;
        }

        $comments = ['Kerja bagus, lanjutkan.', 'Mohon detailkan aktivitas lebih lengkap.', 'Sudah sesuai target hari ini.'];

        foreach (DB::table('logbooks')->limit(20)->pluck('id') as $i => $logbookId) {
            DB::table('mentor_comments')->insert([
                'mentor_id' => $mentorIds[$i % $mentorIds->count()],
                'logbook_id' => $logbookId,
                'comment' => $comments[$i % count($comments)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSharingSessions(): void
    {
        if (DB::table('sharing_sessions')->exists()) {
            return;
        }

        $adminId = DB::table('users')->where('email', 'admin@simagang.com')->value('id');
        $internUserIds = DB::table('interns')->pluck('user_id')->values();

        foreach ([-14, 7, 21] as $i => $offset) {
            DB::table('sharing_sessions')->insert([
                'created_by' => $adminId,
                'speaker_user_id' => $internUserIds[$i % $internUserIds->count()] ?? null,
                'moderator_user_id' => $internUserIds[($i + 1) % $internUserIds->count()] ?? null,
                'session_date' => now()->addDays($offset)->toDateString(),
                'start_time' => '10:00:00',
                'location' => 'Ruang Rapat KOMDIGI Makassar',
                'title' => 'Sharing Session #' . ($i + 1),
                'description' => 'Topik contoh untuk sesi berbagi peserta magang.',
                'evaluation_form_link' => 'https://forms.gle/contoh' . ($i + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedCertificates(): void
    {
        foreach (DB::table('interns')->pluck('id') as $i => $internId) {
            if (DB::table('certificates')->where('intern_id', $internId)->exists()) {
                continue;
            }

            $certificateId = DB::table('certificates')->insertGetId([
                'intern_id' => $internId,
                'certificate_number' => sprintf('CERT/KOMDIGI-MKS/2026/%04d', $i + 1),
                'issue_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('certificate_scores')->insert([
                'certificate_id' => $certificateId,
                'discipline_attendance' => rand(80, 95),
                'responsibility' => rand(80, 95),
                'teamwork_communication' => rand(80, 95),
                'technical_skill' => rand(80, 95),
                'work_ethic' => rand(80, 95),
                'initiative_creativity' => rand(80, 95),
                'micro_skill' => rand(80, 95),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
