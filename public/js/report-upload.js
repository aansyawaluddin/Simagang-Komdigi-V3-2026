/*
 * Laporan akhir intern — upload berkas laporan (PDF, maks. 5 MB) + progres Upload → Review mentor → Disetujui.
 * Dipakai components/report/file-upload.blade.php. Tidak bergantung pada modul Workspace.
 *
 * mode "standalone": laporan sudah ada; komponen punya <form> sendiri ke intern.report.update.
 * mode "embedded"  : di dalam form upload pertama; komponen hanya mengisi <input name="file">.
 * simulate = true  : (hanya APP_ENV=local) progres & hasil upload disimulasikan, tidak ada data yang dikirim.
 */
(function () {
    'use strict';

    const MAX_BYTES = 5 * 1024 * 1024;

    function formatSize(bytes) {
        if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
    }

    const dateFmt = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    document.addEventListener('alpine:init', () => {
        // config: { mode, simulate, state: 'empty'|'review'|'revision'|'approved', fileName, fileUrl, uploadedAt,
        //          serverError, flash }
        window.Alpine.data('reportUpload', (config) => ({
            mode: config.mode,
            simulate: !!config.simulate,
            state: config.state,
            fileName: config.fileName,
            fileUrl: config.fileUrl,
            uploadedAt: config.uploadedAt,
            replacing: false,      // pengguna menekan "Ganti berkas"
            dragging: false,
            picked: null,          // { name, size }
            error: config.serverError || '',
            progress: 0,
            uploading: false,
            justUploaded: false,
            toast: '',

            init() {
                if (config.flash) this.done(config.flash);
            },

            // ---------- Tampilan ----------

            get hasFile() {
                return this.state !== 'empty';
            },

            // Area upload tampil bila belum ada berkas, sedang mengganti, atau diminta revisi.
            get showDrop() {
                if (this.uploading) return false;
                if (this.mode === 'embedded') return true;
                return this.state === 'empty' || this.replacing || (this.state === 'revision' && !this.justUploaded);
            },

            get canReplace() {
                return this.mode === 'standalone' && this.hasFile && this.state !== 'approved' && !this.uploading;
            },

            // Langkah aktif progres: 1 upload, 2 review, 3 disetujui.
            get step() {
                if (this.state === 'approved') return 3;
                if (this.state === 'review' || this.state === 'revision') return 2;
                return 1;
            },

            stepClass(n) {
                if (n === 2 && this.state === 'revision') return 'rpt-step--warn';
                if (n < this.step || (n === 3 && this.state === 'approved')) return 'rpt-step--done';
                if (n === this.step) return 'rpt-step--current';
                return '';
            },

            hint(n) {
                if (n === 1) return this.hasFile ? 'Berkas terkirim' : 'Belum ada berkas';
                if (n === 2) {
                    return { review: 'Sedang direview', revision: 'Perlu revisi', approved: 'Selesai direview' }[this.state] || 'Menunggu upload';
                }
                return this.state === 'approved' ? 'Laporan disetujui' : 'Menunggu review';
            },

            get barWidth() {
                return { 1: this.hasFile ? 50 : 0, 2: 50, 3: 100 }[this.step];
            },

            get uploadedLabel() {
                return this.uploadedAt ? dateFmt.format(new Date(this.uploadedAt)) : '';
            },

            size: formatSize,

            // ---------- Pilih berkas ----------

            pick() {
                this.$refs.input.click();
            },

            onInput(event) {
                this.take(event.target.files[0]);
            },

            onDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (!file) return;
                // Masukkan ke <input> supaya ikut terkirim bersama form.
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.input.files = dt.files;
                this.take(file);
            },

            take(file) {
                this.error = '';
                this.picked = null;
                if (!file) return;
                const isPdf = /\.pdf$/i.test(file.name) && (!file.type || file.type === 'application/pdf');
                if (!isPdf) this.error = 'Berkas harus berformat PDF (.pdf).';
                else if (file.size > MAX_BYTES) this.error = `Ukuran berkas ${formatSize(file.size)} melebihi batas 5 MB.`;
                if (this.error) {
                    this.$refs.input.value = ''; // berkas tidak valid tidak ikut terkirim
                    return;
                }
                this.picked = { name: file.name, size: file.size };
            },

            clear() {
                this.picked = null;
                this.error = '';
                this.$refs.input.value = '';
            },

            cancelReplace() {
                this.clear();
                this.replacing = false;
            },

            // ---------- Upload ----------

            upload() {
                if (!this.picked) { this.error = 'Pilih berkas PDF terlebih dahulu.'; return; }
                this.uploading = true;
                this.progress = 0;
                if (!this.simulate) {
                    // Kirim sungguhan: halaman dimuat ulang dan pesan sukses datang dari server.
                    this.$refs.form.submit();
                    return;
                }
                const timer = setInterval(() => {
                    this.progress = Math.min(100, this.progress + 8 + Math.round(Math.random() * 10));
                    if (this.progress < 100) return;
                    clearInterval(timer);
                    setTimeout(() => {
                        this.fileName = this.picked.name;
                        this.fileUrl = null;
                        this.uploadedAt = new Date().toISOString();
                        this.state = 'review';
                        this.done('Laporan berhasil diupload. Mentor akan segera mereview.');
                    }, 250);
                }, 140);
            },

            done(message) {
                this.uploading = false;
                this.replacing = false;
                this.justUploaded = true;
                this.picked = null;
                if (this.$refs.input) this.$refs.input.value = '';
                this.showToast(message);
            },

            showToast(message) {
                this.toast = message;
                clearTimeout(this._toastTimer);
                this._toastTimer = setTimeout(() => { this.toast = ''; }, 5000);
            },
        }));
    });
})();
