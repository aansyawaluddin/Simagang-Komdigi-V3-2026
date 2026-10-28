/*
 * Workspace — template tugas & tugaskan massal (Fase 2).
 * Komunikasi lewat event window:
 *   masuk:  ws-open-template-form { template?, fromTask?, teamId? }, ws-open-template-assign { template },
 *           ws-open-template-delete { template }
 *   keluar: ws-template-saved { template }, ws-template-deleted { id }
 * Label tim: ws-open-labels { teamId } membuka pengelola; perubahan disiarkan lewat ws-labels-changed { teamId, labels }.
 */
(function () {
    'use strict';

    const WS = window.WS;
    const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));
    const pad = (n) => String(n).padStart(2, '0');

    // Deadline default: hari ini + N hari, pukul 17.00 (nilai <input type="datetime-local">).
    function dueFromDays(days) {
        const d = new Date();
        d.setDate(d.getDate() + Number(days || 0));
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T17:00`;
    }

    const fromLocalInput = (value) => (value ? new Date(value).toISOString() : null);

    // Teks polos dari markdown sederhana (ringkasan di kartu template).
    function plainText(md) {
        return (md || '').replace(/\*\*(.+?)\*\*/g, '$1').replace(/`([^`]+)`/g, '$1')
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1').replace(/^\s*-\s+/gm, '').replace(/\s+/g, ' ').trim();
    }

    function blankTemplate(teamId) {
        return { team_id: teamId || '', title: '', description: '', priority: 'medium', label_ids: [], due_in_days: 3, checklist: [], is_onboarding: false };
    }

    WS.templates = { dueFromDays, plainText };

    // Palet warna label (kontras cukup untuk titik warna di atas putih).
    const LABEL_COLORS = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#ef4444', '#8b5cf6', '#64748b'];

    // Perbarui daftar label milik sebuah item (kartu tugas, template) setelah label tim berubah:
    // nama/warna mengikuti data baru, label yang dihapus dibuang.
    function applyLabels(itemLabels, labels) {
        const byId = new Map(labels.map((l) => [l.id, l]));
        return (itemLabels || []).filter((l) => byId.has(l.id)).map((l) => Object.assign({}, byId.get(l.id)));
    }

    WS.labels = { COLORS: LABEL_COLORS, apply: applyLabels };

    document.addEventListener('alpine:init', () => {
        const Alpine = window.Alpine;

        // ---------- Halaman daftar template ----------
        // config: { templates, teams: {id,name}[] }
        Alpine.data('wsTemplatesPage', (config) => ({
            templates: config.templates,
            teams: config.teams,
            q: '',
            filter: 'all', // all | onboarding | other
            team: '',

            init() {
                window.addEventListener('ws-template-saved', (e) => {
                    const t = e.detail.template;
                    const i = this.templates.findIndex((x) => x.id === t.id);
                    if (i === -1) this.templates.unshift(t);
                    else this.templates.splice(i, 1, Object.assign({}, this.templates[i], t));
                });
                window.addEventListener('ws-template-deleted', (e) => {
                    this.templates = this.templates.filter((x) => x.id !== e.detail.id);
                });
                window.addEventListener('ws-labels-changed', (e) => {
                    this.templates.forEach((t) => {
                        if (t.team.id === e.detail.teamId) t.labels = applyLabels(t.labels, e.detail.labels);
                    });
                });
            },

            manageLabels() {
                emit('ws-open-labels', { teamId: this.team ? Number(this.team) : (this.teams[0] && this.teams[0].id) });
            },

            get visible() {
                const q = this.q.trim().toLowerCase();
                return this.templates.filter((t) => {
                    if (this.filter === 'onboarding' && !t.is_onboarding) return false;
                    if (this.filter === 'other' && t.is_onboarding) return false;
                    if (this.team && t.team.id !== Number(this.team)) return false;
                    return !q || t.title.toLowerCase().includes(q) || (t.description || '').toLowerCase().includes(q);
                });
            },

            count(filter) {
                if (filter === 'onboarding') return this.templates.filter((t) => t.is_onboarding).length;
                if (filter === 'other') return this.templates.filter((t) => !t.is_onboarding).length;
                return this.templates.length;
            },

            get filtering() {
                return !!(this.q.trim() || this.filter !== 'all' || this.team);
            },

            reset() {
                this.q = '';
                this.filter = 'all';
                this.team = '';
            },

            create() {
                emit('ws-open-template-form', { teamId: this.team ? Number(this.team) : null });
            },
            edit: (t) => emit('ws-open-template-form', { template: t }),
            duplicate: (t) => emit('ws-open-template-form', { template: Object.assign({}, t, { id: null, title: t.title + ' (salinan)', is_onboarding: false }) }),
            assign: (t) => emit('ws-open-template-assign', { template: t }),
            remove: (t) => emit('ws-open-template-delete', { template: t }),

            excerpt: (t) => plainText(t.description),
            priorityIcon: (p) => ({ low: 'fa-arrow-down', medium: 'fa-equals', high: 'fa-arrow-up', urgent: 'fa-fire' }[p] || 'fa-flag'),
            priorityLabel: (p) => WS.PRIORITY[p] || p,
            relative: (iso) => WS.time.relative(iso),
        }));

        // ---------- Form buat / edit template ----------
        // config: { teams: {id,name}[], labels, canChooseTeam: bool, defaultTeamId }
        Alpine.data('wsTemplateForm', (config) => ({
            open: false,
            editing: null,   // template yang diedit (punya id)
            fromTask: null,  // TaskDetail saat "Simpan sebagai template"
            form: blankTemplate(config.defaultTeamId),
            errors: {},
            submitted: false,
            saving: false,
            teams: config.teams,
            labels: config.labels,
            canChooseTeam: config.canChooseTeam,

            init() {
                window.addEventListener('ws-open-template-form', (e) => this.start(e.detail || {}));
                window.addEventListener('ws-labels-changed', (e) => {
                    this.labels = e.detail.labels;
                    const ids = new Set(e.detail.labels.map((l) => l.id));
                    this.form.label_ids = this.form.label_ids.filter((id) => ids.has(id));
                });
                this.$watch('form', () => { if (this.submitted) this.validate(); });
            },

            start({ template, fromTask, teamId }) {
                this.editing = template && template.id ? template : null;
                this.fromTask = fromTask || null;
                this.submitted = false;
                this.errors = {};
                const src = template || {};
                if (fromTask) {
                    const created = fromTask.start_at || fromTask.activities?.at(-1)?.created_at;
                    const days = created ? Math.max(1, Math.round((new Date(fromTask.due_at) - new Date(created)) / 86400000)) : 3;
                    this.form = Object.assign(blankTemplate(config.defaultTeamId), {
                        title: fromTask.title,
                        description: fromTask.description || '',
                        priority: fromTask.priority,
                        label_ids: (fromTask.labels || []).map((l) => l.id),
                        due_in_days: Math.min(days, 90),
                        checklist: (fromTask.checklist_items || []).map((c) => ({ content: c.content })),
                    });
                } else {
                    this.form = {
                        team_id: src.team ? src.team.id : (teamId || config.defaultTeamId || (this.teams.length === 1 ? this.teams[0].id : '')),
                        title: src.title || '',
                        description: src.description || '',
                        priority: src.priority || 'medium',
                        label_ids: (src.labels || []).map((l) => l.id),
                        due_in_days: src.due_in_days ?? 3,
                        checklist: (src.checklist || []).map((c) => ({ content: c.content })),
                        is_onboarding: !!src.is_onboarding,
                    };
                }
                this.open = true;
            },

            get heading() {
                if (this.editing) return 'Edit template';
                if (this.fromTask) return 'Simpan ' + this.fromTask.code + ' sebagai template';
                return 'Template baru';
            },

            toggleLabel(id) {
                const i = this.form.label_ids.indexOf(id);
                if (i === -1) this.form.label_ids.push(id);
                else this.form.label_ids.splice(i, 1);
            },

            addChecklist() {
                this.form.checklist.push({ content: '' });
                this.$nextTick(() => {
                    const inputs = this.$root.querySelectorAll('[data-tpl-checklist]');
                    inputs[inputs.length - 1]?.focus();
                });
            },

            validate() {
                const f = this.form;
                const errors = {};
                if (!f.team_id) errors.team_id = 'Pilih tim.';
                if (!f.title.trim()) errors.title = 'Judul wajib diisi.';
                else if (f.title.length > 150) errors.title = 'Judul maksimal 150 karakter.';
                const days = Number(f.due_in_days);
                if (f.due_in_days === '' || !Number.isInteger(days) || days < 0 || days > 90) errors.due_in_days = 'Isi 0–90 hari.';
                this.errors = errors;
                return !Object.keys(errors).length;
            },

            payload() {
                const f = this.form;
                return {
                    team_id: Number(f.team_id),
                    title: f.title.trim(),
                    description: f.description,
                    priority: f.priority,
                    label_ids: f.label_ids,
                    due_in_days: Number(f.due_in_days),
                    checklist: f.checklist.filter((c) => c.content.trim()).map((c) => ({ content: c.content.trim() })),
                    is_onboarding: f.is_onboarding,
                };
            },

            async submit() {
                this.submitted = true;
                if (!this.validate()) {
                    this.$nextTick(() => this.$root.querySelector('.ws-input--error')?.focus());
                    return;
                }
                this.saving = true;
                try {
                    const res = this.editing
                        ? await WS.api.templates.update(this.editing.id, this.payload())
                        : await WS.api.templates.create(this.payload());
                    emit('ws-template-saved', { template: res.data });
                    WS.toast(this.editing ? 'Template diperbarui.' : `Template "${res.data.title}" disimpan.`, 'success');
                    this.open = false;
                } catch (err) {
                    if (err.status === 422) this.errors = Object.fromEntries(Object.entries(err.errors).map(([k, v]) => [k, v[0]]));
                    else WS.toast(err.message, 'error');
                } finally {
                    this.saving = false;
                }
            },
        }));

        // ---------- Tugaskan massal ----------
        // config: { interns: [{ id, name, avatar_url, team, group }] } — tugas dibuat di kelompok tiap intern
        Alpine.data('wsTemplateAssign', (config) => ({
            open: false,
            template: null,
            selected: [],
            q: '',
            dueAt: '',
            startAt: '',
            errors: {},
            submitted: false,
            saving: false,
            result: null, // { data: TaskCard[] } setelah berhasil

            init() {
                window.addEventListener('ws-open-template-assign', (e) => this.start(e.detail.template));
                this.$watch('selected', () => { if (this.submitted) this.validate(); });
                this.$watch('dueAt', () => { if (this.submitted) this.validate(); });
            },

            start(template) {
                this.template = template;
                this.selected = [];
                this.q = '';
                this.dueAt = dueFromDays(template.due_in_days);
                this.startAt = '';
                this.errors = {};
                this.submitted = false;
                this.result = null;
                this.open = true;
            },

            // Intern di tim template (mentor hanya menerima intern timnya dari server).
            get candidates() {
                if (!this.template) return [];
                const q = this.q.trim().toLowerCase();
                return config.interns.filter((i) => i.team.id === this.template.team.id && (!q || i.name.toLowerCase().includes(q)));
            },

            get allSelected() {
                return this.candidates.length > 0 && this.candidates.every((i) => this.selected.includes(i.id));
            },

            toggleAll() {
                const ids = this.candidates.map((i) => i.id);
                this.selected = this.allSelected
                    ? this.selected.filter((id) => !ids.includes(id))
                    : [...new Set(this.selected.concat(ids))];
            },

            toggle(id) {
                const i = this.selected.indexOf(id);
                if (i === -1) this.selected.push(id);
                else this.selected.splice(i, 1);
            },

            validate() {
                const errors = {};
                if (!this.selected.length) errors.assignee_ids = 'Pilih minimal satu intern.';
                if (!this.dueAt) errors.due_at = 'Deadline wajib diisi.';
                else if (this.startAt && new Date(this.startAt) > new Date(this.dueAt)) errors.due_at = 'Deadline harus setelah tanggal mulai.';
                this.errors = errors;
                return !Object.keys(errors).length;
            },

            async submit() {
                this.submitted = true;
                if (!this.validate()) return;
                this.saving = true;
                try {
                    const res = await WS.api.templates.assign(this.template.id, {
                        assignee_ids: this.selected,
                        due_at: fromLocalInput(this.dueAt),
                        start_at: fromLocalInput(this.startAt),
                    });
                    this.result = res;
                    if (res.meta && res.meta.template) emit('ws-template-saved', { template: res.meta.template });
                    WS.toast(`${res.data.length} tugas dibuat dari "${this.template.title}".`, 'success');
                } catch (err) {
                    if (err.status === 422) this.errors = Object.fromEntries(Object.entries(err.errors).map(([k, v]) => [k, v[0]]));
                    else WS.toast(err.message, 'error');
                } finally {
                    this.saving = false;
                }
            },

            boardUrl: (card) => card.workspace.url + '?task=' + encodeURIComponent(card.code),
            dueLabel: (value) => (value ? WS.time.dateTime(new Date(value).toISOString()) : ''),
        }));

        // ---------- Label tim ----------
        // config: { teams: {id,name}[], labelsByTeam: { [teamId]: Label[] } }
        Alpine.data('wsLabelManager', (config) => ({
            open: false,
            teams: config.teams,
            labelsByTeam: config.labelsByTeam,
            teamId: null,
            draft: { name: '', color: LABEL_COLORS[0] },
            editingId: null,
            edit: { name: '', color: '' },
            confirmId: null,
            errors: {},
            busy: false,
            colors: LABEL_COLORS,

            init() {
                window.addEventListener('ws-open-labels', (e) => {
                    this.teamId = (e.detail && e.detail.teamId) || (this.teams[0] && this.teams[0].id);
                    this.reset();
                    this.open = true;
                });
            },

            get labels() {
                return this.labelsByTeam[this.teamId] || [];
            },

            get teamName() {
                const t = this.teams.find((x) => x.id === Number(this.teamId));
                return t ? t.name : '';
            },

            reset() {
                this.draft = { name: '', color: LABEL_COLORS[this.labels.length % LABEL_COLORS.length] };
                this.editingId = null;
                this.confirmId = null;
                this.errors = {};
            },

            check(name, exceptId) {
                const n = name.trim();
                if (!n) return 'Nama label wajib diisi.';
                if (n.length > 30) return 'Maksimal 30 karakter.';
                if (this.labels.some((l) => l.id !== exceptId && l.name.toLowerCase() === n.toLowerCase())) return 'Label dengan nama ini sudah ada.';
                return '';
            },

            publish(list) {
                this.labelsByTeam[this.teamId] = list;
                emit('ws-labels-changed', { teamId: Number(this.teamId), labels: JSON.parse(JSON.stringify(list)) });
            },

            async add() {
                const error = this.check(this.draft.name);
                this.errors = error ? { name: error } : {};
                if (error) return;
                this.busy = true;
                try {
                    const res = await WS.api.labels.create(Number(this.teamId), { name: this.draft.name.trim(), color: this.draft.color });
                    this.publish(this.labels.concat(res.data));
                    WS.toast(`Label "${res.data.name}" ditambahkan.`, 'success');
                    this.reset();
                    this.$nextTick(() => this.$root.querySelector('[data-label-new]')?.focus());
                } catch (err) {
                    if (err.status === 422) this.errors = { name: (err.errors.name || [err.message])[0] };
                    else WS.toast(err.message, 'error');
                } finally {
                    this.busy = false;
                }
            },

            startEdit(l) {
                this.editingId = l.id;
                this.confirmId = null;
                this.edit = { name: l.name, color: l.color };
                this.errors = {};
                this.$nextTick(() => this.$root.querySelector('[data-label-edit]')?.focus());
            },

            async saveEdit(l) {
                const error = this.check(this.edit.name, l.id);
                this.errors = error ? { edit: error } : {};
                if (error) return;
                this.busy = true;
                try {
                    const res = await WS.api.labels.update(l.id, { name: this.edit.name.trim(), color: this.edit.color });
                    this.publish(this.labels.map((x) => (x.id === l.id ? res.data : x)));
                    this.editingId = null;
                } catch (err) {
                    if (err.status === 422) this.errors = { edit: (err.errors.name || [err.message])[0] };
                    else WS.toast(err.message, 'error');
                } finally {
                    this.busy = false;
                }
            },

            async remove(l) {
                if (this.confirmId !== l.id) { this.confirmId = l.id; return; }
                this.busy = true;
                try {
                    await WS.api.labels.remove(l.id);
                    this.publish(this.labels.filter((x) => x.id !== l.id));
                    WS.toast(`Label "${l.name}" dihapus dan dilepas dari semua tugas.`, 'success');
                    this.confirmId = null;
                } catch (err) {
                    WS.toast(err.message, 'error');
                } finally {
                    this.busy = false;
                }
            },
        }));

        // ---------- Hapus template ----------
        Alpine.data('wsTemplateDelete', () => ({
            open: false,
            template: null,
            removing: false,

            init() {
                window.addEventListener('ws-open-template-delete', (e) => { this.template = e.detail.template; this.open = true; });
            },

            async confirm() {
                this.removing = true;
                try {
                    await WS.api.templates.remove(this.template.id);
                    emit('ws-template-deleted', { id: this.template.id });
                    WS.toast('Template dihapus. Tugas yang sudah dibuat dari template ini tidak berubah.', 'success');
                    this.open = false;
                } catch (err) {
                    WS.toast(err.message, 'error');
                } finally {
                    this.removing = false;
                }
            },
        }));
    });
})();
