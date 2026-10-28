/*
 * Workspace — tugas (Fase 1B): store bersama, board kanban, dan list.
 *
 * Alur data:
 *   - Store `wsTasks` memegang TaskCard[] workspace yang sedang dibuka (dari $initialTasks).
 *   - Komponen lain (detail, form, modal) TIDAK mengubah store langsung; mereka memancarkan event
 *     window `ws-task-saved` { card } / `ws-task-deleted` { id }, lalu store menyesuaikan.
 *     Dengan begitu detail tugas juga bisa dipakai di halaman penuh tanpa board.
 *   - Perpindahan status yang butuh dialog (review / kirim hasil) diminta lewat
 *     `ws-open-review` / `ws-open-submit` { task, decision? }.
 */
(function () {
    'use strict';

    const WS = window.WS;

    const STATUSES = ['todo', 'in_progress', 'review', 'revision', 'done'];
    const PRIORITY_RANK = { urgent: 0, high: 1, medium: 2, low: 3 };

    /*
     * Status tujuan yang boleh dipilih user untuk kartu ini.
     * Server tetap memvalidasi; ini hanya agar UI tidak menawarkan aksi yang pasti ditolak.
     */
    function allowedTargets(card) {
        if (!card.can || !card.can.move) return [];
        if (card.can.review) return STATUSES.filter((s) => s !== card.status);
        // Intern: hanya sampai Review; tugas di Review/Selesai menunggu mentor.
        if (card.status === 'review' || card.status === 'done') return [];
        const targets = WS.tasks.submitLocked(card) ? ['todo', 'in_progress'] : ['todo', 'in_progress', 'review'];
        return targets.filter((s) => s !== card.status);
    }

    // Status yang tidak langsung dipindah, melainkan lewat dialog.
    function dialogFor(card, target) {
        if (target === 'done') return { event: 'ws-open-review', decision: 'approve' };
        if (target === 'revision') return { event: 'ws-open-review', decision: 'revision' };
        if (target === 'review' && !card.can.review) return { event: 'ws-open-submit' };
        return null;
    }

    function dueInfo(card) {
        const state = card.is_late ? 'overdue' : WS.time.dueState(card.due_at, card.status);
        return { state, label: WS.time.dateTime(card.due_at), relative: WS.time.relative(card.due_at) };
    }

    // Sama dengan ikon di components/workspace/priority-badge.blade.php
    const PRIORITY_ICON = { low: 'fa-arrow-down', medium: 'fa-equals', high: 'fa-arrow-up', urgent: 'fa-fire' };
    const priorityIcon = (p) => PRIORITY_ICON[p] || 'fa-flag';

    WS.tasks = Object.assign(WS.tasks || {}, { STATUSES, allowedTargets, dialogFor, dueInfo, priorityIcon });

    document.addEventListener('alpine:init', () => {
        const Alpine = window.Alpine;

        // ---------- Store ----------
        Alpine.store('wsTasks', {
            booted: false,
            workspace: null,
            tasks: [],
            initialDone: 0,
            readOnly: false,
            busy: {},          // { [taskId]: true } saat request berjalan
            detailId: null,    // tugas yang dibuka di slide-over
            detailOpen: false,

            boot(config) {
                this.workspace = config.workspace;
                this.tasks = config.tasks;
                this.readOnly = !!config.workspace.is_read_only;
                this.initialDone = config.tasks.filter((t) => t.status === 'done').length;
                this.booted = true;
                if (WS.config.mock && WS.mock) WS.mock.seed('tasks', config.tasks);

                window.addEventListener('ws-task-saved', (e) => this.upsert(e.detail.card));
                window.addEventListener('ws-task-deleted', (e) => this.remove(e.detail.id));
                // Realtime: perubahan tugas oleh orang lain langsung tampil di board & list.
                if (WS.realtime) {
                    const ch = 'workspace.' + config.workspace.id;
                    WS.realtime.subscribe(ch, 'task.changed', (e) => { if (e && e.card && !this.busy[e.card.id]) this.upsert(e.card); });
                    WS.realtime.subscribe(ch, 'task.deleted', (e) => { if (e && e.id) this.remove(e.id); });
                }
                // Label tim berubah (nama/warna/hapus): perbarui chip label di semua kartu.
                window.addEventListener('ws-labels-changed', (e) => {
                    this.tasks.forEach((t) => { t.labels = WS.labels.apply(t.labels, e.detail.labels); });
                });
                window.addEventListener('popstate', () => this.syncFromUrl());
                this.syncFromUrl();
            },

            byId(id) {
                return this.tasks.find((t) => t.id === id);
            },

            column(status) {
                return this.tasks
                    .filter((t) => t.status === status)
                    .sort((a, b) => a.position - b.position);
            },

            count(status) {
                return this.tasks.filter((t) => t.status === status).length;
            },

            // Statistik hidup: angka server + perubahan yang terjadi di halaman ini.
            stat(status, serverValue) {
                if (status === 'late') return this.tasks.filter((t) => t.is_late && t.status !== 'done').length;
                if (status === 'done') return serverValue + (this.count('done') - this.initialDone);
                return this.count(status);
            },

            get activeCount() {
                return this.tasks.filter((t) => t.status !== 'done').length;
            },

            upsert(card) {
                const i = this.tasks.findIndex((t) => t.id === card.id);
                if (i === -1) this.tasks.push(card);
                else this.tasks.splice(i, 1, Object.assign({}, this.tasks[i], card));
            },

            remove(id) {
                this.tasks = this.tasks.filter((t) => t.id !== id);
                if (this.detailId === id) this.closeDetail();
            },

            // Susun ulang posisi satu kolom sesuai urutan id.
            reorder(status, orderedIds) {
                orderedIds.forEach((id, index) => {
                    const t = this.byId(id);
                    if (t) { t.status = status; t.position = index; }
                });
            },

            /*
             * Pindahkan kartu ke `target` pada indeks `index` (optimistic).
             * Dipanggil oleh drag & drop dan menu "Pindah status".
             */
            async requestMove(card, target, index) {
                if (!allowedTargets(card).includes(target) && !(target === card.status && card.can.move)) {
                    WS.toast(target === 'review' && WS.tasks.submitLocked(card) ? WS.tasks.LOCK_NOTE : 'Anda tidak dapat memindahkan tugas ke kolom ini.', 'error');
                    return;
                }
                const dialog = target === card.status ? null : dialogFor(card, target);
                if (dialog) {
                    window.dispatchEvent(new CustomEvent(dialog.event, { detail: { task: card, decision: dialog.decision } }));
                    return;
                }

                const snapshot = this.tasks.map((t) => ({ id: t.id, status: t.status, position: t.position }));
                const ids = this.column(target).map((t) => t.id).filter((id) => id !== card.id);
                ids.splice(Math.max(0, Math.min(index, ids.length)), 0, card.id);
                const from = card.status;
                this.reorder(target, ids);
                if (from !== target) this.reorder(from, this.column(from).map((t) => t.id));

                this.busy[card.id] = true;
                try {
                    const res = await WS.api.tasks.move(card.id, { status: target, column_order: ids });
                    if (res && res.data) this.upsert(res.data);
                    if (from !== target) WS.toast(`${card.code} dipindah ke ${WS.STATUS[target]}`, 'success');
                } catch (err) {
                    snapshot.forEach((s) => Object.assign(this.byId(s.id) || {}, { status: s.status, position: s.position }));
                    WS.toast(err.message + ' Posisi kartu dikembalikan.', 'error');
                } finally {
                    delete this.busy[card.id];
                }
            },

            // ---------- Slide-over detail + URL (?task=T-12) ----------

            openDetail(card, { push = true } = {}) {
                this.detailId = card.id;
                this.detailOpen = true;
                if (push) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('task', card.code);
                    window.history.pushState({ task: card.code }, '', url);
                }
            },

            // Idempoten: dipanggil juga saat slide-over ditutup lewat x-model (detailOpen sudah false).
            closeDetail() {
                this.detailOpen = false;
                const url = new URL(window.location.href);
                if (url.searchParams.has('task')) {
                    url.searchParams.delete('task');
                    window.history.pushState({}, '', url);
                }
            },

            syncFromUrl() {
                const code = new URL(window.location.href).searchParams.get('task');
                const card = code && this.tasks.find((t) => t.code === code);
                if (card) this.openDetail(card, { push: false });
                else this.detailOpen = false;
            },
        });

        // ---------- Board kanban ----------
        Alpine.data('wsBoard', () => ({
            sortables: [],
            draggingId: null,
            justDraggedAt: 0,

            init() {
                if (!window.Sortable) {
                    WS.toast('SortableJS gagal dimuat; gunakan menu "Pindah status".', 'error');
                    return;
                }
                this.$nextTick(() => {
                    this.$root.querySelectorAll('[data-column]').forEach((el) => this.bindColumn(el));
                });
            },

            destroy() {
                this.sortables.forEach((s) => s.destroy());
            },

            bindColumn(el) {
                const store = this.$store.wsTasks;
                const self = this;
                this.sortables.push(window.Sortable.create(el, {
                    group: {
                        name: 'ws-board',
                        put(to, from, dragEl) {
                            const card = store.byId(Number(dragEl.dataset.id));
                            const target = to.el.dataset.column;
                            return !!card && allowedTargets(card).includes(target);
                        },
                    },
                    draggable: '.ws-task-card',
                    filter: '[data-no-drag]',
                    preventOnFilter: false,
                    disabled: store.readOnly,
                    animation: 150,
                    delay: 180,
                    delayOnTouchOnly: true,
                    ghostClass: 'ws-task-card--ghost',
                    chosenClass: 'ws-task-card--chosen',
                    dragClass: 'ws-task-card--drag',
                    onMove(evt) {
                        const card = store.byId(Number(evt.dragged.dataset.id));
                        return !!card && card.can.move;
                    },
                    onStart(evt) {
                        self.draggingId = Number(evt.item.dataset.id);
                    },
                    onEnd(evt) {
                        self.draggingId = null;
                        self.justDraggedAt = Date.now();
                        const card = store.byId(Number(evt.item.dataset.id));
                        const target = evt.to.dataset.column;
                        const index = evt.newDraggableIndex;
                        // Kembalikan DOM ke posisi semula; Alpine yang merender ulang dari data.
                        const siblings = [...evt.from.querySelectorAll(':scope > .ws-task-card')].filter((n) => n !== evt.item);
                        evt.from.insertBefore(evt.item, siblings[evt.oldDraggableIndex] || null);
                        if (!card || (evt.from === evt.to && evt.oldDraggableIndex === evt.newDraggableIndex)) return;
                        store.requestMove(card, target, index);
                    },
                }));
            },

            isDropTarget(status) {
                if (this.draggingId === null) return false;
                const card = this.$store.wsTasks.byId(this.draggingId);
                return !!card && allowedTargets(card).includes(status);
            },

            open(card) {
                // Klik yang terjadi tepat setelah drag bukan niat membuka detail.
                if (Date.now() - this.justDraggedAt < 300) return;
                this.$store.wsTasks.openDetail(card);
            },

            targets: allowedTargets,
            due: dueInfo,
        }));

        // ---------- List / tabel ----------
        Alpine.data('wsTaskList', (labels, interns) => ({
            labels,
            interns,

            init() {
                window.addEventListener('ws-labels-changed', (e) => {
                    this.labels = e.detail.labels;
                    if (this.label && !this.labels.some((l) => String(l.id) === this.label)) this.label = '';
                });
            },

            q: '',
            statuses: [],
            priority: '',
            assignee: '',
            label: '',
            dueFrom: '',
            dueTo: '',
            sortKey: 'due_at',
            sortDir: 'asc',

            get filtered() {
                const q = this.q.trim().toLowerCase();
                const from = this.dueFrom ? new Date(this.dueFrom + 'T00:00:00').getTime() : null;
                const to = this.dueTo ? new Date(this.dueTo + 'T23:59:59').getTime() : null;
                const rows = this.$store.wsTasks.tasks.filter((t) => {
                    if (q && !(t.title.toLowerCase().includes(q) || t.code.toLowerCase().includes(q))) return false;
                    if (this.statuses.length && !this.statuses.includes(t.status)) return false;
                    if (this.priority && t.priority !== this.priority) return false;
                    if (this.assignee && !t.assignees.some((a) => String(a.id) === this.assignee)) return false;
                    if (this.label && !t.labels.some((l) => String(l.id) === this.label)) return false;
                    const due = new Date(t.due_at).getTime();
                    if (from && due < from) return false;
                    if (to && due > to) return false;
                    return true;
                });
                const dir = this.sortDir === 'asc' ? 1 : -1;
                const value = {
                    code: (t) => Number(t.code.replace(/\D/g, '')),
                    title: (t) => t.title.toLowerCase(),
                    status: (t) => STATUSES.indexOf(t.status),
                    priority: (t) => PRIORITY_RANK[t.priority],
                    due_at: (t) => new Date(t.due_at).getTime(),
                }[this.sortKey];
                return rows.sort((a, b) => (value(a) > value(b) ? dir : value(a) < value(b) ? -dir : 0));
            },

            get filtering() {
                return !!(this.q || this.statuses.length || this.priority || this.assignee || this.label || this.dueFrom || this.dueTo);
            },

            toggleStatus(status) {
                const i = this.statuses.indexOf(status);
                if (i === -1) this.statuses.push(status);
                else this.statuses.splice(i, 1);
            },

            sort(key) {
                if (this.sortKey === key) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                else { this.sortKey = key; this.sortDir = 'asc'; }
            },

            ariaSort(key) {
                if (this.sortKey !== key) return 'none';
                return this.sortDir === 'asc' ? 'ascending' : 'descending';
            },

            reset() {
                Object.assign(this, { q: '', statuses: [], priority: '', assignee: '', label: '', dueFrom: '', dueTo: '' });
            },

            due: dueInfo,
        }));
    });
})();
