{{--
    Modal template tugas: form buat/edit (juga "Simpan sebagai template" dari detail tugas),
    tugaskan massal, dan hapus. Dibuka lewat event window (lihat public/js/workspace/templates.js).
    Variabel:
      $tplTeams        {id,name}[]   tim yang boleh dipilih
      $labels          {id,name,color}[]
      $canChooseTeam   bool          admin memilih tim; mentor memakai tim sendiri
      $defaultTeamId   int|null
      $withAssign      bool          sertakan modal tugaskan & hapus (halaman template)
      $assignInterns   (bila $withAssign) intern yang boleh ditugaskan
--}}

{{-- ================= Form template ================= --}}
<div x-data="wsTemplateForm(@js(['teams' => $tplTeams, 'labels' => $labels, 'canChooseTeam' => $canChooseTeam, 'defaultTeamId' => $defaultTeamId]))">
    <x-workspace.modal name="ws-template-form" max-width="lg" labelledby="ws-template-form-heading" x-model="open">
        <form x-on:submit.prevent="submit()" novalidate class="-mt-1">
            <h2 id="ws-template-form-heading" class="text-lg font-bold text-gray-800 mb-1" x-text="heading"></h2>
            <p class="text-sm text-slate-500 mb-5">Template tidak punya assignee dan tanggal pasti. Keduanya diisi saat template ditugaskan.</p>

            <div class="space-y-5">
                <div x-show="canChooseTeam && teams.length > 1">
                    <label class="ws-label" for="ws-t-team">Tim <span class="text-red-600">*</span></label>
                    <select id="ws-t-team" class="ws-input" x-model.number="form.team_id" :class="errors.team_id && 'ws-input--error'">
                        <option value="">Pilih tim…</option>
                        <template x-for="t in teams" :key="t.id"><option :value="t.id" x-text="t.name"></option></template>
                    </select>
                    <p class="ws-field-error" x-show="errors.team_id" x-text="errors.team_id"></p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="ws-label" for="ws-t-title">Judul tugas <span class="text-red-600">*</span></label>
                        <span class="text-xs" :class="form.title.length > 150 ? 'text-red-600' : 'ws-text-faint'" x-text="form.title.length + '/150'"></span>
                    </div>
                    <input id="ws-t-title" type="text" class="ws-input" data-autofocus x-model="form.title" maxlength="160"
                           :class="errors.title && 'ws-input--error'" placeholder="Mis. Laporan mingguan">
                    <p class="ws-field-error" x-show="errors.title" x-text="errors.title"></p>
                </div>

                <div>
                    <label class="ws-label" for="ws-t-desc">Deskripsi</label>
                    <textarea id="ws-t-desc" rows="4" class="ws-input !text-sm" x-model="form.description"
                              placeholder="Tujuan, langkah, dan kriteria selesai… (markdown sederhana)"></textarea>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="ws-label" for="ws-t-priority">Prioritas</label>
                        <select id="ws-t-priority" class="ws-input" x-model="form.priority">
                            <option value="low">Rendah</option>
                            <option value="medium">Sedang</option>
                            <option value="high">Tinggi</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="ws-label" for="ws-t-days">Deadline default <span class="text-red-600">*</span></label>
                        <div class="flex items-center gap-2">
                            <input id="ws-t-days" type="number" min="0" max="90" step="1" class="ws-input !w-24" x-model.number="form.due_in_days"
                                   :class="errors.due_in_days && 'ws-input--error'" aria-describedby="ws-t-days-hint">
                            <span id="ws-t-days-hint" class="text-sm text-slate-500">hari setelah ditugaskan, pukul 17.00</span>
                        </div>
                        <p class="ws-field-error" x-show="errors.due_in_days" x-text="errors.due_in_days"></p>
                    </div>
                </div>

                <div x-show="labels.length">
                    <p class="ws-label" id="ws-t-labels">Label</p>
                    <div class="flex flex-wrap gap-2" role="group" aria-labelledby="ws-t-labels">
                        <template x-for="l in labels" :key="l.id">
                            <button type="button" class="ws-chip ws-chip--plain !py-1 !text-xs" :aria-pressed="form.label_ids.includes(l.id).toString()" x-on:click="toggleLabel(l.id)">
                                <span class="ws-label-chip__dot" :style="{ background: l.color }"></span><span x-text="l.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="ws-label">Checklist</p>
                    <ul class="space-y-2">
                        <template x-for="(item, index) in form.checklist" :key="index">
                            <li class="flex items-center gap-2">
                                <i class="far fa-square text-slate-300" aria-hidden="true"></i>
                                <input type="text" class="ws-input !py-2 !text-sm" x-model="item.content" data-tpl-checklist :aria-label="'Item checklist ' + (index + 1)"
                                       x-on:keydown.enter.prevent="addChecklist()">
                                <button type="button" class="ws-icon-btn ws-icon-btn--sm shrink-0" x-on:click="form.checklist.splice(index, 1)" aria-label="Hapus item"><i class="fas fa-xmark"></i></button>
                            </li>
                        </template>
                    </ul>
                    <button type="button" class="text-sm font-semibold text-indigo-600 mt-2 py-1" x-on:click="addChecklist()"><i class="fas fa-plus mr-1 text-xs"></i>Tambah item</button>
                </div>

                <label class="ws-tpl-toggle">
                    <input type="checkbox" class="mt-1 w-4 h-4 accent-indigo-600" x-model="form.is_onboarding">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Template onboarding</span>
                        <span class="block text-xs text-slate-600">Otomatis ditugaskan ke setiap intern baru yang bergabung ke tim ini.</span>
                    </span>
                </label>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="ws-btn ws-btn--ghost" x-on:click="open = false">Batal</button>
            <button type="button" class="ws-btn ws-btn--primary" x-on:click="submit()" :disabled="saving">
                <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                <span x-text="editing ? 'Simpan perubahan' : 'Simpan template'"></span>
            </button>
        </x-slot:footer>
    </x-workspace.modal>
