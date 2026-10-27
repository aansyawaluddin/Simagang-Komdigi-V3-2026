<?php

namespace Database\Seeders;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Template tugas onboarding untuk semua tim (team_id kosong).
 * Idempoten: dicocokkan lewat judul, aman dijalankan ulang.
 */
class TaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@simagang.com')->value('id');

        $templates = [
            [
                'title' => 'Lengkapi profil',
                'description' => 'Lengkapi data diri, foto profil, dan kontak di halaman profil Simagang.',
                'checklist' => ['Unggah foto profil', 'Periksa nomor telepon', 'Periksa data institusi dan jurusan'],
            ],
            [
                'title' => 'Baca panduan magang',
                'description' => 'Pelajari aturan magang, jam kerja, dan tata cara absensi serta logbook.',
                'checklist' => ['Baca aturan absensi', 'Baca tata cara pengisian logbook', 'Baca ketentuan laporan akhir'],
            ],
            [
                'title' => 'Perkenalan di channel tim',
                'description' => 'Perkenalkan diri di channel #umum tim: nama, asal institusi, dan minat.',
                'checklist' => [],
            ],
            [
                'title' => 'Absen hari pertama',
                'description' => 'Lakukan absensi masuk dan isi logbook untuk hari pertama magang.',
                'checklist' => ['Absen masuk', 'Isi logbook hari pertama'],
            ],
        ];

        foreach ($templates as $template) {
            TaskTemplate::updateOrCreate(
                ['team_id' => null, 'title' => $template['title'], 'is_onboarding' => true],
                [
                    'created_by' => $adminId,
                    'description' => $template['description'],
                    'checklist' => $template['checklist'],
                ]
            );
        }
    }
}
