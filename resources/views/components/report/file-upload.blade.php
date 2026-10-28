{{--
    Upload berkas laporan akhir (PDF, maks. 5 MB) dengan progres Upload → Review mentor → Disetujui di atasnya.
      :report  FinalReport|null
      mode     'standalone' (laporan sudah ada; punya <form> sendiri ke intern.report.update)
               'embedded'   (di dalam form upload pertama; hanya menyediakan <input name="file">)
    Lokal (APP_ENV=local): upload disimulasikan, tidak ada data yang dikirim (?simulasi=0 untuk kirim sungguhan).
    Pratinjau keadaan di lokal: ?status=kosong|menunggu|revisi|disetujui.
--}}
@props(['report' => null, 'mode' => 'standalone'])

@php
    $local = app()->environment('local');
    $state = match (true) {
        ! $report || ! $report->file_path => 'empty',
        $report->status === 'approved' && ! $report->needs_revision => 'approved',
        $report->needs_revision || $report->status === 'rejected' => 'revision',
        default => 'review',
    };
    $preview = $local ? ['kosong' => 'empty', 'menunggu' => 'review', 'revisi' => 'revision', 'disetujui' => 'approved'][request('status')] ?? null : null;
    if ($preview && $mode === 'standalone') $state = $preview;
    $simulate = $mode === 'standalone' && $local && request('simulasi') !== '0';
    $flash = session('success') && str_starts_with((string) session('success'), 'Laporan akhir') ? session('success') : null;
    $inputId = 'report-file-' . $mode;

    // Update di controller menulis ulang link & kegiatan dari request: kirim ulang nilai lama supaya tidak terhapus.
    $keepLinks = $report ? (array_filter((array) ($report->project_links ?: ($report->project_link ? [$report->project_link] : [])))) : [];
    $keepActivities = $report && is_array($report->activities) ? $report->activities : [];
@endphp

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/report-upload.css') }}?v={{ @filemtime(public_path('css/report-upload.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/report-upload.js') }}?v={{ @filemtime(public_path('js/report-upload.js')) }}"></script>
    @endpush
@endonce