</div>

@if ($withAssign ?? false)
    {{-- ================= Tugaskan massal ================= --}}
    <div x-data="wsTemplateAssign(@js(['interns' => $assignInterns]))">
        <x-workspace.modal name="ws-template-assign" max-width="lg" labelledby="ws-template-assign-heading" x-model="open">
            <template x-if="template && !result">
                <div class="-mt-1">
                    <h2 id="ws-template-assign-heading" class="text-lg font-bold text-gray-800 mb-1" x-text="'Tugaskan: ' + template.title"></h2>
                    <p class="text-sm text-slate-500 mb-5">Setiap intern yang dipilih mendapat salinan tugasnya sendiri di kelompoknya.</p>

                    <div class="space-y-5">
                        {{-- Intern --}}
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <p class="ws-label !mb-0" id="ws-a-interns">Intern <span class="text-red-600">*</span>
                                    <span class="font-normal text-slate-500" x-show="selected.length" x-text="'· ' + selected.length + ' dipilih'"></span>
                                </p>
                                <button type="button" class="text-sm font-semibold text-indigo-600 py-1" x-show="candidates.length > 1" x-on:click="toggleAll()"
                                        x-text="allSelected ? 'Batalkan semua' : 'Pilih semua'"></button>
                            </div>
                            <input type="search" class="ws-input !py-2 !text-sm mb-2" x-model="q" placeholder="Cari intern…" aria-label="Cari intern" x-show="candidates.length > 6 || q">
                            <div role="group" aria-labelledby="ws-a-interns">
                            <ul class="ws-tpl-pick" :class="errors.assignee_ids && 'ws-tpl-pick--error'">
                                <template x-for="i in candidates" :key="i.id">
                                    <li>
                                        <label class="ws-tpl-pick__item">
                                            <input type="checkbox" class="w-4 h-4 accent-indigo-600" :checked="selected.includes(i.id)" x-on:change="toggle(i.id)">
                                            <x-workspace.avatar-js user="i" size="xs" />
                                            <span class="text-sm font-medium text-gray-800 truncate" x-text="i.name"></span>
                                            <span class="ml-auto text-xs text-slate-500 truncate" x-text="i.group ? i.group.name : ''"></span>
                                        </label>
                                    </li>
                                </template>
                                <li class="px-3 py-4 text-sm text-slate-500 text-center" x-show="!candidates.length">
                                    <span x-text="q ? 'Tidak ada intern yang cocok.' : 'Belum ada anak magang berkelompok di tim ' + template.team.name + '.'"></span>
                                </li>
                            </ul>
                            </div>
                            <p class="ws-field-error" x-show="errors.assignee_ids" x-text="errors.assignee_ids"></p>
                        </div>

                        {{-- Waktu --}}
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="ws-label" for="ws-a-start">Mulai</label>
                                <input id="ws-a-start" type="datetime-local" class="ws-input" x-model="startAt">
                            </div>
                            <div>
                                <label class="ws-label" for="ws-a-due">Deadline <span class="text-red-600">*</span></label>
                                <input id="ws-a-due" type="datetime-local" class="ws-input" x-model="dueAt" :class="errors.due_at && 'ws-input--error'">
                                <p class="ws-field-error" x-show="errors.due_at" x-text="errors.due_at"></p>
                                <p class="text-xs text-slate-500 mt-1" x-show="!errors.due_at" x-text="'Default template: +' + template.due_in_days + ' hari'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Hasil --}}
            <template x-if="template && result">
                <div class="-mt-1" role="status">
                    <span class="inline-flex w-12 h-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3"><i class="fas fa-check text-lg"></i></span>
                    <h2 id="ws-template-assign-heading" class="text-lg font-bold text-gray-800" x-text="result.data.length + ' tugas dibuat'"></h2>
                    <p class="text-sm text-slate-500 mb-4" x-text="'Dari template ' + template.title + '. Intern sudah mendapat notifikasi.'"></p>
                    <ul class="ws-tpl-pick">
                        <template x-for="c in result.data" :key="c.id">
                            <li>
                                <a :href="boardUrl(c)" class="ws-tpl-pick__item hover:bg-slate-50">
                                    <span class="ws-mono text-xs ws-text-faint w-10 shrink-0" x-text="c.code"></span>
                                    <span class="text-sm font-medium text-gray-800 truncate" x-text="c.assignees.map((a) => a.name).join(', ')"></span>
                                    <span class="ml-auto text-xs text-indigo-600 truncate" x-text="c.workspace.name"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <x-slot:footer>
                <template x-if="!result">
                    <div class="contents">
                        <button type="button" class="ws-btn ws-btn--ghost" x-on:click="open = false">Batal</button>
                        <button type="button" class="ws-btn ws-btn--primary" x-on:click="submit()" :disabled="saving">
                            <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                            <span x-text="selected.length > 1 ? 'Tugaskan ke ' + selected.length + ' intern' : 'Tugaskan'"></span>
                        </button>
                    </div>
                </template>
                <template x-if="result">
                    <button type="button" class="ws-btn ws-btn--primary" x-on:click="open = false">Selesai</button>
                </template>
            </x-slot:footer>
        </x-workspace.modal>
    </div>

    {{-- ================= Hapus template ================= --}}
    <div x-data="wsTemplateDelete">
        <x-workspace.modal name="ws-template-delete" max-width="sm" labelledby="ws-template-delete-heading" x-model="open">
            <template x-if="template">
                <div class="text-center sm:text-left">
                    <span class="inline-flex w-12 h-12 items-center justify-center rounded-full bg-red-50 text-red-600 mb-3"><i class="fas fa-trash text-lg"></i></span>
                    <h2 id="ws-template-delete-heading" class="text-lg font-bold text-gray-800" x-text="'Hapus template ' + template.title + '?'"></h2>
                    <p class="text-sm text-slate-600 mt-2">Tugas yang sudah dibuat dari template ini tidak ikut terhapus.
                        <span x-show="template.is_onboarding">Intern baru tidak akan lagi menerima tugas onboarding ini.</span>
                    </p>
                </div>
            </template>
            <x-slot:footer>
                <button type="button" class="ws-btn ws-btn--ghost" x-on:click="open = false">Batal</button>
                <button type="button" class="ws-btn ws-btn--danger" x-on:click="confirm()" :disabled="removing">
                    <i class="fas" :class="removing ? 'fa-spinner fa-spin' : 'fa-trash'"></i> Hapus template
                </button>
            </x-slot:footer>
        </x-workspace.modal>
    </div>
@endif
