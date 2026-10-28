@extends('layouts.app')

@section('title', 'Dashboard - Sistem Magang')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

    * { font-family: 'Plus Jakarta Sans', sans-serif; }
    .mono { font-family: 'DM Mono', monospace; }
    body { background: #f0f4ff; }

    .dash-bg {
        background: linear-gradient(135deg, #e8eeff 0%, #f0f4ff 40%, #e4ecff 100%);
        min-height: 100vh;
    }

    .profile-strip {
        background: linear-gradient(100deg, #1e3a8a 0%, #3b4fd8 50%, #4f46e5 100%);
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(20, 40, 120, 0.16);
    }

    .profile-strip::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 220px;
        height: 220px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }

    .profile-strip::after {
        content: '';
        position: absolute;
        bottom: -80px;
        left: 30%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }

    .avatar-ring {
        background: linear-gradient(135deg, #60a5fa, #818cf8);
        padding: 3px;
        border-radius: 9999px;
        display: inline-flex;
        flex-shrink: 0;
    }

    .avatar-inner {
        border-radius: 9999px;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
    }

    .panel {
        background: #fff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(30,58,138,0.06), 0 4px 20px rgba(30,58,138,0.06);
    }

    .stat-tile {
        background: #fff;
        border-radius: 1.25rem;
        padding: 1.4rem;
        box-shadow: 0 1px 3px rgba(30,58,138,0.06), 0 4px 20px rgba(30,58,138,0.06);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-direction: row-reverse;
    }

    .stat-tile:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(59,79,216,0.16);
    }

    .stat-tile::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--stat-color, #3b82f6);
    }

    .stat-tile .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.4rem 0 0;
        font-family: 'DM Mono', monospace;
    }

    .stat-tile .stat-label {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.25rem 0 0;
        font-family: 'DM Mono', monospace;
    }

    .section-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 14px;
    }

    .status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-radius: 14px;
        background: #f8faff;
        border: 1px solid #e8eeff;
        transition: all 0.2s ease;
        cursor: default;
    }

    .status-item:hover {
        background: #eff2ff;
        border-color: #c7d2fe;
        transform: translateX(4px);
    }

    .stat-bar-track {
        height: 6px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .stat-bar-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 1.2s cubic-bezier(.4,0,.2,1);
        width: 0;
    }

    .count-pill {
        font-family: 'DM Mono', monospace;
        font-size: 13px;
        font-weight: 500;
        padding: 3px 12px;
        border-radius: 999px;
        min-width: 42px;
        text-align: center;
    }

    .donut-svg circle {
        transition: stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1);
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 13px 18px;
        border-radius: 14px;
        background: #f0f4ff;
        border: 1.5px solid #e0e7ff;
        color: #3b4fd8;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s ease;
        text-decoration: none;
        width: 100%;
    }

    .action-btn:hover {
        background: #e8eeff;
        border-color: #a5b4fc;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99,102,241,0.15);
        color: #3b4fd8;
        text-decoration: none;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        backdrop-filter: blur(3px);
    }

    .modal-box {
        background: #fff;
        border-radius: 20px;
        padding: 28px;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 20px 60px rgba(30,58,138,0.18);
        position: relative;
    }

    .lb-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-radius: 14px;
        background: #f0f4ff;
        border: 1px solid #e0e7ff;
        transition: all 0.2s ease;
    }

    .lb-item:hover {
        background: #e8eeff;
        border-color: #c7d2fe;
        transform: translateX(4px);
    }

    .lb-item.is-me {
        background: #ede9fe;
        border-color: #c4b5fd;
    }

    .divider {
        height: 1px;
        background: #f1f5f9;
        margin: 4px 0;
    }

    .cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 24px;
        background: linear-gradient(110deg, #1e3a8a, #3b4fd8);
        color: #fff;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .cta-btn:hover {
        box-shadow: 0 6px 16px rgba(59,79,216,0.3);
        transform: translateY(-1px);
        color: #fff;
        text-decoration: none;
    }

    .info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-radius: 10px;
        background: #f8faff;
        border: 1px solid #e8eeff;
        font-size: 14px;
    }

    @keyframes fadeSlideUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .anim-1 {
        animation: fadeSlideUp 0.5s ease both;
    }

    .anim-2 {
        animation: fadeSlideUp 0.5s ease 0.1s both;
    }

    .anim-3 {
        animation: fadeSlideUp 0.5s ease 0.2s both;
    }

    .anim-4 {
        animation: fadeSlideUp 0.5s ease 0.3s both;
    }

    @media (max-width: 640px) {
        .avatar-inner {
            width: 60px;
            height: 60px;
        }

        .panel {
            padding: 16px;
        }

        .stat-tile {
            padding: 1rem;
        }

        .stat-tile .stat-value {
            font-size: 1.4rem;
        }

        .grid.grid-cols-3 {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-tile {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-direction: row;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            font-size: 1rem;
            flex: 0 0 44px;
        }

        .stat-value {
            font-size: 1.25rem;
        }

        .stat-label {
            font-size: 9px;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalAbsen = $totalHadir + $totalIzin + $totalSakit + $totalTidakHadir ?: 1;
    $pctHadir  = round(($totalHadir / $totalAbsen) * 100);
    $pctIzin   = round(($totalIzin / $totalAbsen) * 100);
    $pctSakit  = round(($totalSakit / $totalAbsen) * 100);
    $pctAlfa   = round(($totalTidakHadir / $totalAbsen) * 100);

    $circ = 263.9;
    $hadirDash = $pctHadir / 100 * $circ;
    $izinDash  = $pctIzin / 100 * $circ;
    $sakitDash = $pctSakit / 100 * $circ;
    $alfaDash  = $pctAlfa / 100 * $circ;

    $izinStart  = $hadirDash;
    $sakitStart = $hadirDash + $izinDash;
    $alfaStart  = $hadirDash + $izinDash + $sakitDash;

    $microPct = $microSkillTotal > 0 ? round(($microSkillApproved / $microSkillTotal) * 100) : 0;
@endphp

<div class="dash-bg py-8">

@if(!empty($mustFillSharingMaterial) && $mustFillSharingMaterial)
<div id="sharingAlertOverlay"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center">

    <div class="bg-white rounded-3xl p-8 max-w-md w-full mx-4 text-center shadow-2xl">

        <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-yellow-100 flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-3">
            Materi Sharing Session Belum Lengkap
        </h2>

        <p class="text-gray-600 mb-6">
            Anda ditunjuk sebagai narasumber pada sharing session.
            Silakan lengkapi judul dan deskripsi materi sharing session terlebih dahulu.
        </p>

        <button id="btnLengkapiMateri"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl">
            Lengkapi Sekarang
        </button>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.overflow = 'hidden';

    document.getElementById('btnLengkapiMateri').addEventListener('click', function() {
        window.location.href =
            "{{ route('intern.sharing-session.edit-materi', $sharingSessionAlert->id) }}";
    });
});
</script>
@endif

@if(
    !empty($mustUploadSharingDocumentation) &&
    $mustUploadSharingDocumentation &&
    !empty($sharingDocumentationAlert)
)
<div id="sharingDocumentationAlertOverlay"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center">

    <div class="bg-white rounded-3xl p-8 max-w-md w-full mx-4 text-center shadow-2xl">

        <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-amber-100 flex items-center justify-center">
            <i class="fas fa-camera text-amber-500 text-4xl"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-3">
            Dokumentasi Belum Diupload
        </h2>

        <p class="text-gray-600 mb-4">
            Anda ditunjuk sebagai moderator pada sharing session berikut, tetapi gambar dokumentasi belum diunggah.
        </p>

        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-left mb-6">
            <p class="text-sm font-bold text-amber-800">
                {{ $sharingDocumentationAlert->title ?? 'Sharing Session' }}
            </p>

            <p class="text-xs text-amber-700 mt-2">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ $sharingDocumentationAlert->session_date ? $sharingDocumentationAlert->session_date->format('d M Y') : '-' }}

                <span class="mx-1">•</span>

                <i class="fas fa-clock mr-1"></i>
                {{ $sharingDocumentationAlert->start_time ? \Carbon\Carbon::parse($sharingDocumentationAlert->start_time)->format('H:i') : '-' }} WITA
            </p>

            <p class="text-xs text-amber-700 mt-1">
                <i class="fas fa-map-marker-alt mr-1"></i>
                {{ $sharingDocumentationAlert->location ?? '-' }}
            </p>
        </div>

        <button id="btnUploadDokumentasi"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl">
            Upload Dokumentasi Sekarang
        </button>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.overflow = 'hidden';

    const btnUploadDokumentasi = document.getElementById('btnUploadDokumentasi');

    if (btnUploadDokumentasi) {
        btnUploadDokumentasi.addEventListener('click', function() {
            window.location.href =
                "{{ route('intern.sharing-session.edit-materi', $sharingDocumentationAlert->id) }}";
        });
    }
});
</script>
@endif

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- PROFILE HEADER --}}
    <div class="profile-strip anim-1">
        <div class="px-6 py-7 flex flex-col sm:flex-row items-center sm:items-start gap-5 relative z-10">

            {{-- Avatar --}}
            <div class="avatar-ring flex-shrink-0">
                <div class="avatar-inner">
                    @if($intern->photo_path)
                        <img src="{{ url('storage/'.$intern->photo_path) }}"
                             alt="{{ $intern->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <i class="fas fa-user text-2xl text-white"></i>
                    @endif
                </div>
            </div>

            {{-- Identity --}}
            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-xl font-bold text-white mb-1">
                    {{ $intern->name }}
                </h1>

                <p class="text-blue-200 font-semibold text-base">
                    {{ $intern->institution }}
                </p>

                <p class="text-blue-300 text-sm mt-1">
                    <i class="fas fa-graduation-cap mr-1"></i>
                    {{ $intern->education_level }} &mdash; {{ $intern->major }}
                </p>

                @if($certificate && ($certificate->issue_date->isToday() || $certificate->issue_date->isPast()))
                    <a href="{{ route('intern.certificates.print', $certificate->id) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 mt-3 px-4 py-2 rounded-full text-xs font-bold"
                       style="background:rgba(250,204,21,0.18);border:1px solid rgba(250,204,21,0.4);color:#fde68a;">
                        <i class="fas fa-award"></i>
                        Lihat Sertifikat
                    </a>
                @endif
            </div>

            {{-- Summary right --}}
            <div class="flex-shrink-0 text-center sm:text-right">
                <p class="text-blue-200 text-xs font-semibold uppercase tracking-widest mb-1">
                    Periode Magang
                </p>

                <p class="text-lg font-extrabold text-white mono">
                    {{ $intern->start_date ? $intern->start_date->format('d M Y') : '-' }}
                    &mdash;
                    {{ $intern->end_date ? $intern->end_date->format('d M Y') : '-' }}
                </p>
            </div>
        </div>
    </div>

    @if(isset($todaySharingSessions) && $todaySharingSessions->count() > 0)
        <div class="mb-6 space-y-4">
            @foreach($todaySharingSessions as $session)
                <div class="bg-white rounded-3xl shadow-sm border border-blue-100 p-5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-blue-500 mb-2">
                                Sharing Session Hari Ini
                            </p>

                            <h3 class="text-xl font-bold text-gray-800">
                                {{ $session->title ?? 'Materi Belum Diisi' }}
                            </h3>

                            <p class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-clock text-green-500 mr-2"></i>
                                {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i') : '-' }}
                                WITA - Selesai
                            </p>

                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-map-marker-alt text-orange-500 mr-2"></i>
                                {{ $session->location ?? '-' }}
                            </p>

                            @if($session->speaker_user_id === auth()->id() && $session->material_status !== 'lengkap')
                                <p class="mt-3 inline-flex items-center gap-2 bg-yellow-50 text-yellow-700 border border-yellow-200 px-4 py-2 rounded-2xl text-sm font-semibold">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    Anda sebagai narasumber belum melengkapi materi sharing session.
                                </p>
                            @endif

                            @if($session->moderator_user_id === auth()->id() && empty($session->documentation_photo))
                                <p class="mt-3 inline-flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 px-4 py-2 rounded-2xl text-sm font-semibold">
                                    <i class="fas fa-camera"></i>
                                    Anda sebagai moderator belum mengunggah gambar dokumentasi.
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            @if($session->speaker_user_id === auth()->id())
                                <a href="{{ route('intern.sharing-session.edit-materi', $session) }}"
                                   class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-edit"></i>
                                    Lengkapi Materi
                                </a>
                            @endif

                            @if($session->moderator_user_id === auth()->id() && empty($session->documentation_photo))
                                <a href="{{ route('intern.sharing-session.edit-materi', $session) }}"
                                   class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-camera"></i>
                                    Upload Dokumentasi
                                </a>
                            @endif

                            @php
                                $formEvaluasiUrl = $session->evaluation_form_link;
                                $materiLengkap = $session->material_status === 'lengkap';
                                $bolehIsiEvaluasi = $materiLengkap && $session->evaluation_is_open;
                            @endphp

                            @if($bolehIsiEvaluasi)
                                <a href="{{ $formEvaluasiUrl }}"
                                   target="_blank"
                                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-clipboard-check"></i>
                                    Isi Evaluasi
                                </a>
                            @elseif(!$materiLengkap)
                                <span class="inline-flex items-center justify-center gap-2 bg-yellow-50 text-yellow-700 border border-yellow-200 px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-file-circle-xmark"></i>
                                    Materi Belum Lengkap
                                </span>
                            @elseif($session->evaluation_already_closed)
                                <span class="inline-flex items-center justify-center gap-2 bg-red-50 text-red-600 border border-red-200 px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-lock"></i>
                                    Form Sudah Ditutup
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-500 px-5 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-clock"></i>
                                    Form Belum Dibuka
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- STAT TILES --}}
    <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-4 anim-2">
        <div class="stat-tile" style="--stat-color:#22c55e;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                <i class="fas fa-calendar-check"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Kehadiran</p>
                <p class="stat-value">{{ $totalHadir }}</p>
            </div>
        </div>

        <div class="stat-tile" style="--stat-color:#f59e0b;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <i class="fas fa-calendar-times"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Izin</p>
                <p class="stat-value">{{ $totalIzin }}</p>
            </div>
        </div>

        <div class="stat-tile" style="--stat-color:#ef5350;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#ef5350,#e53935);">
                <i class="fas fa-calendar-minus"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Sakit</p>
                <p class="stat-value">{{ $totalSakit }}</p>
            </div>
        </div>

        <div class="stat-tile" style="--stat-color:#f97316;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#f97316,#ea580c);">
                <i class="fas fa-user-times"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Tidak Hadir</p>
                <p class="stat-value">{{ $totalTidakHadir }}</p>
            </div>
        </div>

        <div class="stat-tile" style="--stat-color:#10b981;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <i class="fas fa-file-alt"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Laporan</p>
                <p class="stat-value" style="font-size:1.1rem;margin-top:0.6rem;">
                    {{ $hasFinalReport ? 'Sudah' : 'Belum' }}
                </p>
            </div>
        </div>

        <div class="stat-tile" style="--stat-color:#8b5cf6;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                <i class="fas fa-graduation-cap"></i>
            </div>

            <div class="stat-content">
                <p class="stat-label">Mikro Skill</p>
                <p class="stat-value">
                    {{ $microSkillApproved }}
                    <span style="font-size:1rem;color:#94a3b8;">
                        /{{ \App\Models\MicroSkill::count() }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- WORKSPACE: Tugas Saya & Pesan belum dibaca (hanya tampil bila modul Workspace aktif) --}}
    <x-workspace.dashboard-widgets role="intern" class="anim-2" />

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Status Absensi Hari Ini --}}
            <div class="panel anim-3">
                <p class="section-label">Status Absensi Hari Ini</p>

                @if($todayAttendance)
                    <div class="space-y-3">

                        <div class="status-item" style="background:#f0f4ff;border-color:#c7d2fe;">
                            <div class="flex items-center gap-3">
                                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
                                    @if($todayAttendance->status=='hadir') background:#dcfce7;
                                    @elseif($todayAttendance->status=='izin') background:#fef9c3;
                                    @elseif($todayAttendance->status=='sakit') background:#ffedd5;
                                    @else background:#fee2e2; @endif">
                                    <i class="fas
                                        @if($todayAttendance->status=='hadir') fa-check-circle" style="color:#16a34a;
                                        @elseif($todayAttendance->status=='izin') fa-clipboard" style="color:#a16207;
                                        @elseif($todayAttendance->status=='sakit') fa-heartbeat" style="color:#c2410c;
                                        @else fa-times-circle" style="color:#dc2626; @endif"></i>
                                </div>

                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">Status Kehadiran</p>
                                    <p class="text-xs text-gray-400">
                                        {{ \Carbon\Carbon::today()->locale('id')->translatedFormat('l, d F Y') }}
                                    </p>
                                </div>
                            </div>

                            <span class="count-pill"
                                style="@if($todayAttendance->status=='hadir') background:#dcfce7;color:#15803d;
                                @elseif($todayAttendance->status=='izin') background:#fef9c3;color:#a16207;
                                @elseif($todayAttendance->status=='sakit') background:#ffedd5;color:#c2410c;
                                @else background:#fee2e2;color:#b91c1c; @endif">
                                {{ ucfirst($todayAttendance->status == 'alfa' ? 'Tidak Hadir' : $todayAttendance->status) }}
                            </span>
                        </div>

                        @if($todayAttendance->status == 'hadir')
                            <div class="divider"></div>

                            <div class="info-row">
                                <span class="text-gray-500 text-sm flex items-center gap-2">
                                    <i class="fas fa-sign-in-alt text-blue-400"></i>
                                    Check In
                                </span>

                                <span class="mono font-semibold text-gray-800">
                                    {{ $todayAttendance->check_in ? \Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') : '—' }}
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="text-gray-500 text-sm flex items-center gap-2">
                                    <i class="fas fa-sign-out-alt text-indigo-400"></i>
                                    Check Out
                                </span>

                                <span class="mono font-semibold text-gray-800">
                                    {{ $todayAttendance->check_out ? \Carbon\Carbon::parse($todayAttendance->check_out)->format('H:i') : '—' }}
                                </span>
                            </div>

                            @if(!$todayAttendance->check_out)
                                <form action="{{ route('intern.attendance.checkout') }}"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      id="checkoutForm"
                                      class="mt-4">
                                    @csrf

                                    <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4 sm:p-6 mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-4">
                                            <i class="fas fa-camera mr-1 text-blue-500"></i>
                                            Foto Bukti Absensi Keluar
                                        </label>

                                        <div class="mb-4">
                                            <video id="checkoutVideo"
                                                   width="100%"
                                                   height="auto"
                                                   class="border-2 border-blue-300 rounded-lg hidden shadow-md transform -scale-x-100"
                                                   autoplay
                                                   playsinline></video>

                                            <canvas id="checkoutCanvas" class="hidden"></canvas>

                                            <img id="checkoutCapturedImage"
                                                 class="w-full max-w-md mx-auto border-2 border-green-300 rounded-lg hidden mb-4 shadow-md"
                                                 alt="Captured image">
                                        </div>

                                        <div class="flex flex-wrap gap-3 mb-4">
                                            <button type="button"
                                                    id="checkoutStartCamera"
                                                    class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                                                <i class="fas fa-camera mr-2"></i>
                                                Buka Kamera
                                            </button>

                                            <button type="button"
                                                    id="checkoutCapturePhoto"
                                                    class="hidden inline-flex items-center justify-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                                                <i class="fas fa-camera-retro mr-2"></i>
                                                Ambil Foto
                                            </button>

                                            <button type="button"
                                                    id="checkoutStopCamera"
                                                    class="hidden inline-flex items-center justify-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                                                <i class="fas fa-stop mr-2"></i>
                                                Stop Kamera
                                            </button>
                                        </div>

                                        <input type="file"
                                               name="photo"
                                               id="checkoutPhoto"
                                               accept="image/*"
                                               class="hidden">

                                        <input type="hidden"
                                               name="photo_data"
                                               id="checkoutPhotoData"
                                               value="">

                                        <div class="mt-4 bg-blue-100 border border-blue-300 rounded-lg p-3">
                                            <p class="text-xs text-blue-800 flex items-start">
                                                <i class="fas fa-info-circle mr-2 mt-0.5"></i>
                                                <span>Pastikan wajah Anda terlihat jelas pada foto. Foto akan digunakan sebagai bukti absensi keluar.</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                                            <i class="fas fa-sign-out-alt mr-2"></i>
                                            Check Out
                                        </button>
                                    </div>
                                </form>
                            @endif

                            @if($todayAttendance->photo_path || $todayAttendance->photo_checkout)
                                <div class="divider"></div>

                                <div class="flex gap-4 flex-wrap">
                                    @if($todayAttendance->photo_path)
                                        <div>
                                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">
                                                Foto Check In
                                            </p>

                                            <img src="{{ $todayAttendance->check_in_photo_url }}"
                                                 alt="Check In"
                                                 class="w-24 h-24 object-cover rounded-xl border-2 border-green-100 cursor-pointer hover:border-green-300 transition-all"
                                                 onclick="window.open('{{ $todayAttendance->check_in_photo_url }}','_blank')">
                                        </div>
                                    @endif

                                    @if($todayAttendance->photo_checkout)
                                        <div>
                                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">
                                                Foto Check Out
                                            </p>

                                            <img src="{{ $todayAttendance->check_out_photo_url }}"
                                                 alt="Check Out"
                                                 class="w-24 h-24 object-cover rounded-xl border-2 border-indigo-100 cursor-pointer hover:border-indigo-300 transition-all"
                                                 onclick="window.open('{{ $todayAttendance->check_out_photo_url }}','_blank')">
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <div style="width:56px;height:56px;background:#f0f4ff;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                            <i class="fas fa-calendar-plus text-2xl" style="color:#3b4fd8;"></i>
                        </div>

                        <p class="text-sm text-gray-500 mb-4 font-medium">
                            Anda belum melakukan absensi hari ini.
                        </p>

                        @if($cekaktif)
                            <a href="{{ route('intern.attendance.create') }}" class="action-btn" style="width:auto;">
                                <i class="fas fa-calendar-plus"></i>
                                Absensi Sekarang
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Quick Links --}}
            <div class="panel anim-4">
                <p class="section-label">Aksi Cepat</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="{{ route('intern.attendance.index') }}" class="action-btn">
                        <i class="fas fa-calendar-check"></i>
                        Riwayat Absensi
                    </a>

                    <a href="{{ route('intern.logbook.index') }}" class="action-btn">
                        <i class="fas fa-book"></i>
                        Logbook Harian
                    </a>

                    <a href="{{ route('intern.report.index') }}" class="action-btn">
                        <i class="fas fa-file-alt"></i>
                        Laporan Akhir

                        @if(!$hasFinalReport)
                            <span style="margin-left:auto;background:#fef08a;color:#854d0e;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:700;">
                                Belum
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('intern.microskill.index') }}" class="action-btn">
                        <i class="fas fa-graduation-cap"></i>
                        Mikro Skill

                        @if($microSkillTotal > 0)
                            <span style="margin-left:auto;background:#ede9fe;color:#6d28d9;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:700;">
                                {{ $microSkillApproved }}/{{ $microSkillTotal }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>

            @if(!empty($latestApprovedLogbook))
                <div class="panel anim-4">
                    <p class="section-label">Notifikasi: Logbook Disetujui</p>

                    <div class="rounded-2xl bg-white px-4 py-3 shadow-sm border border-slate-100">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1 text-emerald-600">
                                <i class="fas fa-circle-check"></i>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ \Illuminate\Support\Str::limit($latestApprovedLogbook->activity, 120) }}
                                    </div>

                                    <a href="{{ route('intern.logbook.show', $latestApprovedLogbook) }}"
                                       class="text-xs text-blue-600 ml-2">
                                        Detail
                                    </a>
                                </div>

                                <div class="text-xs text-slate-500 mt-1">
                                    {{ $latestApprovedLogbook->approved_at ? \Carbon\Carbon::parse($latestApprovedLogbook->approved_at)->locale('id')->isoFormat('D MMMM Y') : '' }}
                                </div>

                                @if($latestApprovedLogbook->approval_note)
                                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">
                                        <strong>Catatan:</strong>
                                        {{ $latestApprovedLogbook->approval_note }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div class="space-y-5">

            {{-- Donut Distribusi Kehadiran --}}
            <div class="panel anim-2 flex flex-col items-center">
                <p class="section-label w-full">Distribusi Kehadiran</p>

                <div class="relative" style="width:160px;height:160px;">
                    <svg width="160"
                         height="160"
                         viewBox="0 0 100 100"
                         class="donut-svg"
                         style="transform:rotate(-90deg);">

                        <circle cx="50"
                                cy="50"
                                r="42"
                                fill="none"
                                stroke="#e2e8f0"
                                stroke-width="12"/>

                        <circle id="donut-hadir"
                                cx="50"
                                cy="50"
                                r="42"
                                fill="none"
                                stroke="#22c55e"
                                stroke-width="12"
                                stroke-dasharray="{{ $circ }}"
                                stroke-dashoffset="{{ $circ }}"
                                stroke-linecap="butt"
                                style="transition:stroke-dashoffset 1.2s ease;"
                                data-dash="{{ $hadirDash }}"
                                data-start="0"/>

                        <circle id="donut-izin"
                                cx="50"
                                cy="50"
                                r="42"
                                fill="none"
                                stroke="#f59e0b"
                                stroke-width="12"
                                stroke-dasharray="{{ $circ }}"
                                stroke-dashoffset="{{ $circ }}"
                                stroke-linecap="butt"
                                style="transition:stroke-dashoffset 1.2s ease 0.2s;"
                                data-dash="{{ $izinDash }}"
                                data-start="{{ $izinStart }}"/>

                        <circle id="donut-sakit"
                                cx="50"
                                cy="50"
                                r="42"
                                fill="none"
                                stroke="#ef5350"
                                stroke-width="12"
                                stroke-dasharray="{{ $circ }}"
                                stroke-dashoffset="{{ $circ }}"
                                stroke-linecap="butt"
                                style="transition:stroke-dashoffset 1.2s ease 0.4s;"
                                data-dash="{{ $sakitDash }}"
                                data-start="{{ $sakitStart }}"/>

                        <circle id="donut-alfa"
                                cx="50"
                                cy="50"
                                r="42"
                                fill="none"
                                stroke="#f97316"
                                stroke-width="12"
                                stroke-dasharray="{{ $circ }}"
                                stroke-dashoffset="{{ $circ }}"
                                stroke-linecap="butt"
                                style="transition:stroke-dashoffset 1.2s ease 0.6s;"
                                data-dash="{{ $alfaDash }}"
                                data-start="{{ $alfaStart }}"/>
                    </svg>

                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span class="mono" style="font-size:26px;font-weight:800;color:#1e3a8a;">
                            {{ $pctHadir }}%
                        </span>

                        <span style="font-size:10px;color:#94a3b8;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">
                            Hadir
                        </span>
                    </div>
                </div>

                <div class="mt-5 space-y-2 w-full">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;background:#22c55e;border-radius:3px;display:inline-block;"></span>
                            <span class="text-gray-600 font-medium">Hadir</span>
                        </div>

                        <span class="mono text-gray-800 font-semibold">
                            {{ $totalHadir }} hari
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;background:#f59e0b;border-radius:3px;display:inline-block;"></span>
                            <span class="text-gray-600 font-medium">Izin</span>
                        </div>

                        <span class="mono text-gray-800 font-semibold">
                            {{ $totalIzin }} hari
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;background:#ef5350;border-radius:3px;display:inline-block;"></span>
                            <span class="text-gray-600 font-medium">Sakit</span>
                        </div>

                        <span class="mono text-gray-800 font-semibold">
                            {{ $totalSakit }} hari
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;background:#f97316;border-radius:3px;display:inline-block;"></span>
                            <span class="text-gray-600 font-medium">Tidak Hadir</span>
                        </div>

                        <span class="mono text-gray-800 font-semibold">
                            {{ $totalTidakHadir }} hari
                        </span>
                    </div>
                </div>
            </div>

            {{-- Leaderboard Mikro Skill --}}
            <div class="panel anim-4">
                <p class="section-label">Leaderboard Mikro Skill</p>

                @if(isset($topMicroSkills) && $topMicroSkills->count() > 0)
                    <div class="space-y-2">
                        @foreach($topMicroSkills->take(3) as $index => $row)
                            @php
                                $isMe = ($row['intern_id'] === $intern->id);
                                $rankStyle = match($index) {
                                    0 => 'background:linear-gradient(135deg,#f59e0b,#d97706);',
                                    1 => 'background:linear-gradient(135deg,#94a3b8,#64748b);',
                                    2 => 'background:linear-gradient(135deg,#f97316,#ea580c);',
                                    default => 'background:linear-gradient(135deg,#3b82f6,#6366f1);',
                                };
                            @endphp

                            <div class="lb-item {{ $isMe ? 'is-me' : '' }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative flex-shrink-0">
                                        <span style="width:32px;height:32px;border-radius:50%;{{ $rankStyle }}color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
                                            {{ $index + 1 }}
                                        </span>

                                        @if($index < 3)
                                            <i class="fas fa-crown"
                                               style="position:absolute;top:-7px;right:-4px;font-size:9px;color:#f59e0b;"></i>
                                        @endif
                                    </div>

                                    @if(!empty($row['photo_path']))
                                        <img src="{{ url('storage/'.$row['photo_path']) }}"
                                             class="w-9 h-9 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                                    @else
                                        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#60a5fa,#818cf8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-user text-white" style="font-size:12px;"></i>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 text-sm truncate">
                                            {{ $row['name'] }}

                                            @if($isMe)
                                                <span style="font-size:10px;background:#ede9fe;color:#6d28d9;padding:1px 6px;border-radius:999px;font-weight:700;">
                                                    Kamu
                                                </span>
                                            @endif
                                        </p>

                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $row['institution'] }}
                                        </p>
                                    </div>
                                </div>

                                <span class="count-pill flex-shrink-0" style="background:#e0e7ff;color:#3730a3;">
                                    {{ $row['total'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <a href="{{ route('intern.microskill.leaderboard') }}" class="cta-btn">
                            Lihat Selengkapnya
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-gray-400">
                        <i class="fas fa-chart-line text-3xl mb-3 text-gray-200"></i>
                        <p class="text-sm">
                            Belum ada data mikro skill.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>
</div>

@push('scripts')
<script>
    const checkoutVideo = document.getElementById('checkoutVideo');
    const checkoutCanvas = document.getElementById('checkoutCanvas');
    const checkoutCapturedImage = document.getElementById('checkoutCapturedImage');
    const checkoutStartCameraBtn = document.getElementById('checkoutStartCamera');
    const checkoutCapturePhotoBtn = document.getElementById('checkoutCapturePhoto');
    const checkoutStopCameraBtn = document.getElementById('checkoutStopCamera');
    const checkoutPhotoInput = document.getElementById('checkoutPhoto');
    const checkoutPhotoData = document.getElementById('checkoutPhotoData');
    let checkoutStream = null;

    if (checkoutStartCameraBtn) {
        checkoutStartCameraBtn.addEventListener('click', async () => {
            try {
                checkoutStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false
                });

                checkoutVideo.srcObject = checkoutStream;
                checkoutVideo.classList.remove('hidden');
                checkoutCapturedImage.classList.add('hidden');
                checkoutStartCameraBtn.classList.add('hidden');
                checkoutCapturePhotoBtn.classList.remove('hidden');
                checkoutStopCameraBtn.classList.remove('hidden');
            } catch (err) {
                alert('Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.');
            }
        });

        checkoutCapturePhotoBtn.addEventListener('click', () => {
            checkoutCanvas.width = checkoutVideo.videoWidth;
            checkoutCanvas.height = checkoutVideo.videoHeight;

            const ctx = checkoutCanvas.getContext('2d');

            ctx.translate(checkoutCanvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(checkoutVideo, 0, 0);
            ctx.setTransform(1, 0, 0, 1, 0, 0);

            const imageData = checkoutCanvas.toDataURL('image/png');

            checkoutCapturedImage.src = imageData;
            checkoutCapturedImage.classList.remove('hidden');
            checkoutPhotoData.value = imageData;

            fetch(imageData).then(r => r.blob()).then(blob => {
                const file = new File([blob], 'checkout-photo.png', {
                    type: 'image/png'
                });

                const dt = new DataTransfer();

                dt.items.add(file);
                checkoutPhotoInput.files = dt.files;
            });

            if (checkoutStream) {
                checkoutStream.getTracks().forEach(t => t.stop());
                checkoutStream = null;
            }

            checkoutVideo.classList.add('hidden');
            checkoutStartCameraBtn.innerHTML = '<i class="fas fa-camera mr-2"></i>Ambil Foto Ulang';
            checkoutStartCameraBtn.classList.remove('hidden');
            checkoutCapturePhotoBtn.classList.add('hidden');
            checkoutStopCameraBtn.classList.add('hidden');
        });

        checkoutStopCameraBtn.addEventListener('click', () => {
            if (checkoutStream) {
                checkoutStream.getTracks().forEach(t => t.stop());
                checkoutStream = null;
            }

            checkoutVideo.classList.add('hidden');
            checkoutStartCameraBtn.classList.remove('hidden');
            checkoutCapturePhotoBtn.classList.add('hidden');
            checkoutStopCameraBtn.classList.add('hidden');
        });

        const checkoutForm = document.getElementById('checkoutForm');

        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                if (!checkoutPhotoData.value && !checkoutPhotoInput.files.length) {
                    e.preventDefault();
                    alert('Silakan ambil foto terlebih dahulu.');
                    return false;
                }

                if (checkoutStream) {
                    checkoutStream.getTracks().forEach(t => t.stop());
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const circ = {{ $circ }};

        setTimeout(() => {
            ['donut-hadir', 'donut-izin', 'donut-sakit', 'donut-alfa'].forEach(id => {
                const el = document.getElementById(id);

                if (!el) {
                    return;
                }

                const dash = parseFloat(el.dataset.dash || 0);
                const start = parseFloat(el.dataset.start || 0);

                el.style.strokeDasharray = `${dash} ${circ - dash}`;
                el.style.strokeDashoffset = -start;
            });
        }, 400);

        setTimeout(() => {
            document.querySelectorAll('.stat-bar-fill').forEach(el => {
                el.style.width = el.dataset.width + '%';
            });
        }, 300);
    });
</script>
@endpush
@endsection