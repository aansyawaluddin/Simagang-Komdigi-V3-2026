/*
 * Workspace — mock endpoint channel & pesan (hanya mode preview).
 * Halaman men-seed 'channel-context' { workspace, members, channels, tasks }. Pesan contoh dibuat
 * deterministik per channel (id = channel_id * 1000 + n, cocok dengan last_read_message_id dari fixture PHP).
 * Setelah halaman dibuka, anggota lain "mengirim" pesan berkala supaya polling, badge unread,
 * dan notifikasi mention bisa diuji. Semua perubahan hanya di memori browser.
 * Fase 2: thread (balasan disimpan per pesan induk), pin, pencarian (?q=, ?pinned=1), buat/ubah/arsip channel.
 */
(function () {
    'use strict';

    const WS = window.WS;
    const { define, delay, clone } = WS.mock;
    const me = WS.config.user || { id: 0, name: 'Anda', role: 'intern' };
    const isStaff = me.role === 'admin' || me.role === 'mentor';
    const meMini = { id: me.id, name: me.name, role: me.role, avatar_url: null };
    const ADMIN = { id: 9000, name: 'Admin Simagang', role: 'admin', avatar_url: null };
    const COUNT = { team: 64, topic: 18, direct: 26, announcement: 12 };

    const channels = new Map(); // id → { channel, messages (pesan utama), replies: Map(parentId → balasan[]), seq }
    let channelSeq = 0;
    let context = { workspace: null, members: [], tasks: [] };
    const sentAt = [];          // rate limit kirim pesan (30/menit)
    const failedOnce = new Set();

    const now = () => new Date().toISOString();
    const forbidden = (msg) => new WS.HttpError(403, msg);
    const invalid = (errors) => new WS.HttpError(422, Object.values(errors)[0][0], errors);
    const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const escRe = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    function rng(seed) {
        let a = seed >>> 0;
        return () => {
            a = (a + 0x6D2B79F5) | 0;
            let t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    // ---------- Isi pesan ----------

    const POOLS = {
        mentor: [
            'Selamat pagi semua 👋 Jangan lupa isi logbook harian sebelum pukul 17.00 ya.',
            '@{intern} tolong cek #{task} ya, ada catatan baru dari saya di komentar.',
            'Untuk #{task}, pakai format **laporan** yang sama dengan minggu lalu.',
            'Rapat sinkronisasi hari ini pukul 14.00 di ruang rapat lantai 3.',
            'Hasilnya sudah sesuai, terima kasih. Lanjut ke tugas berikutnya 👍',
            'Ada yang sudah coba endpoint `GET /api/absensi`? Responsnya agak lambat di jam sibuk.',
            'Catatan review hari ini:\n\n- Perhatikan penamaan variabel\n- Tambahkan komentar di fungsi yang kompleks\n- Jangan commit file `.env`',
            'Panduan desain terbaru ada di [Figma tim](https://www.figma.com). Silakan dipelajari dulu.',
        ],
        intern: [
            'Siap kak, saya kerjakan hari ini.',
            'Kak @{mentor}, #{task} sudah saya kirim untuk review.',
            'Izin bertanya kak, untuk #{task} datanya diambil dari tabel absensi lama atau yang baru?',
            { text: 'Saya sudah upload screenshot hasil uji di bawah ini.', image: 'Hasil uji form login' },
            'Teman-teman, ada yang punya akses ke dataset survei terbaru?',
            'Noted kak 🙏',
            'Sudah saya perbaiki validasinya:\n\n- input kosong\n- format email\n- panjang maksimal nama',
            '@{intern} nanti siang kita bahas pembagian #{task} ya.',
            { text: 'Draft laporan mingguan saya lampirkan ya kak.', file: 'laporan-mingguan.pdf' },
        ],
        dmMentor: [
            'Halo {intern}, bagaimana progres minggu ini? Ada kendala?',
            'Untuk #{task} kamu bisa mulai dari bagian yang paling sederhana dulu.',
            'Nanti sore kita review bareng ya, sekitar pukul 15.00.',
            'Bagus, sudah ada kemajuan 👍 Pertahankan.',
            'Jangan sungkan bertanya di sini kalau ada yang belum jelas.',
        ],
        dmIntern: [
            'Halo kak, progres #{task} sudah sekitar 60%.',
            'Ada kendala di bagian koneksi database kak, boleh minta waktu diskusi?',
            'Baik kak, terima kasih atas masukannya 🙏',
            'Siap kak, sore ini saya siapkan.',
            { text: 'Ini tangkapan layar error-nya kak.', image: 'Error koneksi database' },
        ],
        announcement: [
            '**Sharing session** Jumat ini pukul 14.00 di Aula Lantai 2. Materi: etika kerja di instansi pemerintah.',
            'Pengingat: absensi dibuka pukul 07.30–08.30 WIB. Absen di luar jam tersebut tercatat terlambat.',
            'Laporan akhir magang dikumpulkan paling lambat **2 minggu** sebelum masa magang berakhir.',
            '@channel Tanggal 28 Oktober libur nasional, absensi ditiadakan.',
            'Selamat datang untuk peserta magang gelombang baru 🎉 Silakan lengkapi profil dan baca panduan magang.',
            { text: 'Formulir evaluasi mentor sudah dibuka. Panduan pengisian terlampir.', file: 'panduan-evaluasi.pdf' },
        ],
    };

    const REPLIES = [
        'Siap, saya cek dulu.',
        'Sudah saya perbaiki, mohon dicek lagi ya.',
        'Terima kasih infonya 🙏',
        'Apakah deadline-nya tetap hari Jumat?',
        'Saya bantu bagian dokumentasinya.',
        'Noted 👍',
        'Bisa dijelaskan lagi bagian yang kedua?',
    ];

    const INCOMING = {
        mentor: ['@{me} sudah sampai mana progres #{task}?', 'Tim, jangan lupa update status kartu di board ya.', 'Saya baru tambahkan catatan di #{task}.'],
        intern: ['Kak @{me}, boleh minta waktu sebentar untuk diskusi #{task}?', 'Ada yang mau makan siang bareng? 🍜', '#{task} sudah saya pindah ke Review.'],
    };

    function poolFor(channel, author) {
        if (channel.type === 'announcement') return POOLS.announcement;
        if (channel.type === 'direct') return author.role === 'intern' ? POOLS.dmIntern : POOLS.dmMentor;
        return author.role === 'intern' ? POOLS.intern : POOLS.mentor;
    }

    function authorsOf(channel) {
        if (channel.type === 'direct') return channel.members || [];
        if (channel.type === 'announcement') {
            return [me.role === 'admin' ? meMini : ADMIN, context.members.find((m) => m.role === 'mentor')].filter(Boolean);
        }
        return context.members;
    }

    // Orang yang bisa di-mention di channel ini.
    function peopleOf(channel) {
        const list = channel.type === 'direct' ? (channel.members || []) : context.members.concat(authorsOf(channel));
        const seen = new Set();
        return list.filter((p) => !seen.has(p.id) && seen.add(p.id));
    }

    // Isi placeholder; null bila tidak bisa diisi (mis. belum ada tugas untuk #{task}).
    function fill(template, author, channel, r) {
        const people = peopleOf(channel).filter((p) => p.id !== author.id);
        const pick = (list) => list[Math.floor(r() * list.length)];
        const values = {
            mentor: () => (pick(people.filter((p) => p.role === 'mentor')) || pick(people) || {}).name,
            intern: () => (pick(people.filter((p) => p.role === 'intern')) || {}).name,
            me: () => me.name,
            task: () => (channel.workspace_id !== null && context.tasks.length ? pick(context.tasks).code : null),
        };
        let ok = true;
        const text = template.replace(/\{(\w+)\}/g, (_, key) => {
            const v = values[key] && values[key]();
            if (!v) ok = false;
            return v || '';
        });
        return ok ? text : null;
    }

    // `recent`: template yang baru dipakai, dihindari supaya isi channel tidak berulang beruntun.
    function chooseBody(pool, author, channel, r, recent = []) {
        for (let attempt = 0; attempt < 12; attempt++) {
            const entry = pool[Math.floor(r() * pool.length)];
            if (recent.includes(entry) && attempt < 10) continue;
            const tpl = typeof entry === 'string' ? { text: entry } : entry;
            const text = fill(tpl.text, author, channel, r);
            if (!text) continue;
            recent.push(entry);
            if (recent.length > 4) recent.shift();
            return Object.assign({}, tpl, { text });
        }
        return { text: 'Baik, terima kasih.' };
    }

    function svgThumb(label, c1, c2) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="480" height="300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient></defs><rect width="480" height="300" fill="url(#g)"/><rect x="32" y="36" width="260" height="18" rx="9" fill="#ffffff" opacity=".55"/><rect x="32" y="70" width="180" height="12" rx="6" fill="#ffffff" opacity=".35"/><rect x="32" y="110" width="416" height="120" rx="14" fill="#ffffff" opacity=".18"/><text x="32" y="270" font-family="sans-serif" font-size="20" fill="#fff">${esc(label)}</text></svg>`;
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    let attachmentSeq = 700000;
    function attachmentsFor(tpl) {
        if (tpl.image) {
            const thumb = svgThumb(tpl.image, '#2d3ecb', '#8b5cf6');
            return [{ id: ++attachmentSeq, original_name: tpl.image.toLowerCase().replace(/\s+/g, '-') + '.png', mime: 'image/png', size: 182400, is_image: true, thumbnail_url: thumb, download_url: '#' }];
        }
        if (tpl.file) {
            return [{ id: ++attachmentSeq, original_name: tpl.file, mime: 'application/pdf', size: 412300, is_image: false, thumbnail_url: null, download_url: '#' }];
        }
        return [];
    }

    // ---------- Render (meniru renderer server: markdown + mention + rujukan tugas) ----------

    function renderInto(channel, m) {
        if (m.is_deleted) {
            Object.assign(m, { body: '', body_html: '', mentions: [], mentions_channel: false, attachments: [] });
            return;
        }
        let html = WS.mock.md(m.body);
        const mentions = [];
        const tokens = [];
        peopleOf(channel).slice().sort((a, b) => b.name.length - a.name.length).forEach((p) => {
            const re = new RegExp('@' + escRe(esc(p.name)) + '(?![\\w])', 'g');
            html = html.replace(re, () => {
                if (!mentions.includes(p.id)) mentions.push(p.id);
                tokens.push(`<span class="ws-mention" data-user-id="${p.id}">@${esc(p.name)}</span>`);
                return `\u0000${tokens.length - 1}\u0000`;
            });
        });
        const authorIsStaff = m.user && m.user.role !== 'intern';
        let mentionsChannel = false;
        if (authorIsStaff) {
            html = html.replace(/@channel(?![\w])/g, () => {
                mentionsChannel = true;
                return '<span class="ws-mention ws-mention--all">@channel</span>';
            });
        }
        html = html.replace(/<a href="#">#(T-\d+)<\/a>/g, (_, code) => {
            const task = channel.workspace_id !== null && context.tasks.find((t) => t.code === code);
            return task ? `<a href="${esc(task.url)}" class="ws-task-ref" data-task-id="${task.id}">#${code}</a>` : '#' + code;
        });
        html = html.replace(/\u0000(\d+)\u0000/g, (_, i) => tokens[Number(i)]);
        Object.assign(m, { body_html: html, mentions, mentions_channel: mentionsChannel });
    }

    function build(entry, n, user, body, createdAt, extra) {
        const m = Object.assign({
            id: entry.channel.id * 1000 + n,
            channel_id: entry.channel.id,
            parent_id: null,
            user,
            body,
            attachments: [],
            is_pinned: false,
            edited_at: null,
            is_deleted: false,
            reply_count: 0,
            created_at: createdAt,
            _touched: 0,
        }, extra || {});
        renderInto(entry.channel, m);
        return m;
    }

    // Reaksi disimpan sebagai { emoji: [userId] }; dikirim sebagai [{ emoji, count, me }].
    const REACTION_SET = ['👍', '❤️', '😂', '🎉', '🙏', '👀', '✅', '🔥'];

    function present(entry, m) {
        const out = clone(m);
        delete out._touched;
        delete out._reactions;
        out.reactions = Object.entries(m._reactions || {})
            .filter(([, ids]) => ids.length)
            .map(([emoji, ids]) => ({ emoji, count: ids.length, me: ids.includes(me.id) }));
        const own = !!m.user && m.user.id === me.id;
        const readOnly = entry.channel.is_archived;
        out.reply_count = m.reply_count || 0;
        out.last_reply_at = m.last_reply_at || null;
        out.can = {
            edit: own && !m.is_deleted && !readOnly,
            delete: !m.is_deleted && !readOnly && (own || isStaff),
            pin: isStaff && !readOnly && !m.parent_id && !m.is_deleted,
        };
        return out;
    }

    // Semua pesan channel: utama + balasan thread.
    function allOf(entry) {
        const out = entry.messages.slice();
        entry.replies.forEach((list) => out.push(...list));
        return out;
    }

    function addReply(entry, parent, msg) {
        const list = entry.replies.get(parent.id) || [];
        list.push(msg);
        entry.replies.set(parent.id, list);
        parent.reply_count = list.length;
        parent.last_reply_at = msg.created_at;
        parent._touched = Date.now(); // induk ikut di `updated` polling (reply_count baru)
    }

    function generate(entry) {
        const c = entry.channel;
        const r = rng(c.id * 7919 + 17);
        const base = c.id * 1000;
        const read = c.last_read_message_id ? c.last_read_message_id - base : COUNT[c.type];
        const total = read + (c.unread_count || 0);
        const authors = authorsOf(c);
        if (!authors.length) return [];
        const others = authors.filter((a) => a.id !== me.id);

        // Waktu mundur dari sekarang; sebagian pesan beruntun (1–4 menit) supaya pengelompokan teruji.
        // Workspace yang diarsipkan (magang selesai) berhenti sekitar seminggu lalu.
        const times = [];
        let t = Date.now() - (c.is_archived ? 8 * 1440 : c.unread_count ? 9 : 75) * 60000;
        for (let n = total; n >= 1; n--) {
            times[n] = t;
            t -= (r() < 0.35 ? 1 + Math.floor(r() * 4) : 20 + Math.floor(r() * 240)) * 60000;
        }

        const messages = [];
        const recent = [];
        let prev = null;
        const welcomeAt = c.type === 'team' && c.workspace_id !== null ? total - 22 : -1;
        for (let n = 1; n <= total; n++) {
            const createdAt = new Date(times[n]).toISOString();
            if (n === welcomeAt) {
                const newcomer = context.members.find((m) => m.role === 'intern');
                if (newcomer) {
                    messages.push(build(entry, n, null, `Selamat datang **${newcomer.name}** di ${context.workspace.name}! 👋 Silakan perkenalkan diri dan cek tugas onboarding di tab Board.`, createdAt));
                    prev = null;
                    continue;
                }
            }
            const pool = n > read && others.length ? others : authors;
            const author = prev && pool.some((a) => a.id === prev.id) && r() < 0.35 ? prev : pool[Math.floor(r() * pool.length)];
            const tpl = chooseBody(poolFor(c, author), author, c, r, recent);
            const extra = { attachments: attachmentsFor(tpl) };
            if (n === 4) extra.edited_at = new Date(times[n] + 3 * 60000).toISOString();
            const m = build(entry, n, author, tpl.text, createdAt, extra);
            if (n === 7 && total > 12) {
                m.is_deleted = true;
                renderInto(c, m);
            }
            messages.push(m);
            prev = author;
        }
        entry.seq = total;

        // Thread contoh pada pesan yang sudah dibaca. Id balasan di bawah rentang pesan utama
        // (base - 500 + k) supaya tidak terbaca sebagai pesan baru oleh polling ?after=.
        let replySeq = base - 500;
        messages.forEach((m, idx) => {
            if (m.is_deleted || !m.user || idx % 9 !== 3 || m.id > base + read) return;
            const pool = authors.filter((a) => a.id !== m.user.id);
            if (!pool.length) return;
            let t = Date.parse(m.created_at);
            const n = 1 + Math.floor(r() * 3);
            for (let k = 0; k < n; k++) {
                t = Math.min(t + (4 + Math.floor(r() * 50)) * 60000, Date.now() - 60000);
                const author = pool[Math.floor(r() * pool.length)];
                const reply = build(entry, 0, author, REPLIES[Math.floor(r() * REPLIES.length)], new Date(t).toISOString(), { id: ++replySeq, parent_id: m.id });
                addReply(entry, m, reply);
                reply._touched = 0;
            }
            m._touched = 0;
        });

        // Reaksi contoh dari anggota lain pada sebagian pesan.
        const reactors = authors.filter((a) => a.id !== me.id);
        messages.forEach((m, idx) => {
            if (m.is_deleted || !reactors.length || idx % 4 !== 1) return;
            const n = 1 + Math.floor(r() * 2);
            m._reactions = {};
            for (let k = 0; k < n; k++) {
                const emoji = REACTION_SET[Math.floor(r() * REACTION_SET.length)];
                const who = reactors.slice(0, 1 + Math.floor(r() * reactors.length)).map((a) => a.id);
                m._reactions[emoji] = [...new Set((m._reactions[emoji] || []).concat(who))];
            }
        });

        // Pesan disematkan: pesan mentor/admin terakhir yang sudah dibaca, sebanyak channel.pinned_count.
        messages.filter((m) => !m.is_deleted && m.user && m.user.role !== 'intern' && m.id <= base + read)
            .slice(-(c.pinned_count || 0) || messages.length)
            .slice(0, c.pinned_count || 0)
            .forEach((m) => { m.is_pinned = true; });
        return messages;
    }

    // ---------- Simulasi pesan masuk ----------

    let simCount = 0;
    let firstSimulated = null;

    function scheduleSimulation() {
        if (simCount >= 12) return;
        setTimeout(simulate, simCount === 0 ? 12000 : 20000 + Math.random() * 15000);
    }

    function simulate() {
        const candidates = [...channels.values()].filter((e) => e.channel.workspace_id !== null && !e.channel.is_archived
            && authorsOf(e.channel).some((a) => a.id !== me.id));
        if (!candidates.length) return;
        const entry = Math.random() < 0.7 ? candidates[0] : candidates[Math.floor(Math.random() * candidates.length)];
        const c = entry.channel;
        const others = authorsOf(c).filter((a) => a.id !== me.id);
        const author = others[Math.floor(Math.random() * others.length)];
        const canMentionMe = me.role !== 'admin' && peopleOf(c).some((p) => p.id === me.id);
        const pool = (INCOMING[author.role === 'intern' ? 'intern' : 'mentor'])
            .filter((tpl) => canMentionMe ? (simCount % 2 === 0) === tpl.includes('{me}') : !tpl.includes('{me}'));
        // Setiap simulasi ke-3: balasan di thread pesan terbaru (menguji polling balasan & reply_count).
        if (simCount % 3 === 2) {
            const parent = entry.messages.slice(-6).reverse().find((m) => !m.is_deleted && m.user);
            if (parent) {
                const reply = build(entry, ++entry.seq, author, REPLIES[Math.floor(Math.random() * REPLIES.length)], now(), { parent_id: parent.id });
                reply._touched = Date.now();
                addReply(entry, parent, reply);
                signal(entry);
                simCount++;
                scheduleSimulation();
                return;
            }
        }
        const tpl = chooseBody(pool.length ? pool : poolFor(c, author), author, c, Math.random);
        const msg = build(entry, ++entry.seq, author, tpl.text, now());
        msg._touched = Date.now();
        entry.messages.push(msg);

        if (msg.mentions.includes(me.id)) {
            const where = c.type === 'direct' ? 'pesan langsung' : '#' + c.name;
            WS.mock.notify({
                type: 'mentioned',
                title: `${author.name} menyebut Anda di ${where}`,
                body: msg.body.length > 90 ? msg.body.slice(0, 90) + '…' : msg.body,
                url: `${context.workspace.url}?tab=channel&channel=${c.id}&message=${msg.id}`,
                actor: { name: author.name, avatar_url: null },
            });
        }

        signal(entry);
        simCount++;
        if (simCount === 1) firstSimulated = { entry, msg };
        // Uji `updated` pada polling: pesan simulasi pertama diedit beberapa detik kemudian.
        if (simCount === 2 && firstSimulated) {
            setTimeout(() => {
                const { entry: e, msg: m } = firstSimulated;
                if (m.is_deleted) return;
                m.body += ' (sudah saya cek ulang)';
                m.edited_at = now();
                renderInto(e.channel, m);
                m._touched = Date.now();
            }, 8000);
        }
        scheduleSimulation();
    }

    // Realtime tiruan (?realtime=mock): beri tahu halaman bahwa channel ini berubah.
    function signal(entry) {
        if (!WS.realtime || WS.realtime.driver !== 'mock') return;
        const c = entry.channel;
        WS.realtime.emit(c.workspace_id === null ? 'announcements' : 'workspace.' + c.workspace_id, 'message.changed', { channel_id: c.id });
    }

    // ---------- Endpoint ----------

    function boot(value) {
        context = { workspace: value.workspace, members: value.members || [], tasks: value.tasks || [] };
        (value.channels || []).forEach((c) => {
            if (channels.has(c.id)) return;
            const entry = { channel: c, messages: [], replies: new Map(), seq: 0 };
            channels.set(c.id, entry);
            entry.messages = generate(entry);
        });
        scheduleSimulation();
    }

    WS.mock.seed = (function (original) {
        return function (key, value) {
            if (key === 'channel-context') return boot(clone(value));
            return original(key, value);
        };
    })(WS.mock.seed);

    function entryOf(channelId) {
        const entry = channels.get(Number(channelId));
        if (!entry) throw new WS.HttpError(404, 'Channel tidak ditemukan.');
        return entry;
    }

    function findMessage(id) {
        for (const entry of channels.values()) {
            const m = allOf(entry).find((x) => x.id === Number(id));
            if (m) return { entry, m };
        }
        throw new WS.HttpError(404, 'Pesan tidak ditemukan.');
    }

    define('messages.list', async (channelId, query) => {
        const entry = entryOf(channelId);
        const q = query || {};
        const polling = q.after !== undefined && q.after !== null;
        await delay(polling ? 120 : 380);
        const list = entry.messages;
        const serverTime = now();
        if (q.q) {
            // Pencarian: pesan utama + balasan, terbaru dulu, maks. 20.
            const term = String(q.q).toLowerCase();
            const hits = allOf(entry).filter((m) => !m.is_deleted && m.body.toLowerCase().includes(term))
                .sort((a, b) => b.created_at.localeCompare(a.created_at));
            return { data: hits.slice(0, 20).map((m) => present(entry, m)), meta: { total: hits.length } };
        }
        if (q.pinned) {
            return { data: list.filter((m) => m.is_pinned && !m.is_deleted).reverse().map((m) => present(entry, m)), meta: {} };
        }
        if (polling) {
            const after = Number(q.after);
            const since = q.since ? Date.parse(q.since) : 0;
            const all = allOf(entry);
            return {
                data: all.filter((m) => m.id > after).sort((a, b) => a.id - b.id).map((m) => present(entry, m)),
                updated: all.filter((m) => m.id <= after && m._touched > since).map((m) => present(entry, m)),
                meta: { server_time: serverTime },
            };
        }
        const pool = q.before ? list.filter((m) => m.id < Number(q.before)) : list;
        const page = pool.slice(-50);
        return { data: page.map((m) => present(entry, m)), meta: { has_more_before: pool.length > page.length, server_time: serverTime } };
    });

    define('messages.send', async (channelId, p) => {
        const entry = entryOf(channelId);
        const c = entry.channel;
        await delay(350);
        if (!c.can.post) {
            throw forbidden(c.type === 'announcement' ? 'Hanya mentor dan admin yang dapat memposting di #pengumuman.'
                : c.is_archived ? 'Kelompok sudah selesai magang. Pesan hanya bisa dibaca.'
                    : 'Anda tidak dapat mengirim pesan di channel ini.');
        }
        const body = (p.body || '').trim();
        const ids = p.attachment_ids || [];
        if (!body && !ids.length) throw invalid({ body: ['Tulis pesan atau lampirkan file.'] });
        if (body.length > 4000) throw invalid({ body: ['Pesan maksimal 4.000 karakter.'] });
        const t = Date.now();
        while (sentAt.length && t - sentAt[0] > 60000) sentAt.shift();
        if (sentAt.length >= 30) throw new WS.HttpError(429, 'Terlalu banyak pesan. Tunggu sebentar sebelum mengirim lagi.');
        // Pemicu uji tampilan "gagal terkirim": pesan berisi "/gagal" gagal sekali, berhasil saat dicoba lagi.
        if (/\/gagal\b/.test(body) && !failedOnce.has(body)) {
            failedOnce.add(body);
            throw new WS.HttpError(500, 'Terjadi kesalahan di server.');
        }
        const parentId = p.parent_id ? Number(p.parent_id) : null;
        const parent = parentId ? entry.messages.find((m) => m.id === parentId) : null;
        if (parentId && (!parent || parent.is_deleted)) throw invalid({ parent_id: ['Pesan induk tidak ditemukan atau sudah dihapus.'] });
        sentAt.push(t);
        const attachments = ids.map((id) => WS.mock.attachment && WS.mock.attachment(id)).filter(Boolean);
        const msg = build(entry, ++entry.seq, meMini, body, now(), { attachments, parent_id: parentId });
        msg._touched = Date.now();
        if (parent) addReply(entry, parent, msg);
        else entry.messages.push(msg);
        return { data: present(entry, msg) };
    });

    define('messages.update', async (id, p) => {
        const { entry, m } = findMessage(id);
        await delay(250);
        if (!m.user || m.user.id !== me.id) throw forbidden('Anda hanya dapat mengedit pesan sendiri.');
        if (entry.channel.is_archived) throw forbidden('Kelompok sudah selesai magang. Pesan hanya bisa dibaca.');
        if (m.is_deleted) throw invalid({ body: ['Pesan sudah dihapus.'] });
        const body = (p.body || '').trim();
        if (!body && !m.attachments.length) throw invalid({ body: ['Pesan tidak boleh kosong.'] });
        if (body.length > 4000) throw invalid({ body: ['Pesan maksimal 4.000 karakter.'] });
        m.body = body;
        m.edited_at = now();
        renderInto(entry.channel, m);
        m._touched = Date.now();
        return { data: present(entry, m) };
    });

    define('messages.remove', async (id) => {
        const { entry, m } = findMessage(id);
        await delay(250);
        const own = !!m.user && m.user.id === me.id;
        if (!own && !isStaff) throw forbidden('Anda hanya dapat menghapus pesan sendiri.');
        if (entry.channel.is_archived) throw forbidden('Kelompok sudah selesai magang. Pesan hanya bisa dibaca.');
        m.is_deleted = true;
        m.is_pinned = false;
        renderInto(entry.channel, m);
        m._touched = Date.now();
        return { data: present(entry, m) };
    });

    define('messages.replies', async (id) => {
        const { entry, m } = findMessage(id);
        await delay(300);
        if (m.parent_id) throw new WS.HttpError(404, 'Pesan ini adalah balasan, bukan induk thread.');
        return { data: (entry.replies.get(m.id) || []).map((x) => present(entry, x)), parent: present(entry, m) };
    });

    define('messages.react', async (id, p) => {
        const { entry, m } = findMessage(id);
        await delay(150);
        const c = entry.channel;
        if (c.is_archived) throw forbidden('Channel diarsipkan. Pesan hanya bisa dibaca.');
        if (!c.can.post && c.type !== 'announcement') throw forbidden('Anda tidak dapat bereaksi di channel ini.');
        if (m.is_deleted) throw invalid({ emoji: ['Pesan sudah dihapus.'] });
        if (!REACTION_SET.includes(p.emoji)) throw invalid({ emoji: ['Reaksi tidak tersedia.'] });
        m._reactions = m._reactions || {};
        const ids = m._reactions[p.emoji] || [];
        m._reactions[p.emoji] = ids.includes(me.id) ? ids.filter((x) => x !== me.id) : ids.concat(me.id);
        m._touched = Date.now(); // anggota lain melihatnya lewat `updated` polling
        return { data: present(entry, m).reactions };
    });

    define('messages.pin', async (id, p) => {
        const { entry, m } = findMessage(id);
        await delay(220);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat menyematkan pesan.');
        if (entry.channel.is_archived) throw forbidden('Channel diarsipkan. Pesan hanya bisa dibaca.');
        if (m.parent_id) throw invalid({ is_pinned: ['Balasan thread tidak bisa disematkan.'] });
        if (m.is_deleted) throw invalid({ is_pinned: ['Pesan sudah dihapus.'] });
        m.is_pinned = !!p.is_pinned;
        m._touched = Date.now();
        return { data: present(entry, m) };
    });

    function validateChannel(p, existing) {
        const errors = {};
        if ('name' in p) {
            const name = String(p.name || '');
            const wsId = existing ? existing.workspace_id : (context.workspace && context.workspace.id);
            const taken = [...channels.values()].some((e) => e.channel.name === name && e.channel !== existing
                && (e.channel.workspace_id === wsId || e.channel.workspace_id === null));
            if (name.length < 2 || name.length > 40) errors.name = ['Nama 2–40 karakter.'];
            else if (!/^[a-z0-9][a-z0-9-]*$/.test(name)) errors.name = ['Gunakan huruf kecil, angka, dan tanda hubung.'];
            else if (taken) errors.name = ['Nama channel sudah dipakai.'];
        }
        if (p.description && p.description.length > 120) errors.description = ['Deskripsi maksimal 120 karakter.'];
        if (Object.keys(errors).length) throw invalid(errors);
    }

    define('channels.create', async (workspaceId, p) => {
        await delay(450);
        if (!isStaff) throw forbidden('Hanya mentor atau admin yang dapat membuat channel.');
        validateChannel(p, null);
        const memberIds = p.is_private ? (p.member_ids || []) : [];
        if (p.is_private && memberIds.length < 2) throw invalid({ member_ids: ['Pilih minimal satu anggota lain.'] });
        const channel = {
            id: Number(workspaceId) * 10 + 5 + (++channelSeq), workspace_id: Number(workspaceId), name: p.name, type: 'topic',
            description: p.description || null, is_private: !!p.is_private, is_archived: false,
            members_count: p.is_private ? memberIds.length : context.members.length, members: null,
            unread_count: 0, last_read_message_id: 0, pinned_count: 0,
            can: { post: true, manage: true, pin: true },
        };
        const entry = { channel, messages: [], replies: new Map(), seq: 0 };
        channels.set(channel.id, entry);
        const intro = build(entry, ++entry.seq, null, `**${me.name}** membuat channel ini.`, now());
        intro._touched = Date.now();
        entry.messages.push(intro);
        channel.last_read_message_id = intro.id;
        return { data: clone(channel) };
    });

    define('channels.update', async (id, p) => {
        const entry = entryOf(id);
        const c = entry.channel;
        await delay(300);
        if (!c.can.manage) throw forbidden('Anda tidak dapat mengelola channel ini.');
        if ('is_archived' in p && c.type !== 'topic') throw forbidden('Channel ini dibuat sistem dan tidak bisa diarsipkan.');
        validateChannel(p, c);
        if ('name' in p) c.name = p.name;
        if ('description' in p) c.description = p.description || null;
        if ('is_archived' in p) {
            c.is_archived = !!p.is_archived;
            c.can = Object.assign({}, c.can, { post: !c.is_archived, pin: !c.is_archived });
        }
        return { data: clone(c) };
    });

    define('channels.mute', async (id, p) => {
        await delay(180);
        const entry = channels.get(Number(id));
        // Halaman pengaturan notifikasi tidak memuat data channel: cukup kembalikan statusnya.
        if (!entry) return { data: { id: Number(id), is_muted: !!p.is_muted } };
        entry.channel.is_muted = !!p.is_muted;
        return { data: clone(entry.channel) };
    });

    define('channels.markRead', async (channelId, p) => {
        const entry = entryOf(channelId);
        await delay(80);
        entry.channel.last_read_message_id = Math.max(entry.channel.last_read_message_id || 0, Number(p.message_id) || 0);
        return null;
    });

    // Usulan kontrak #3 — dibaca oleh handler notifications.summary (mock.js).
    // `total` hanya channel milik workspace (tanpa #pengumuman global), sama seperti Workspace.unread_messages.
    WS.mock.unreadMessages = () => {
        const byChannel = {};
        let total = 0;
        channels.forEach((e) => {
            const lastRead = e.channel.last_read_message_id || 0;
            const n = e.messages.filter((m) => m.id > lastRead && !m.is_deleted && (!m.user || m.user.id !== me.id)).length;
            if (!n) return;
            byChannel[e.channel.id] = n;
            if (e.channel.workspace_id !== null) total += n;
        });
        return { total, by_channel: byChannel };
    };
})();
