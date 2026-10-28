{{--
    Kelola label tim (dibuka lewat event ws-open-labels { teamId }). Perubahan disiarkan ws-labels-changed.
    Variabel: $labelTeams {id,name}[], $labelsByTeam [teamId => Label[]]
--}}
<div x-data="wsLabelManager(@js(['teams' => $labelTeams, 'labelsByTeam' => (object) $labelsByTeam]))">
    <x-workspace.modal name="ws-labels" max-width="md" labelledby="ws-labels-heading" x-model="open">
        <div class="-mt-1">
            <h2 id="ws-labels-heading" class="text-lg font-bold text-gray-800 mb-1">Label tim</h2>
            <p class="text-sm text-slate-500 mb-4">Label dipakai untuk mengelompokkan tugas, misalnya <em>frontend</em> atau <em>dokumen</em>.</p>

            <div class="mb-4" x-show="teams.length > 1">
                <label class="ws-label" for="ws-l-team">Tim</label>
                <select id="ws-l-team" class="ws-input !py-2 !text-sm" x-model.number="teamId" x-on:change="reset()">
                    <template x-for="t in teams" :key="t.id"><option :value="t.id" x-text="t.name" :selected="t.id === teamId"></option></template>
                </select>
            </div>

            <ul class="ws-tpl-pick !max-h-[300px]" aria-label="Daftar label">
                <template x-for="l in labels" :key="l.id">
                    <li class="px-3 py-2.5">
                        {{-- Tampilan --}}
                        <div class="flex items-center gap-3" x-show="editingId !== l.id">
                            <span class="ws-label-chip !text-sm"><span class="ws-label-chip__dot" :style="{ background: l.color }"></span><span x-text="l.name"></span></span>
                            <span class="ml-auto flex items-center gap-1" x-show="confirmId !== l.id">
                                <button type="button" class="ws-icon-btn ws-icon-btn--sm" x-on:click="startEdit(l)" :aria-label="'Ubah label ' + l.name"><i class="fas fa-pen"></i></button>
                                <button type="button" class="ws-icon-btn ws-icon-btn--sm hover:!text-red-700" x-on:click="remove(l)" :aria-label="'Hapus label ' + l.name"><i class="fas fa-trash"></i></button>
                            </span>
                            <span class="ml-auto flex items-center gap-2 text-xs" x-show="confirmId === l.id" role="alert">
                                <span class="text-red-700 font-semibold">Hapus & lepas dari semua tugas?</span>
                                <button type="button" class="ws-btn ws-btn--danger ws-btn--sm !py-1" x-on:click="remove(l)" :disabled="busy">Hapus</button>
                                <button type="button" class="ws-btn ws-btn--ghost ws-btn--sm !py-1" x-on:click="confirmId = null">Batal</button>
                            </span>
                        </div>
                        {{-- Ubah --}}
                        <div x-show="editingId === l.id">
                            <div class="flex items-center gap-2">
                                <input type="text" class="ws-input !py-1.5 !text-sm" data-label-edit maxlength="40" x-model="edit.name"
                                       :aria-label="'Nama label ' + l.name" :class="errors.edit && 'ws-input--error'"
                                       x-on:keydown.enter.prevent="saveEdit(l)" x-on:keydown.escape.prevent.stop="editingId = null">
                                <button type="button" class="ws-btn ws-btn--primary ws-btn--sm" x-on:click="saveEdit(l)" :disabled="busy">Simpan</button>
                                <button type="button" class="ws-btn ws-btn--ghost ws-btn--sm" x-on:click="editingId = null">Batal</button>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2" role="radiogroup" :aria-label="'Warna label ' + l.name">
                                <template x-for="c in colors" :key="c">
                                    <button type="button" class="ws-color" role="radio" :aria-checked="(edit.color === c).toString()" :aria-label="'Warna ' + c"
                                            :style="{ background: c }" x-on:click="edit.color = c"></button>
                                </template>
                            </div>
                            <p class="ws-field-error" x-show="errors.edit" x-text="errors.edit"></p>
                        </div>
                    </li>
                </template>
                <li class="px-3 py-4 text-sm text-slate-500 text-center" x-show="!labels.length" x-text="'Belum ada label untuk ' + teamName + '.'"></li>
            </ul>

            {{-- Tambah --}}
            <div class="mt-4">
                <label class="ws-label" for="ws-l-new">Label baru</label>
                <div class="flex items-center gap-2">
                    <input id="ws-l-new" type="text" class="ws-input !py-2 !text-sm" data-label-new data-autofocus maxlength="40" x-model="draft.name"
                           placeholder="Mis. riset" :class="errors.name && 'ws-input--error'" x-on:keydown.enter.prevent="add()">
                    <button type="button" class="ws-btn ws-btn--primary ws-btn--sm shrink-0" x-on:click="add()" :disabled="busy">
                        <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Tambah
                    </button>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2" role="radiogroup" aria-label="Warna label baru">
                    <template x-for="c in colors" :key="c">
                        <button type="button" class="ws-color" role="radio" :aria-checked="(draft.color === c).toString()" :aria-label="'Warna ' + c"
                                :style="{ background: c }" x-on:click="draft.color = c"></button>
                    </template>
                </div>
                <p class="ws-field-error" x-show="errors.name" x-text="errors.name"></p>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" class="ws-btn ws-btn--primary" x-on:click="open = false">Selesai</button>
        </x-slot:footer>
    </x-workspace.modal>
</div>
