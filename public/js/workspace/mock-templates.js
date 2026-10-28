/*
 * Workspace — mock endpoint template tugas (hanya mode preview).
 * Halaman men-seed 'template-context' { templates, interns, labels, teams }.
 * Aturan yang seharusnya ditegakkan backend (403/422) ditiru supaya UI teruji. Perubahan hanya di memori.
 */
(function () {
    'use strict';

    const WS = window.WS;
    const { define, delay, clone } = WS.mock;
    const me = WS.config.user || { id: 0, name: 'Anda', role: 'intern' };
    const isStaff = me.role === 'admin' || me.role === 'mentor';
    const meMini = { id: me.id, name: me.name, role: me.role, avatar_url: null };

    let ctx = { templates: [], interns: [], labels: [], teams: [] };
    const labelsByTeam = new Map(); // teamId → Label[] (di-seed 'label-context')
    let seq = 5000;
    let taskSeq = 60; // nomor T-n untuk tugas hasil assign (di atas nomor fixture)

    const forbidden = (msg) => new WS.HttpError(403, msg || 'Anda tidak memiliki akses untuk aksi ini.');
    const invalid = (errors) => new WS.HttpError(422, Object.values(errors)[0][0], errors);
    const now = () => new Date().toISOString();

    WS.mock.seed = (function (original) {
        return function (key, value) {
            if (key === 'label-context') {
                (value.teams || []).forEach((t) => { if (!labelsByTeam.has(t.id)) labelsByTeam.set(t.id, clone(value.labels || [])); });
                return;
            }
            if (key === 'template-context') {
                ctx = Object.assign({ templates: [], interns: [], labels: [], teams: [] }, clone(value));
                return;
            }
            return original(key, value);
        };
    })(WS.mock.seed);

    function find(id) {
        const t = ctx.templates.find((x) => x.id === Number(id));
        if (!t) throw new WS.HttpError(404, 'Template tidak ditemukan.');
        return t;
    }

    function validate(p) {
        const errors = {};
        if (!p.team_id) errors.team_id = ['Pilih tim.'];
        if (!p.title || !p.title.trim()) errors.title = ['Judul wajib diisi.'];
        else if (p.title.length > 150) errors.title = ['Judul maksimal 150 karakter.'];
        if (!Number.isInteger(p.due_in_days) || p.due_in_days < 0 || p.due_in_days > 90) errors.due_in_days = ['Isi 0–90 hari.'];
        if (Object.keys(errors).length) throw invalid(errors);
    }

    function apply(t, p) {
        const labelById = new Map(ctx.labels.map((l) => [l.id, l]));
        Object.assign(t, {
            team: ctx.teams.find((x) => x.id === p.team_id) || t.team || { id: p.team_id, name: 'Tim' },
            title: p.title.trim(),
            description: p.description || '',
            description_html: WS.mock.md ? WS.mock.md(p.description || '') : '',
            priority: p.priority,
            labels: (p.label_ids || []).map((id) => labelById.get(id)).filter(Boolean),
            due_in_days: p.due_in_days,
            checklist: (p.checklist || []).map((c) => ({ content: c.content })),
            is_onboarding: !!p.is_onboarding,
            updated_at: now(),
        });
    }

    // ---------- Label tim ----------

    function labelOwner(id) {
        for (const [teamId, list] of labelsByTeam) {
            const label = list.find((l) => l.id === Number(id));
            if (label) return { teamId, list, label };
        }
        throw new WS.HttpError(404, 'Label tidak ditemukan.');
    }

    function validateLabel(list, p, exceptId) {
        const name = (p.name || '').trim();
        if (!name) throw invalid({ name: ['Nama label wajib diisi.'] });
        if (name.length > 30) throw invalid({ name: ['Maksimal 30 karakter.'] });
        if (list.some((l) => l.id !== exceptId && l.name.toLowerCase() === name.toLowerCase())) throw invalid({ name: ['Label dengan nama ini sudah ada.'] });
        if (!/^#[0-9a-f]{6}$/i.test(p.color || '')) throw invalid({ color: ['Warna tidak valid.'] });
    }

    // Label baru/berubah juga dipakai mock lain (form template di halaman yang sama).
    window.addEventListener('ws-labels-changed', (e) => { ctx.labels = clone(e.detail.labels); });

    define('labels.create', async (teamId, p) => {
        await delay(250);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat mengelola label.');
        const list = labelsByTeam.get(Number(teamId)) || [];
        validateLabel(list, p);
        const label = { id: ++seq, name: p.name.trim(), color: p.color };
        list.push(label);
        labelsByTeam.set(Number(teamId), list);
        return { data: clone(label) };
    });

    define('labels.update', async (id, p) => {
        await delay(220);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat mengelola label.');
        const { list, label } = labelOwner(id);
        validateLabel(list, p, label.id);
        Object.assign(label, { name: p.name.trim(), color: p.color });
        return { data: clone(label) };
    });

    define('labels.remove', async (id) => {
        await delay(220);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat mengelola label.');
        const { teamId, list, label } = labelOwner(id);
        labelsByTeam.set(teamId, list.filter((l) => l !== label));
        return null;
    });

    define('templates.create', async (p) => {
        await delay(350);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat membuat template.');
        validate(p);
        const t = { id: ++seq, creator: meMini, usage_count: 0, can: { update: true, delete: true, assign: true } };
        apply(t, p);
        ctx.templates.unshift(t);
        return { data: clone(t) };
    });

    define('templates.update', async (id, p) => {
        await delay(300);
        const t = find(id);
        if (!t.can.update) throw forbidden('Anda hanya dapat mengubah template tim sendiri.');
        validate(p);
        apply(t, p);
        return { data: clone(t) };
    });

    define('templates.remove', async (id) => {
        await delay(250);
        const t = find(id);
        if (!t.can.delete) throw forbidden('Anda hanya dapat menghapus template tim sendiri.');
        ctx.templates = ctx.templates.filter((x) => x !== t);
        return null;
    });

    define('templates.assign', async (id, p) => {
        await delay(600);
        const t = find(id);
        if (!t.can.assign) throw forbidden('Anda hanya dapat menugaskan template tim sendiri.');
        const errors = {};
        const ids = p.assignee_ids || [];
        if (!ids.length) errors.assignee_ids = ['Pilih minimal satu intern.'];
        if (!p.due_at) errors.due_at = ['Deadline wajib diisi.'];
        else if (p.start_at && new Date(p.start_at) > new Date(p.due_at)) errors.due_at = ['Deadline harus setelah tanggal mulai.'];
        const interns = ids.map((x) => ctx.interns.find((i) => i.id === x));
        if (interns.some((i) => !i || !i.group)) errors.assignee_ids = ['Ada intern yang belum punya kelompok.'];
        if (Object.keys(errors).length) throw invalid(errors);

        const created = interns.map((intern) => {
            const ws = intern.group;
            const n = ++taskSeq;
            const taskId = ws.id * 100 + n;
            return {
                id: taskId, workspace_id: ws.id, code: 'T-' + n, title: t.title, status: 'todo', priority: t.priority, position: 0,
                start_at: p.start_at || null, due_at: p.due_at, completed_at: null, is_late: false, labels: clone(t.labels),
                assignees: [{ id: intern.id, user_id: intern.user_id, name: intern.name, avatar_url: intern.avatar_url }],
                checklist: { done: 0, total: t.checklist.length }, comments_count: 0, attachments_count: 0,
                url: (WS.config.baseUrl || '') + '/workspace/tasks/' + taskId,
                workspace: { id: ws.id, name: ws.name, url: ws.url },
                can: { update: true, move: true, review: true, delete: true },
            };
        });
        t.usage_count += created.length;
        return { data: created, meta: { created: created.length, template: clone(t) } };
    });
})();
