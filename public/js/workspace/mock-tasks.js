/*
 * Workspace — mock endpoint tugas & lampiran (hanya mode preview).
 * Detail diambil sekali dari route preview (GET /workspace/tasks/{id}, JSON dari PHP fixture),
 * lalu semua perubahan disimpan di memori browser — hilang saat halaman dimuat ulang.
 * Aturan yang seharusnya ditegakkan backend ditiru di sini (403/422) supaya UI teruji.
 */
(function () {
    'use strict';

    const WS = window.WS;
    const { define, delay, clone } = WS.mock;
    const me = WS.config.user || { id: 0, name: 'Anda', role: 'intern' };
    const isStaff = me.role === 'admin' || me.role === 'mentor';
    const meMini = { id: me.id, name: me.name, role: me.role, avatar_url: null };

    const CARD_KEYS = ['id', 'workspace_id', 'code', 'title', 'status', 'priority', 'position', 'start_at', 'due_at',
        'completed_at', 'is_late', 'labels', 'assignees', 'checklist', 'comments_count', 'attachments_count', 'url', 'can'];

    const tasks = new Map();   // id → TaskDetail (atau TaskCard bila detail belum diambil, _full = false)
    let context = { interns: [], labels: [], workspace: null }; // di-seed halaman: 'task-context'
    let seq = 900000;
    const uploads = new Map(); // attachment id → Attachment

    const forbidden = (msg) => new WS.HttpError(403, msg || 'Anda tidak memiliki akses untuk aksi ini.');
    const invalid = (errors) => new WS.HttpError(422, Object.values(errors)[0][0], errors);
    const now = () => new Date().toISOString();

    function toCard(task) {
        const card = {};
        CARD_KEYS.forEach((k) => { card[k] = clone(task[k]); });
        return card;
    }

    // Markdown sederhana yang aman (meniru Str::markdown + html_input=escape di server).
    function md(text) {
        const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const inline = (s) => esc(s)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" rel="nofollow noopener" target="_blank">$1</a>')
            .replace(/#(T-\d+)/g, '<a href="#">#$1</a>');
        return (text || '').trim().split(/\n{2,}/).map((block) => {
            const lines = block.split('\n');
            if (lines.every((l) => /^\s*-\s+/.test(l))) {
                return '<ul>' + lines.map((l) => '<li>' + inline(l.replace(/^\s*-\s+/, '')) + '</li>').join('') + '</ul>';
            }
            return '<p>' + lines.map(inline).join('<br>') + '</p>';
        }).join('');
    }

    function activity(task, action, meta, user = meMini) {
        task.activities = task.activities || [];
        task.activities.unshift({
            id: ++seq, user, action, meta: meta || {},
            task: { id: task.id, code: task.code, title: task.title, url: task.url },
            created_at: now(),
        });
    }

    async function ensure(id) {
        let task = tasks.get(id);
        if (task && task._full) return task;
        const res = await WS.http('GET', (WS.config.baseUrl || '') + '/workspace/tasks/' + id);
        const detail = res.data;
        if (task) Object.assign(detail, toCard(task)); // perubahan lokal (mis. status) menang
        detail._full = true;
        tasks.set(id, detail);
        return detail;
    }

    function assertMovable(task) {
        if (!task.can.move) throw forbidden('Anda hanya dapat memindahkan tugas yang di-assign ke Anda.');
    }

    function validateTask(p, partial) {
        const errors = {};
        if (!partial || 'title' in p) {
            if (!p.title || !p.title.trim()) errors.title = ['Judul wajib diisi.'];
            else if (p.title.length > 150) errors.title = ['Judul maksimal 150 karakter.'];
        }
        if (!partial || 'assignee_ids' in p) {
            if (!p.assignee_ids || !p.assignee_ids.length) errors.assignee_ids = ['Pilih minimal satu intern.'];
        }
        if (!partial || 'due_at' in p) {
            if (!p.due_at) errors.due_at = ['Deadline wajib diisi.'];
            else if (p.start_at && new Date(p.start_at) > new Date(p.due_at)) errors.due_at = ['Deadline harus setelah tanggal mulai.'];
        }
        (p.result_links || []).forEach((l, i) => {
            if (!/^https?:\/\/\S+$/.test(l.url || '')) errors[`result_links.${i}.url`] = ['URL tidak valid.'];
        });
        if (Object.keys(errors).length) throw invalid(errors);
    }

    function applyPayload(task, p) {
        const internById = new Map(context.interns.map((i) => [i.id, i]));
        const labelById = new Map(context.labels.map((l) => [l.id, l]));
        if ('title' in p) task.title = p.title.trim();
        if ('description' in p) { task.description = p.description || ''; task.description_html = md(task.description); }
        if ('priority' in p) task.priority = p.priority;
        if ('start_at' in p) task.start_at = p.start_at || null;
        if ('due_at' in p) {
            task.due_at = p.due_at;
            task.is_late = task.status !== 'done' && new Date(p.due_at) < new Date();
        }
        if ('assignee_ids' in p) task.assignees = p.assignee_ids.map((id) => internById.get(id)).filter(Boolean);
        if ('label_ids' in p) task.labels = (p.label_ids || []).map((id) => labelById.get(id)).filter(Boolean);
        if ('checklist_items' in p) {
            task.checklist_items = p.checklist_items.filter((c) => c.content.trim()).map((c, i) => ({
                id: c.id || ++seq, content: c.content.trim(), is_done: !!c.is_done, position: i,
            }));
        }
        if ('result_links' in p) task.result_links = p.result_links.filter((l) => l.url);
        if (p.attachment_ids && p.attachment_ids.length) {
            const added = p.attachment_ids.map((id) => uploads.get(id)).filter(Boolean)
                .filter((a) => !(task.attachments || []).some((x) => x.id === a.id));
            task.attachments = (task.attachments || []).concat(added);
            added.forEach((a) => activity(task, 'attachment_added', { file: a.original_name }));
        }
        const items = task.checklist_items || [];
        task.checklist = { done: items.filter((c) => c.is_done).length, total: items.length };
        task.attachments_count = (task.attachments || []).length;
    }

    // ---------- Endpoint ----------

    WS.mock.seed = (function (original) {
        return function (key, value) {
            if (key === 'tasks') {
                value.forEach((card) => { if (!tasks.has(card.id)) tasks.set(card.id, Object.assign(clone(card), { _full: false })); });
                return;
            }
            if (key === 'task-context') {
                context = Object.assign({ interns: [], labels: [], workspace: null }, clone(value));
                return;
            }
            original(key, value);
        };
    })(WS.mock.seed);

    define('tasks.show', async (id) => {
        await delay(250);
        const task = await ensure(id);
        return { data: clone(task) };
    });

    define('tasks.move', async (id, p) => {
        await delay(300);
        const task = await ensure(id);
        assertMovable(task);
        if (!isStaff && (p.status === 'done' || p.status === 'revision')) {
            throw forbidden('Hanya mentor atau admin yang dapat menandai Selesai atau Revisi.');
        }
        if (!isStaff && task.status === 'review') throw forbidden('Tugas sedang direview mentor.');
        const from = task.status;
        if (p.status === 'review' && (p.result_links || p.attachment_ids)) {
            applyPayload(task, { result_links: (task.result_links || []).concat(p.result_links || []), attachment_ids: p.attachment_ids });
        }
        if (p.note && p.note.trim()) {
            task.comments = (task.comments || []).concat({
                id: ++seq, user: meMini, body: p.note, body_html: md(p.note), attachments: [], created_at: now(), is_deleted: false, can: { delete: true },
            });
            task.comments_count = task.comments.length;
        }
        task.status = p.status;
        if (from !== p.status) activity(task, 'status_changed', { from, to: p.status });
        // Urutan kolom tujuan
        (p.column_order || [task.id]).forEach((tid, i) => { const t = tasks.get(tid); if (t) { t.status = p.status; t.position = i; } });
        if (!p.column_order) task.position = -1;
        return { data: toCard(task) };
    });

    define('tasks.review', async (id, p) => {
        await delay(350);
        const task = await ensure(id);
        if (!isStaff || !task.can.review) throw forbidden('Hanya mentor atau admin yang dapat mereview tugas.');
        const errors = {};
        if (p.decision === 'revision' && !(p.feedback || '').trim()) errors.feedback = ['Catatan revisi wajib diisi.'];
        if (p.score != null && p.score !== '' && (p.score < 0 || p.score > 100)) errors.score = ['Nilai harus 0–100.'];
        if (Object.keys(errors).length) throw invalid(errors);
        const approve = p.decision === 'approve';
        task.status = approve ? 'done' : 'revision';
        task.position = -1;
        task.score = approve && p.score !== '' && p.score != null ? Number(p.score) : null;
        task.feedback = (p.feedback || '').trim() || null;
        task.feedback_html = task.feedback ? md(task.feedback) : null;
        task.completed_at = approve ? now() : null;
        if (approve) task.is_late = false;
        activity(task, 'reviewed', approve ? { decision: 'approve', score: task.score } : { decision: 'revision' });
        return { data: clone(task) };
    });

    define('tasks.create', async (workspaceId, p) => {
        await delay(400);
        if (!isStaff && me.role !== 'intern') throw forbidden();
        validateTask(p, false);
        // separate_copies: satu tugas per assignee (assign massal); selain itu satu tugas bersama.
        const groups = p.separate_copies && p.assignee_ids.length > 1 ? p.assignee_ids.map((id) => [id]) : [p.assignee_ids];
        return { data: groups.map((ids) => createOne(workspaceId, Object.assign({}, p, { assignee_ids: ids }))) };
    });

    function createOne(workspaceId, p) {
        const numbers = [...tasks.values()].filter((t) => t.workspace_id === workspaceId).map((t) => Number(t.code.slice(2)));
        const n = (numbers.length ? Math.max(...numbers) : 0) + 1;
        const id = workspaceId * 100 + n;
        const task = {
            id, workspace_id: workspaceId, code: 'T-' + n, status: 'todo', position: -1, completed_at: null,
            is_late: false, labels: [], assignees: [], comments_count: 0, url: (WS.config.baseUrl || '') + '/workspace/tasks/' + id,
            creator: meMini, workspace: context.workspace, checklist_items: [], attachments: [], result_links: [],
            score: null, feedback: null, feedback_html: null, comments: [], activities: [], description: '', description_html: '',
            _full: true, _local: true,
        };
        applyPayload(task, p);
        const mine = task.assignees.some((a) => a.user_id === me.id);
        task.can = { update: true, move: isStaff || mine, review: isStaff, delete: isStaff, comment: true };
        activity(task, 'created');
        task.assignees.forEach((a) => activity(task, 'assigned', { assignee: a.name }));
        tasks.set(id, task);
        return toCard(task);
    }

    define('tasks.update', async (id, p) => {
        await delay(350);
        const task = await ensure(id);
        if (!task.can.update) throw forbidden('Anda tidak dapat mengubah tugas ini.');
        validateTask(p, true);
        applyPayload(task, p);
        activity(task, 'updated');
        return { data: clone(task) };
    });

    define('tasks.remove', async (id) => {
        await delay(300);
        const task = tasks.get(id) || await ensure(id);
        if (!task.can.delete) throw forbidden('Anda tidak dapat menghapus tugas ini.');
        tasks.delete(id);
        return null;
    });

    define('tasks.comment', async (id, p) => {
        await delay(300);
        const task = await ensure(id);
        if (!(p.body || '').trim()) throw invalid({ body: ['Komentar tidak boleh kosong.'] });
        const comment = {
            id: ++seq, user: meMini, body: p.body, body_html: md(p.body), attachments: [], created_at: now(),
            is_deleted: false, can: { delete: true },
        };
        task.comments = (task.comments || []).concat(comment);
        task.comments_count = task.comments.length;
        activity(task, 'commented');
        return { data: clone(comment) };
    });

    define('tasks.toggleChecklist', async (taskId, itemId, p) => {
        await delay(200);
        const task = await ensure(taskId);
        if (!task.can.move && !task.can.update) throw forbidden();
        const item = (task.checklist_items || []).find((c) => c.id === itemId);
        if (!item) throw new WS.HttpError(404, 'Item checklist tidak ditemukan.');
        item.is_done = !!p.is_done;
        task.checklist = { done: task.checklist_items.filter((c) => c.is_done).length, total: task.checklist_items.length };
        return { data: clone(task.checklist) };
    });

    // ---------- Upload lampiran ----------

    const ALLOWED = /\.(pdf|docx?|xlsx?|png|jpe?g|zip)$/i;

    define('attachments.upload', async (formData, onProgress) => {
        const file = formData.get('file');
        if (!file) throw invalid({ file: ['Pilih file.'] });
        if (!ALLOWED.test(file.name)) throw invalid({ file: ['Tipe file tidak didukung.'] });
        if (file.size > 10 * 1024 * 1024) throw invalid({ file: ['Ukuran file maksimal 10 MB.'] });
        for (let p = 0; p <= 100; p += 20) {
            if (onProgress) onProgress(p);
            await delay(120);
        }
        const isImage = /^image\//.test(file.type);
        const attachment = {
            id: ++seq, original_name: file.name, mime: file.type || 'application/octet-stream', size: file.size,
            is_image: isImage, thumbnail_url: isImage ? URL.createObjectURL(file) : null, download_url: URL.createObjectURL(file),
        };
        uploads.set(attachment.id, attachment);
        return { data: clone(attachment) };
    });

    // Realtime tiruan: ±25 detik setelah dibuka, intern lain mulai mengerjakan satu tugas To Do.
    if (WS.config.realtime && WS.config.realtime.driver === 'mock') {
        setTimeout(() => {
            const card = [...tasks.values()].find((t) => t.status === 'todo' && !t.assignees.some((a) => a.user_id === me.id));
            if (!card || !context.workspace) return;
            card.status = 'in_progress';
            WS.realtime.emit('workspace.' + context.workspace.id, 'task.changed', { card: toCard(card) });
        }, 25000);
    }

    // Label tim diubah lewat pengelola label: tugas baru/diedit memakai daftar terbaru.
    window.addEventListener('ws-labels-changed', (e) => { context.labels = clone(e.detail.labels); });

    // Dipakai mock-channels.js (render pesan & lampiran pesan).
    WS.mock.md = md;
    WS.mock.attachment = (id) => (uploads.has(id) ? clone(uploads.get(id)) : null);
})();
