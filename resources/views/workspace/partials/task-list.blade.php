{{--
    List / tabel tugas dengan filter (assignee, status, prioritas, label, rentang deadline).
    Filter berjalan di client atas data store (maks. 200 tugas aktif per workspace).
    Variabel: $labels, $assignableInterns
--}}
@php
    $statusChips = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'revision' => 'Revisi', 'done' => 'Selesai'];
    $sortColumns = ['code' => 'Kode', 'title' => 'Judul', 'status' => 'Status', 'priority' => 'Prioritas', 'due_at' => 'Deadline'];
@endphp

<div x-data="wsTaskList(@js($labels), @js($assignableInterns))">
    {{-- Filter. HP: hanya pencarian + tombol "Filter" (filter lain dilipat). --}}
    <div class="ws-panel !p-4 mb-4 space-y-3" x-data="{ more: false }">
        <div class="flex flex-col lg:flex-row gap-3">
            <div class="flex gap-2 flex-1">
                <label class="relative flex-1 min-w-0">
                    <span class="sr-only">Cari tugas</span>
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 ws-text-faint text-sm"></i>
                    <input type="search" x-model.debounce.150ms="q" class="ws-input !pl-10" placeholder="Cari judul atau kode…">
                </label>
                <button type="button" class="ws-btn ws-btn--soft md:hidden shrink-0" x-on:click="more = !more" :aria-expanded="more.toString()" aria-controls="ws-list-more">
                    <i class="fas fa-sliders"></i> Filter
                    <span class="ws-tab__count" x-show="(assignee ? 1 : 0) + (priority ? 1 : 0) + (label ? 1 : 0) + statuses.length + (dueFrom || dueTo ? 1 : 0)" x-cloak
                          x-text="(assignee ? 1 : 0) + (priority ? 1 : 0) + (label ? 1 : 0) + statuses.length + (dueFrom || dueTo ? 1 : 0)"></span>
                </button>
            </div>
            <div id="ws-list-more" class="ws-filter-more grid grid-cols-2 sm:grid-cols-3 gap-3 lg:w-[540px]" :class="more && 'is-open'">
                <label>
                    <span class="sr-only">Assignee</span>
                    <select x-model="assignee" class="ws-input">
                        <option value="">Semua assignee</option>
                        <template x-for="i in interns" :key="i.id"><option :value="String(i.id)" x-text="i.name"></option></template>
                    </select>
                </label>
                <label>
                    <span class="sr-only">Prioritas</span>
                    <select x-model="priority" class="ws-input">
                        <option value="">Semua prioritas</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">Tinggi</option>
                        <option value="medium">Sedang</option>
                        <option value="low">Rendah</option>
                    </select>
                </label>
                <label class="col-span-2 sm:col-span-1">
                    <span class="sr-only">Label</span>
                    <select x-model="label" class="ws-input">
                        <option value="">Semua label</option>
                        <template x-for="l in labels" :key="l.id"><option :value="String(l.id)" x-text="l.name"></option></template>
                    </select>
                </label>
            </div>
        </div>
        <div class="ws-filter-more flex flex-col md:flex-row md:items-center gap-3" :class="more && 'is-open'">
            <div class="flex flex-wrap gap-2" role="group" aria-label="Filter status">
                @foreach ($statusChips as $key => $label)
                    <button type="button" class="ws-chip ws-chip--plain !py-1 !text-xs" :aria-pressed="statuses.includes('{{ $key }}').toString()" x-on:click="toggleStatus('{{ $key }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            {{-- HP: label di baris sendiri, dua input berbagi lebar supaya tidak melebihi layar. --}}
            <div class="flex flex-wrap items-center gap-2 md:ml-auto text-sm">
                <span class="text-slate-500 w-full sm:w-auto">Deadline</span>
                <input type="date" x-model="dueFrom" class="ws-input !py-1.5 !text-sm min-w-0 flex-1 sm:flex-none sm:w-40" aria-label="Deadline dari">
                <span class="ws-text-faint" aria-hidden="true">–</span>
                <input type="date" x-model="dueTo" class="ws-input !py-1.5 !text-sm min-w-0 flex-1 sm:flex-none sm:w-40" aria-label="Deadline sampai">
            </div>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500">
            <span x-text="filtered.length + ' dari ' + $store.wsTasks.tasks.length + ' tugas'"></span>
            <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" x-show="filtering" x-cloak x-on:click="reset()">
                <i class="fas fa-xmark mr-1"></i>Reset filter
            </button>
        </div>
    </div>

    {{-- Tabel (md ke atas) --}}
    <div class="ws-panel !p-0 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="ws-table">
                <thead>
                    <tr>
                        @foreach ($sortColumns as $key => $label)
                            <th scope="col" :aria-sort="ariaSort('{{ $key }}')" class="{{ $key === 'title' ? 'w-full' : '' }}">
                                <button type="button" class="ws-sort-btn" x-on:click="sort('{{ $key }}')">
                                    {{ $label }}
                                    <i class="fas text-[9px]" :class="sortKey !== '{{ $key }}' ? 'fa-sort opacity-40' : (sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down')"></i>
                                </button>
                            </th>
                            @if ($key === 'title')
                                <th scope="col">Assignee</th>
                            @endif
                        @endforeach
                        <th scope="col">Checklist</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in filtered" :key="t.id">
                        <tr x-on:click="$store.wsTasks.openDetail(t)">
                            <td class="ws-mono text-xs ws-text-faint whitespace-nowrap" x-text="t.code"></td>
                            <td>
                                <button type="button" class="text-left font-semibold text-gray-800 hover:text-indigo-600 line-clamp-1" x-text="t.title"
                                        x-on:click.stop="$store.wsTasks.openDetail(t)"></button>
                                <div class="flex flex-wrap gap-1 mt-1" x-show="t.labels.length">
                                    <template x-for="label in t.labels" :key="label.id">
                                        <span class="ws-label-chip"><span class="ws-label-chip__dot" :style="{ background: label.color }"></span><span x-text="label.name"></span></span>
                                    </template>
                                </div>
                            </td>
                            <td>
                                <span class="ws-avatar-stack">
                                    <template x-for="a in t.assignees" :key="a.id"><x-workspace.avatar-js user="a" size="xs" /></template>
                                </span>
                            </td>
                            <td>
                                <span class="ws-badge" :class="'ws-status--' + t.status"><span class="ws-badge__dot"></span><span x-text="WS.STATUS[t.status]"></span></span>
                            </td>
                            <td>
                                <span class="ws-badge" :class="'ws-priority--' + t.priority"><i class="fas text-[10px]" :class="WS.tasks.priorityIcon(t.priority)"></i><span x-text="WS.PRIORITY[t.priority]"></span></span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="ws-due" :class="'ws-due--' + due(t).state" x-text="due(t).label"></span>
                                <div class="ws-unfinished" x-show="WS.tasks.unfinished(t)">
                                    <span class="ws-badge ws-badge--late !text-[11px] !px-2 !py-0"><i class="fas fa-hourglass-end text-[9px]"></i>Belum diselesaikan</span>
                                    <span class="ws-unfinished__lock" x-show="WS.tasks.submitLocked(t)" :title="WS.tasks.LOCK_NOTE"><i class="fas fa-lock"></i>Upload dikunci</span>
                                </div>
                                <template x-if="t.is_late && t.status === 'review'"><span class="block text-[11px] font-semibold text-red-600">Terlambat</span></template>
                            </td>
                            <td class="whitespace-nowrap text-xs text-slate-500">
                                <span x-show="t.checklist.total" x-text="t.checklist.done + '/' + t.checklist.total"></span>
                                <span x-show="!t.checklist.total" class="text-slate-300">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="filtered.length === 0" x-cloak>
            <x-workspace.empty-state icon="fa-filter-circle-xmark" title="Tidak ada tugas yang cocok" description="Ubah atau reset filter untuk melihat tugas lain." />
        </div>
    </div>

    {{-- Kartu (HP) --}}
    <div class="md:hidden space-y-3">
        <template x-for="t in filtered" :key="t.id">
            <button type="button" class="ws-task-card ws-task-card--locked w-full text-left" x-on:click="$store.wsTasks.openDetail(t)">
                <div class="flex items-center gap-2 mb-1">
                    <span class="ws-mono text-[11px] ws-text-faint" x-text="t.code"></span>
                    <span class="ws-badge !text-[11px] !px-2 !py-0" :class="'ws-status--' + t.status"><span x-text="WS.STATUS[t.status]"></span></span>
                    <span class="ws-badge !text-[11px] !px-2 !py-0" :class="'ws-priority--' + t.priority"><span x-text="WS.PRIORITY[t.priority]"></span></span>
                </div>
                <p class="text-sm font-semibold text-gray-800 line-clamp-2" x-text="t.title"></p>
                <div class="flex items-center gap-3 mt-2">
                    <span class="ws-due" :class="'ws-due--' + due(t).state"><i class="far fa-clock"></i><span x-text="due(t).label"></span></span>
                    <span class="ws-avatar-stack ml-auto">
                        <template x-for="a in t.assignees" :key="a.id"><x-workspace.avatar-js user="a" size="xs" /></template>
                    </span>
                </div>
            </button>
        </template>
        <div class="ws-panel" x-show="filtered.length === 0" x-cloak>
            <x-workspace.empty-state icon="fa-filter-circle-xmark" title="Tidak ada tugas yang cocok" description="Ubah atau reset filter untuk melihat tugas lain." />
        </div>
    </div>
</div>