<div {{ $attributes->class(['rpt']) }}
     x-data="reportUpload(@js([
         'mode' => $mode,
         'simulate' => $simulate,
         'state' => $state,
         'fileName' => $report?->file_name,
         'fileUrl' => $report && $report->file_path ? route('download', ['path' => $report->file_path]) : null,
         'uploadedAt' => $report?->submitted_at?->toIso8601String(),
         'serverError' => $errors->first('file') ?: null,
         'flash' => $flash,
     ]))">

    {{-- ===== Progres: Upload → Review mentor → Disetujui ===== --}}
    <div class="rpt-steps-wrap">
    <div class="rpt-steps__track" aria-hidden="true"><span :class="state === 'revision' && 'rpt-steps__fill--warn'" :style="'width:' + barWidth + '%'"></span></div>
    <ol class="rpt-steps" aria-label="Progres laporan akhir">
        @foreach ([1 => ['Upload laporan', 'fa-upload'], 2 => ['Review mentor', 'fa-user-check'], 3 => ['Disetujui', 'fa-award']] as $n => [$label, $icon])
            <li class="rpt-step" :class="stepClass({{ $n }})" :aria-current="step === {{ $n }} ? 'step' : null">
                <span class="rpt-step__dot">
                    <i class="fas" :class="stepClass({{ $n }}) === 'rpt-step--done' ? 'fa-check' : (stepClass({{ $n }}) === 'rpt-step--warn' ? 'fa-rotate-left' : '{{ $icon }}')"></i>
                </span>
                <span class="rpt-step__label">{{ $label }}</span>
                <span class="rpt-step__hint" x-text="hint({{ $n }})"></span>
            </li>
        @endforeach
    </ol>
    </div>

    {{-- ===== Status berkas (laporan sudah diupload) ===== --}}
    @if ($mode === 'standalone')
        <div class="rpt-notice" :class="'rpt-notice--' + state + (justUploaded ? ' rpt-notice--fresh' : '')"
             x-show="hasFile && !uploading" role="status">
            <span class="rpt-notice__icon" aria-hidden="true">
                <i class="fas" :class="{ review: 'fa-hourglass-half', revision: 'fa-triangle-exclamation', approved: 'fa-circle-check' }[state]"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="rpt-notice__title" x-text="state === 'approved' ? 'Laporan disetujui mentor' : state === 'revision' ? 'Mentor meminta revisi laporan' : (justUploaded ? 'Laporan berhasil diupload' : 'Laporan sudah diupload')"></p>
                <p class="rpt-notice__desc" x-text="state === 'approved'
                    ? 'Berkas laporan sudah final dan tidak dapat diganti lagi.'
                    : state === 'revision'
                        ? 'Perbaiki laporan sesuai catatan, lalu upload ulang berkas PDF di bawah.'
                        : 'Menunggu review mentor. Salah upload? Anda masih bisa mengganti berkas sebelum disetujui.'"></p>
                <div class="rpt-file mt-3">
                    <span class="rpt-file__icon" aria-hidden="true"><i class="fas fa-file-pdf"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 truncate" x-text="fileName"></span>
                        <span class="block text-xs text-gray-600" x-text="uploadedLabel ? 'Diupload ' + uploadedLabel : ''"></span>
                    </span>
                    <span class="rpt-file__actions">
                        <a class="rpt-btn rpt-btn--ghost" x-show="fileUrl" :href="fileUrl" target="_blank" rel="noopener">
                            <i class="fas fa-eye"></i> Lihat berkas
                        </a>
                        <button type="button" class="rpt-btn rpt-btn--soft" x-show="canReplace && !replacing && state !== 'revision'"
                                x-on:click="replacing = true; $nextTick(() => $refs.input.focus())">
                            <i class="fas fa-arrows-rotate"></i> Ganti berkas
                        </button>
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Area upload ===== --}}
    @if ($mode === 'standalone')
        <form method="POST" action="{{ $report ? route('intern.report.update', $report) : route('intern.report.store') }}" enctype="multipart/form-data" x-ref="form">
            @csrf
            @if ($report)
                @method('PUT')
                @foreach ($keepLinks as $link)
                    <input type="hidden" name="project_links[]" value="{{ $link }}">
                @endforeach
                @foreach ($keepActivities as $i => $activity)
                    <input type="hidden" name="activities[{{ $i }}][description]" value="{{ data_get($activity, 'description') }}">
                @endforeach
            @endif
    @endif

    <div x-show="showDrop || uploading" class="rpt-upload">
        <input type="file" name="file" id="{{ $inputId }}" accept="application/pdf,.pdf" class="sr-only" x-ref="input"
               x-on:change="onInput($event)" aria-label="Berkas laporan akhir (PDF, maks. 5 MB)" aria-describedby="{{ $inputId }}-hint" @if ($mode === 'embedded') required @endif>

        {{-- Placeholder upload --}}
        <div class="rpt-drop" :class="{ 'rpt-drop--drag': dragging, 'rpt-drop--error': error }" x-show="!picked && !uploading"
             x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="onDrop($event)">
            <span class="rpt-drop__icon" aria-hidden="true"><i class="fas fa-cloud-arrow-up"></i></span>
            <p class="font-semibold text-gray-900" x-text="state === 'revision' ? 'Upload laporan hasil revisi' : (replacing ? 'Upload berkas pengganti' : 'Seret & lepas laporan akhir di sini')"></p>
            <p class="text-sm text-gray-500 my-1">atau</p>
            <button type="button" class="rpt-btn rpt-btn--primary" x-on:click="pick()">
                <i class="fas fa-folder-open"></i> Pilih berkas PDF
            </button>
            <p id="{{ $inputId }}-hint" class="rpt-drop__hint">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <span>Format <strong>PDF</strong> · ukuran maksimal <strong>5 MB</strong> · 1 berkas.
                Gabungkan seluruh bab (sampul hingga lampiran) dalam satu berkas PDF.</span>
            </p>
        </div>

        {{-- Berkas terpilih --}}
        <div class="rpt-file rpt-file--picked" x-show="picked && !uploading">
            <span class="rpt-file__icon" aria-hidden="true"><i class="fas fa-file-pdf"></i></span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-gray-900 truncate" x-text="picked ? picked.name : ''"></span>
                <span class="block text-xs text-emerald-700" x-text="picked ? size(picked.size) + ' · siap diupload' : ''"></span>
            </span>
            <span class="rpt-file__actions">
                <button type="button" class="rpt-btn rpt-btn--ghost" x-on:click="pick()"><i class="fas fa-arrows-rotate"></i> Pilih lain</button>
                <button type="button" class="rpt-btn rpt-btn--ghost" x-on:click="clear()" aria-label="Hapus berkas terpilih"><i class="fas fa-xmark"></i></button>
                @if ($mode === 'standalone')
                    <button type="button" class="rpt-btn rpt-btn--primary" x-on:click="upload()"><i class="fas fa-upload"></i> Upload</button>
                @endif
            </span>
        </div>

        {{-- Progres upload --}}
        <div class="rpt-progress" x-show="uploading" role="status" aria-live="polite">
            <div class="flex items-center justify-between gap-3 text-sm">
                <span class="font-semibold text-gray-900 truncate"><i class="fas fa-file-pdf text-red-600 mr-1.5"></i><span x-text="picked ? picked.name : ''"></span></span>
                <span class="text-gray-600 shrink-0" x-text="simulate ? progress + '%' : 'Mengunggah…'"></span>
            </div>
            <div class="rpt-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="simulate ? progress : null" aria-label="Progres upload">
                <span :class="!simulate && 'rpt-progress__bar--indeterminate'" :style="simulate ? 'width:' + progress + '%' : ''"></span>
            </div>
            <p class="text-xs text-gray-500 mt-2">Jangan tutup halaman sampai upload selesai.</p>
        </div>

        <p class="rpt-error" x-show="error" x-text="error" role="alert"></p>

        <button type="button" class="text-sm font-semibold text-gray-600 hover:text-gray-900 mt-3 py-1" x-show="replacing && !uploading" x-on:click="cancelReplace()">
            Batal ganti berkas
        </button>
    </div>

    @if ($mode === 'standalone')
        </form>
    @endif

    @if ($simulate)
        <p class="rpt-sim">
            <i class="fas fa-flask" aria-hidden="true"></i>
            <span>Mode simulasi (lokal): berkas tidak benar-benar dikirim. Coba keadaan lain dengan <code>?status=kosong</code>, <code>menunggu</code>, <code>revisi</code>, atau <code>disetujui</code>.</span>
        </p>
    @endif

    {{-- Notifikasi berhasil --}}
    <div class="rpt-toast" x-show="toast" x-cloak x-transition.opacity role="status" aria-live="polite">
        <i class="fas fa-circle-check text-emerald-600" aria-hidden="true"></i>
        <span class="flex-1" x-text="toast"></span>
        <button type="button" class="text-gray-500 hover:text-gray-800" x-on:click="toast = ''" aria-label="Tutup notifikasi"><i class="fas fa-xmark"></i></button>
    </div>
</div>
