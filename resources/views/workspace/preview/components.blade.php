{{-- PREVIEW ONLY — galeri komponen Workspace. Dihapus bersama routes/workspace-preview.php. --}}
@extends('workspace.layout')

@section('title', 'Galeri Komponen Workspace')

@section('workspace-content')
    <x-workspace.hero class="ws-anim-1 mb-6" eyebrow="Preview · Fase 0" title="Galeri Komponen" subtitle="Komponen dasar yang dipakai semua halaman Workspace">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1.5"><i class="fas fa-code"></i> resources/views/components/workspace</span>
        </x-slot:meta>
        <button type="button" class="ws-btn ws-btn--primary" x-data x-on:click="WS.toast('Tombol hero diklik', 'success')">
            <i class="fas fa-plus"></i> Aksi utama
        </button>
    </x-workspace.hero>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Badge --}}
        <section class="ws-panel ws-anim-2">
            <p class="ws-section-label mb-4">Status tugas</p>
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach (['todo', 'in_progress', 'review', 'revision', 'done'] as $status)
                    <x-workspace.status-badge :status="$status" />
                @endforeach
            </div>
            <p class="ws-section-label mb-4">Prioritas & label</p>
            <div class="flex flex-wrap gap-2">
                @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                    <x-workspace.priority-badge :priority="$priority" />
                @endforeach
                <x-workspace.late-badge />
            </div>
        </section>

        {{-- Avatar --}}
        <section class="ws-panel ws-anim-2">
            <p class="ws-section-label mb-4">Avatar</p>
            <div class="flex items-end gap-3 mb-6">
                <x-workspace.avatar name="Andi Pratama" size="xs" />
                <x-workspace.avatar name="Andi Pratama" size="sm" />
                <x-workspace.avatar name="Andi Pratama" size="md" />
                <x-workspace.avatar name="Andi Pratama" size="lg" />
            </div>
            <p class="ws-section-label mb-4">Tumpukan assignee</p>
            <div class="ws-avatar-stack">
                <x-workspace.avatar name="Andi Pratama" size="sm" />
                <x-workspace.avatar name="Rina Wulandari" size="sm" />
                <x-workspace.avatar name="Siti Rahma" size="sm" />
                <span class="ws-avatar ws-avatar--sm" style="background:#e2e8f0;color:#475569;">+2</span>
            </div>
        </section>

        {{-- Tombol --}}
        <section class="ws-panel ws-anim-3">
            <p class="ws-section-label mb-4">Tombol</p>
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" class="ws-btn ws-btn--primary"><i class="fas fa-paper-plane"></i> Primary</button>
                <button type="button" class="ws-btn ws-btn--soft"><i class="fas fa-filter"></i> Soft</button>
                <button type="button" class="ws-btn ws-btn--ghost">Ghost</button>
                <button type="button" class="ws-btn ws-btn--danger"><i class="fas fa-trash"></i> Danger</button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="ws-btn ws-btn--primary ws-btn--sm">Kecil</button>
                <button type="button" class="ws-btn ws-btn--soft ws-btn--sm" disabled>Disabled</button>
                <button type="button" class="ws-icon-btn" aria-label="Contoh ikon"><i class="fas fa-ellipsis"></i></button>
            </div>
        </section>

        {{-- Form --}}
        <section class="ws-panel ws-anim-3">
            <p class="ws-section-label mb-4">Form</p>
            <div class="space-y-4">
                <div>
                    <label class="ws-label" for="demo-title">Judul tugas</label>
                    <input id="demo-title" type="text" class="ws-input" placeholder="Maks. 150 karakter" maxlength="150">
                </div>
                <div>
                    <label class="ws-label" for="demo-due">Deadline</label>
                    <input id="demo-due" type="datetime-local" class="ws-input ws-input--error">
                    <p class="ws-field-error">Deadline wajib diisi.</p>
                </div>
                <div>
                    <label class="ws-label" for="demo-priority">Prioritas</label>
                    <select id="demo-priority" class="ws-input">
                        <option>Rendah</option>
                        <option selected>Sedang</option>
                        <option>Tinggi</option>
                        <option>Urgent</option>
                    </select>
                </div>
            </div>
        </section>

        {{-- Overlay --}}
        <section class="ws-panel ws-anim-3"
                 x-data="{
                     reviewOpen: false, decision: 'approve', score: 85, feedback: '', submitted: false,
                     saveReview() {
                         this.submitted = true;
                         if (this.decision === 'revision' && !this.feedback.trim()) return;
                         this.reviewOpen = false;
                         WS.toast(this.decision === 'approve' ? 'T-12 ditandai Selesai' : 'T-12 dikembalikan ke Revisi', 'success');
                     },
                 }">
            <p class="ws-section-label mb-4">Modal & slide-over</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="ws-btn ws-btn--soft" x-on:click="reviewOpen = true; submitted = false">
                    <i class="fas fa-clipboard-check"></i> Modal review (x-model)
                </button>
                <button type="button" class="ws-btn ws-btn--soft" x-on:click="$dispatch('ws-open-modal', 'task-demo')">
                    <i class="fas fa-table-columns"></i> Slide-over (event)
                </button>
            </div>

            <x-workspace.modal name="review-demo" title="Review T-12 · Integrasi API absensi" x-model="reviewOpen">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="ws-tile cursor-pointer flex items-center gap-2" :class="decision === 'approve' && '!border-emerald-300 !bg-emerald-50'">
                            <input type="radio" value="approve" x-model="decision" class="accent-emerald-600">
                            <span class="text-sm font-semibold">Selesai</span>
                        </label>
                        <label class="ws-tile cursor-pointer flex items-center gap-2" :class="decision === 'revision' && '!border-orange-300 !bg-orange-50'">
                            <input type="radio" value="revision" x-model="decision" class="accent-orange-600">
                            <span class="text-sm font-semibold">Minta revisi</span>
                        </label>
                    </div>
                    <div x-show="decision === 'approve'">
                        <label class="ws-label" for="demo-score">Nilai (0–100)</label>
                        <input id="demo-score" type="number" min="0" max="100" class="ws-input" x-model.number="score">
                    </div>
                    <div>
                        <label class="ws-label" for="demo-feedback">
                            Catatan <span x-show="decision === 'revision'" class="text-red-600">*</span>
                        </label>
                        <textarea id="demo-feedback" rows="3" class="ws-input" x-model="feedback"
                                  :class="submitted && decision === 'revision' && !feedback.trim() && 'ws-input--error'"
                                  placeholder="Masukan untuk intern"></textarea>
                        <p class="ws-field-error" x-show="submitted && decision === 'revision' && !feedback.trim()">Catatan wajib diisi untuk revisi.</p>
                    </div>
                </div>
                <x-slot:footer>
                    <button type="button" class="ws-btn ws-btn--ghost" x-on:click="reviewOpen = false">Batal</button>
                    <button type="button" class="ws-btn ws-btn--primary" x-on:click="saveReview()">Simpan review</button>
                </x-slot:footer>
            </x-workspace.modal>

            <x-workspace.slide-over name="task-demo">
                <x-slot:header>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="ws-mono text-xs font-medium text-slate-500">T-12</span>
                        <x-workspace.status-badge status="review" />
                        <x-workspace.priority-badge priority="high" />
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Integrasi API absensi</h2>
                </x-slot:header>

                <div class="space-y-5 text-sm text-slate-600">
                    <p>Isi detail tugas (deskripsi, checklist, lampiran, komentar, riwayat) dibangun di Fase 1B memakai partial yang sama dengan halaman penuh.</p>
                    <div class="ws-tile flex items-center justify-between">
                        <span>Deadline</span>
                        <span class="font-semibold text-gray-800">28 Okt 2026, 17.00</span>
                    </div>
                    <div class="ws-tile flex items-center justify-between">
                        <span>Assignee</span>
                        <span class="ws-avatar-stack">
                            <x-workspace.avatar name="Andi Pratama" size="xs" />
                            <x-workspace.avatar name="Rina Wulandari" size="xs" />
                        </span>
                    </div>
                </div>

                <x-slot:footer>
                    <button type="button" class="ws-btn ws-btn--ghost" x-on:click="$dispatch('ws-close-modal', 'task-demo')">Tutup</button>
                    <button type="button" class="ws-btn ws-btn--primary"><i class="fas fa-clipboard-check"></i> Review</button>
                </x-slot:footer>
            </x-workspace.slide-over>
        </section>

        {{-- Toast & utilitas --}}
        <section class="ws-panel ws-anim-3" x-data="{ samples: [-0.2, -12, -180, -1500, -20000, 90] }">
            <p class="ws-section-label mb-4">Toast</p>
            <div class="flex flex-wrap gap-2 mb-6">
                <button type="button" class="ws-btn ws-btn--soft ws-btn--sm" x-on:click="WS.toast('Tugas berhasil dibuat', 'success')">Sukses</button>
                <button type="button" class="ws-btn ws-btn--soft ws-btn--sm" x-on:click="WS.toast('Gagal memindahkan kartu. Posisi dikembalikan.', 'error')">Gagal</button>
                <button type="button" class="ws-btn ws-btn--soft ws-btn--sm" x-on:click="WS.toast('3 pesan baru di #umum-aptika')">Info</button>
            </div>
            <p class="ws-section-label mb-4">WS.time.relative</p>
            <ul class="space-y-1 text-sm">
                <template x-for="m in samples" :key="m">
                    <li class="flex justify-between">
                        <span class="ws-mono ws-text-faint" x-text="m + ' menit'"></span>
                        <span x-text="WS.time.relative(new Date(Date.now() + m * 60000).toISOString())"></span>
                    </li>
                </template>
            </ul>
        </section>

        {{-- Banner & empty state --}}
        <section class="ws-panel lg:col-span-2 ws-anim-3">
            <p class="ws-section-label mb-4">Banner & empty state</p>
            <x-workspace.pending-mentor-banner class="mb-4" />
            <x-workspace.empty-state icon="fa-clipboard-list" title="Belum ada tugas" description="Tugas yang di-assign mentor akan muncul di sini.">
                <button type="button" class="ws-btn ws-btn--primary ws-btn--sm"><i class="fas fa-plus"></i> Buat tugas</button>
            </x-workspace.empty-state>
        </section>
    </div>
@endsection
