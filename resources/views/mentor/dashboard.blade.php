@extends('layouts.app')

@section('title', 'Dashboard Mentor - Sistem Magang')

@push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

        *,
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .mono {
            font-family: 'DM Mono', monospace;
        }

        /* ── Page background ── */
        .dash-bg {
            min-height: 100vh;
            background: #f0f4ff;
        }

        /* ── Hero strip ── */
        .hero-strip {
            background: #0f1f6e;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .hero-strip::before {
            content: '';
            position: absolute;
            top: -70px;
            right: -50px;
            width: 220px;
            height: 220px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-strip::after {
            content: '';
            position: absolute;
            bottom: -90px;
            left: 28%;
            width: 280px;
            height: 280px;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }

        /* ── Stat tiles ── */
        .stat-tile {
            background: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            border: 1px solid #e8eeff;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform .2s ease, box-shadow .2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-tile::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 0 0 14px 14px;
        }

        .stat-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(20, 40, 120, 0.10);
        }

        .tile-blue::after {
            background: linear-gradient(90deg, #3b82f6, #6366f1);
        }

        .tile-green::after {
            background: linear-gradient(90deg, #22c55e, #10b981);
        }

        .tile-yellow::after {
            background: linear-gradient(90deg, #f59e0b, #f97316);
        }

        .tile-gray::after {
            background: linear-gradient(90deg, #64748b, #94a3b8);
        }

        .tile-indigo::after {
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
        }

        .tile-rose::after {
            background: linear-gradient(90deg, #f43f5e, #e11d48);
        }

        .tile-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── Attendance bar ── */
        .att-bar-track {
            height: 4px;
            background: #eef0ff;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 4px;
        }

        .att-bar-fill {
            height: 100%;
            border-radius: 99px;
            width: 0;
            transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
        }

        /* ── Panel ── */
        .panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8eeff;
            overflow: hidden;
        }

        .panel-header {
            background: #0f1f6e;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header h2 {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.01em;
            margin: 0;
        }

        .panel-header i {
            color: #93c5fd;
            font-size: 15px;
        }

        .panel-header .hd-badge {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.12);
            color: #bfdbfe;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 999px;
        }

        .panel-body {
            padding: 18px 20px;
        }

        .panel-body::-webkit-scrollbar {
            width: 5px;
        }

        .panel-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .panel-body::-webkit-scrollbar-thumb {
            background: #c7d2fe;
            border-radius: 3px;
        }

        .panel-body::-webkit-scrollbar-thumb:hover {
            background: #a5b4fc;
        }

        /* ── Equal height columns ── */
        @media (min-width: 1024px) {
            .grid.grid-cols-1.lg\:grid-cols-5 {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
                align-items: stretch;
            }

            .grid.grid-cols-1.lg\:grid-cols-5>.lg\:col-span-3 {
                grid-column: span 3;
                display: flex !important;
                flex-direction: column;
            }

            .grid.grid-cols-1.lg\:grid-cols-5>.lg\:col-span-2 {
                grid-column: span 2;
                display: flex !important;
                flex-direction: column;
            }

            .grid.grid-cols-1.lg\:grid-cols-5>.lg\:col-span-3>.panel {
                height: 100%;
                display: flex;
                flex-direction: column;
            }
        }

        /* ── Intern row ── */
        .intern-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f8faff;
            border: 1px solid #e8eeff;
            transition: all .2s ease;
        }

        .intern-row:hover {
            background: #eff2ff;
            border-color: #c7d2fe;
            transform: translateX(3px);
        }

        .avatar-sm {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        /* ── Status badge ── */
        .s-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .s-hadir {
            background: #dcfce7;
            color: #15803d;
        }

        .s-izin {
            background: #fef9c3;
            color: #92400e;
        }

        .s-sakit {
            background: #ffedd5;
            color: #c2410c;
        }

        .s-alfa {
            background: #fee2e2;
            color: #b91c1c;
        }

        .s-absent {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* ── Mini pill ── */
        .mini-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
        }

        /* ── Leaderboard podium ── */
        .podium-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1.5px solid transparent;
            transition: all .2s ease;
        }

        .podium-item:hover {
            transform: translateX(4px);
        }

        .podium-1 {
            background: linear-gradient(100deg, #fffbeb, #fef9c3);
            border-color: #fde68a;
        }

        .podium-2 {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .podium-3 {
            background: linear-gradient(100deg, #fff7ed, #ffedd5);
            border-color: #fed7aa;
        }

        .rank-badge {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #fff;
            box-shadow: 0 3px 10px rgba(251, 191, 36, .4);
        }

        .rank-2 {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            color: #fff;
            box-shadow: 0 3px 10px rgba(100, 116, 139, .3);
        }

        .rank-3 {
            background: linear-gradient(135deg, #fb923c, #ea580c);
            color: #fff;
            box-shadow: 0 3px 10px rgba(251, 146, 60, .35);
        }

        /* ── Absensi table ── */
        .att-tr {
            transition: background .15s ease;
        }

        .att-tr:hover {
            background: #f5f7ff;
        }

        .att-tr-absent {
            background: #fff5f5;
        }

        /* ── Section label ── */
        .sec-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        /* ── Tab toggle ── */
        .tab-btn {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
            border: none;
            background: transparent;
            color: #64748b;
        }

        .tab-btn.active {
            background: #0f1f6e;
            color: #fff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ── Logbook card ── */
        .logbook-card {
            background: #f8faff;
            border: 1.5px solid #e8eeff;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all .2s ease;
        }

        .logbook-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 40, 120, 0.08);
        }

        /* ── Fade-in animations ── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .a1 {
            animation: fadeUp .5s ease both;
        }

        .a2 {
            animation: fadeUp .5s .08s ease both;
        }

        .a3 {
            animation: fadeUp .5s .16s ease both;
        }

        .a4 {
            animation: fadeUp .5s .24s ease both;
        }

        .a5 {
            animation: fadeUp .5s .32s ease both;
        }
    </style>
@endpush

@section('content')
    @php
        if (!isset($alumni)) {
            $alumniCollection = $interns->filter(function ($i) {
                $s = strtolower($i->status ?? '');
                return in_array($s, ['tidak aktif', 'inactive', 'pelepasan', 'released', 'alumni']);
            });
        } else {
            $alumniCollection = $alumni;
        }
        $alumniCount = is_countable($alumniCollection) ? $alumniCollection->count() : 0;
        $hadirCount = $todayAttendances->where('status', 'hadir')->count();
        $izinCount = $todayAttendances->whereIn('status', ['izin', 'sakit'])->count();
        $alfaCount = $todayAttendances->where('status', 'alfa')->count() + $todayAbsentInterns->count();
        $internsTotal = $interns->count();
        $hadirPct = $internsTotal ? round(($hadirCount / $internsTotal) * 100) : 0;
    @endphp

    <div class="dash-bg py-7">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- ── HERO ── --}}
            <div class="hero-strip shadow-lg a1">
                <div class="relative z-10 px-6 py-7 flex flex-col sm:flex-row items-center sm:items-start gap-5">

                    {{-- Avatar --}}
                    <div
                        style="background:rgba(255,255,255,0.10);border-radius:14px;padding:14px;border:1.5px solid rgba(255,255,255,0.12);flex-shrink:0;">
                        <i class="fas fa-chalkboard-teacher text-blue-300 text-2xl"></i>
                    </div>

                    {{-- Identity --}}
                    <div class="flex-1 text-center sm:text-left">
                        <div
                            style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.14);color:#bfdbfe;font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;margin-bottom:8px;">
                            <i class="fas fa-circle" style="font-size:6px;color:#34d399;"></i> Aktif Membimbing
                        </div>
                        <h1 class="text-xl font-bold text-white mb-1">{{ $mentor?->name ?? auth()->user()->name }}</h1>
                        <p class="text-sm" style="color:#93c5fd;">
                            Ringkasan bimbingan hari ini &mdash;
                            <span
                                class="font-semibold text-white">{{ \Carbon\Carbon::today()->locale('id')->translatedFormat('l, d F Y') }}</span>
                        </p>
                        @if (isset($todayLogbooksPending) && count($todayLogbooksPending) > 0)
                            <div
                                style="margin-top:10px;display:inline-flex;align-items:center;gap:10px;background:#fff7ed;padding:8px 12px;border-radius:12px;">
                                <i class="fas fa-book" style="color:#c2410c;"></i>
                                <div style="font-size:13px;color:#92400e;font-weight:700;">
                                    Ada {{ count($todayLogbooksPending) }} logbook perlu direview hari ini.
                                    <a href="{{ route('mentor.logbook.index') }}"
                                        style="margin-left:8px;color:#7c2d12;text-decoration:underline;font-weight:800;">Lihat
                                        sekarang</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Attendance meter --}}
                    <div class="text-center sm:text-right flex-shrink-0">
                        <p
                            style="font-size:11px;color:#7dd3fc;font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">
                            Kehadiran Hari Ini</p>
                        <p class="mono" style="font-size:44px;font-weight:800;color:#fff;line-height:1;">
                            {{ $hadirPct }}<span style="font-size:20px;color:#7dd3fc;">%</span>
                        </p>
                        <p style="font-size:12px;color:#7dd3fc;margin-top:2px;">{{ $hadirCount }} / {{ $internsTotal }}
                            hadir</p>
                        <div
                            style="height:4px;background:rgba(255,255,255,0.12);border-radius:99px;margin-top:8px;overflow:hidden;width:140px;margin-left:auto;">
                            <div id="hero-bar"
                                style="height:100%;background:linear-gradient(90deg,#34d399,#10b981);border-radius:99px;width:0;transition:width 1.2s ease;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── STAT TILES ── --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 a2">

                <div class="stat-tile tile-blue">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Anak<br>Magang</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1);"><i
                                class="fas fa-users"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $internsTotal }}</p>
                </div>

                <div class="stat-tile tile-green">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Hadir<br>Hari Ini</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#22c55e,#10b981);"><i
                                class="fas fa-calendar-check"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $hadirCount }}</p>
                    <div class="att-bar-track">
                        <div class="att-bar-fill" style="background:#22c55e;" data-w="{{ $hadirPct }}"></div>
                    </div>
                </div>

                <div class="stat-tile tile-yellow">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Izin /<br>Sakit</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#f59e0b,#f97316);"><i
                                class="fas fa-calendar-times"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $izinCount }}</p>
                    <div class="att-bar-track">
                        <div class="att-bar-fill" style="background:#f59e0b;"
                            data-w="{{ $internsTotal ? round(($izinCount / $internsTotal) * 100) : 0 }}"></div>
                    </div>
                </div>

                <div class="stat-tile tile-gray">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Tidak<br>Hadir</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#64748b,#94a3b8);"><i
                                class="fas fa-user-times"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $alfaCount }}</p>
                    <div class="att-bar-track">
                        <div class="att-bar-fill" style="background:#94a3b8;"
                            data-w="{{ $internsTotal ? round(($alfaCount / $internsTotal) * 100) : 0 }}"></div>
                    </div>
                </div>

                <div class="stat-tile tile-indigo">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Mikro Skill<br>Hari Ini</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);"><i
                                class="fas fa-graduation-cap"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $microTodayTotal }}</p>
                    <p class="text-xs font-medium text-gray-500">Total hari ini</p>
                </div>

                <div class="stat-tile tile-rose">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500 leading-tight">Alumni</p>
                        <div class="tile-icon" style="background:linear-gradient(135deg,#f43f5e,#e11d48);"><i
                                class="fas fa-user-graduate"></i></div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mono">{{ $alumniCount }}</p>
                </div>

            </div>

            {{-- ── WORKSPACE: Perlu Review & Tugas Terlambat (hanya tampil bila modul Workspace aktif) ── --}}
            <x-workspace.dashboard-widgets role="mentor" class="a2" />

            {{-- ── TWO-COLUMN ROW ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                {{-- LEFT: Intern list --}}
                <div class="lg:col-span-3 flex flex-col">
                    <div class="panel a3 flex flex-col flex-1">
                        <div class="panel-header">
                            <i class="fas fa-users-cog"></i>
                            <h2>Daftar Anak Magang</h2>
                            <span class="hd-badge">{{ $internsTotal }} orang</span>
                        </div>
                        <div class="panel-body" style="flex:1;overflow-y:auto;">
                            {{-- Tabs --}}
                            <div
                                style="display:flex;gap:4px;background:#f1f5f9;border-radius:10px;padding:3px;margin-bottom:14px;">
                                <button class="tab-btn active" onclick="switchTab('aktif', this)">
                                    <i class="fas fa-user-check mr-1"></i> Aktif ({{ $internsTotal }})
                                </button>
                                <button class="tab-btn" onclick="switchTab('alumni', this)">
                                    <i class="fas fa-user-graduate mr-1"></i> Alumni ({{ $alumniCount }})
                                </button>
                            </div>

                            {{-- Aktif tab --}}
                            <div id="tab-aktif" class="tab-content active">
                                @php
                                    $internsList = collect($interns);
                                    $internsPerPage = 8;
                                    $internsCurrentPage = request('interns_page', 1);
                                    $internsSliced = $internsList->slice(
                                        ($internsCurrentPage - 1) * $internsPerPage,
                                        $internsPerPage,
                                    );
                                    $internsTotalPages = ceil($internsList->count() / $internsPerPage);
                                @endphp
                                <div class="space-y-2">
                                    @forelse($internsSliced as $intern)
                                        <div class="intern-row">
                                            <div class="avatar-sm">{{ strtoupper(substr($intern->name, 0, 1)) }}</div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate">
                                                    {{ $intern->name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $intern->institution }}</p>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="mini-pill" style="background:#dbeafe;color:#1e40af;">
                                                    <i class="fas fa-calendar-alt" style="font-size:9px;"></i>
                                                    {{ $intern->attendances_count }}
                                                </span>
                                                <span class="mini-pill" style="background:#ede9fe;color:#5b21b6;">
                                                    <i class="fas fa-book" style="font-size:9px;"></i>
                                                    {{ $intern->micro_skills_count }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div style="text-align:center;padding:32px;color:#94a3b8;">
                                            <i class="fas fa-user-slash text-4xl mb-3" style="display:block;"></i>
                                            <p class="text-sm">Belum ada anak magang.</p>
                                        </div>
                                    @endforelse
                                </div>
                                @if ($internsList->count() > $internsPerPage)
                                    <div
                                        style="margin-top:14px;padding-top:14px;border-top:1px solid #e8eeff;display:flex;align-items:center;justify-content:space-between;">
                                        <div style="font-size:12px;color:#94a3b8;">
                                            Menampilkan
                                            {{ ($internsCurrentPage - 1) * $internsPerPage + 1 }}–{{ min($internsCurrentPage * $internsPerPage, $internsList->count()) }}
                                            dari {{ $internsList->count() }}
                                        </div>
                                        <div style="display:flex;gap:5px;">
                                            @if ($internsCurrentPage > 1)
                                                <a href="?interns_page=1"
                                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">
                                                    <i class="fas fa-chevron-left"></i> Awal
                                                </a>
                                            @endif
                                            @for ($i = max(1, $internsCurrentPage - 1); $i <= min($internsTotalPages, $internsCurrentPage + 1); $i++)
                                                @if ($i === $internsCurrentPage)
                                                    <button
                                                        style="display:inline-flex;align-items:center;padding:5px 11px;background:#0f1f6e;border:1px solid #0f1f6e;border-radius:7px;font-size:12px;font-weight:700;color:#fff;cursor:pointer;">{{ $i }}</button>
                                                @else
                                                    <a href="?interns_page={{ $i }}"
                                                        style="display:inline-flex;align-items:center;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">{{ $i }}</a>
                                                @endif
                                            @endfor
                                            @if ($internsCurrentPage < $internsTotalPages)
                                                <a href="?interns_page={{ $internsTotalPages }}"
                                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">
                                                    Akhir <i class="fas fa-chevron-right"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Alumni tab --}}
                            <div id="tab-alumni" class="tab-content">
                                @php
                                    $alumniList = collect($alumniCollection);
                                    $alumniPerPage = 8;
                                    $alumniCurrentPage = request('alumni_page', 1);
                                    $alumniSliced = $alumniList->slice(
                                        ($alumniCurrentPage - 1) * $alumniPerPage,
                                        $alumniPerPage,
                                    );
                                    $alumniTotalPages = ceil($alumniList->count() / $alumniPerPage);
                                @endphp
                                <div class="space-y-2">
                                    @forelse($alumniSliced as $a)
                                        <div class="intern-row" style="background:#fff5f5;border-color:#fecdd3;">
                                            <div class="avatar-sm"
                                                style="background:linear-gradient(135deg,#f43f5e,#be185d);">
                                                {{ strtoupper(substr($a->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate">
                                                    {{ $a->name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $a->institution }}</p>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="mini-pill" style="background:#dbeafe;color:#1e40af;">
                                                    <i class="fas fa-calendar-alt" style="font-size:9px;"></i>
                                                    {{ $a->attendances_count ?? 0 }}
                                                </span>
                                                <span class="mini-pill" style="background:#ede9fe;color:#5b21b6;">
                                                    <i class="fas fa-book" style="font-size:9px;"></i>
                                                    {{ $a->micro_skills_count ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div style="text-align:center;padding:32px;color:#94a3b8;">
                                            <i class="fas fa-inbox text-4xl mb-3" style="display:block;"></i>
                                            <p class="text-sm">Belum ada data alumni.</p>
                                        </div>
                                    @endforelse
                                </div>
                                @if ($alumniList->count() > $alumniPerPage)
                                    <div
                                        style="margin-top:14px;padding-top:14px;border-top:1px solid #e8eeff;display:flex;align-items:center;justify-content:space-between;">
                                        <div style="font-size:12px;color:#94a3b8;">
                                            Menampilkan
                                            {{ ($alumniCurrentPage - 1) * $alumniPerPage + 1 }}–{{ min($alumniCurrentPage * $alumniPerPage, $alumniList->count()) }}
                                            dari {{ $alumniList->count() }}
                                        </div>
                                        <div style="display:flex;gap:5px;">
                                            @if ($alumniCurrentPage > 1)
                                                <a href="?alumni_page=1"
                                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">
                                                    <i class="fas fa-chevron-left"></i> Awal
                                                </a>
                                            @endif
                                            @for ($i = max(1, $alumniCurrentPage - 1); $i <= min($alumniTotalPages, $alumniCurrentPage + 1); $i++)
                                                @if ($i === $alumniCurrentPage)
                                                    <button
                                                        style="display:inline-flex;align-items:center;padding:5px 11px;background:#0f1f6e;border:1px solid #0f1f6e;border-radius:7px;font-size:12px;font-weight:700;color:#fff;cursor:pointer;">{{ $i }}</button>
                                                @else
                                                    <a href="?alumni_page={{ $i }}"
                                                        style="display:inline-flex;align-items:center;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">{{ $i }}</a>
                                                @endif
                                            @endfor
                                            @if ($alumniCurrentPage < $alumniTotalPages)
                                                <a href="?alumni_page={{ $alumniTotalPages }}"
                                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:#f1f5f9;border:1px solid #e8eeff;border-radius:7px;font-size:12px;font-weight:600;color:#1e40af;text-decoration:none;">
                                                    Akhir <i class="fas fa-chevron-right"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Leaderboard + Donut --}}
                <div class="lg:col-span-2 flex flex-col gap-4">

                    {{-- Leaderboard --}}
                    <div class="panel a3">
                        <div class="panel-header">
                            <i class="fas fa-trophy" style="color:#fbbf24;"></i>
                            <h2>Top Mikro Skill</h2>
                        </div>
                        <div class="panel-body">
                            <p class="sec-label">Top 3 Bimbingan</p>
                            @if (isset($topMicroSkills) && count($topMicroSkills))
                                <div class="space-y-3">
                                    @foreach ($topMicroSkills->take(3) as $index => $row)
                                        <div class="podium-item podium-{{ $index + 1 }}">
                                            <div class="rank-badge rank-{{ $index + 1 }}">{{ $index + 1 }}</div>
                                            @if (!empty($row['photo_path']))
                                                <img src="{{ url('storage/' . $row['photo_path']) }}"
                                                    style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.1);flex-shrink:0;"
                                                    alt="{{ $row['name'] }}">
                                            @else
                                                <div
                                                    style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0;">
                                                    {{ strtoupper(substr($row['name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-gray-900 text-sm truncate">{{ $row['name'] }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate">{{ $row['institution'] }}</p>
                                            </div>
                                            <span
                                                style="display:inline-flex;align-items:center;gap:4px;background:#e0e7ff;color:#3730a3;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:800;font-family:'DM Mono',monospace;">
                                                <i class="fas fa-star" style="font-size:9px;color:#6366f1;"></i>
                                                {{ $row['total'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="text-align:center;padding:24px;color:#94a3b8;">
                                    <i class="fas fa-chart-line text-4xl mb-3" style="display:block;"></i>
                                    <p class="text-sm">Belum ada data.</p>
                                </div>
                            @endif
                            <div style="margin-top:16px;text-align:center;">
                                <a href="{{ route('mentor.microskill.leaderboard') }}"
                                    style="display:inline-flex;align-items:center;gap:8px;background:#0f1f6e;color:#fff;font-size:12px;font-weight:700;padding:9px 20px;border-radius:10px;text-decoration:none;width:100%;justify-content:center;transition:opacity .2s;"
                                    onmouseover="this.style.opacity='.85';" onmouseout="this.style.opacity='1';">
                                    Lihat Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Donut chart --}}
                    @php
                        $circ = 188.5;
                        $hadirDash = $internsTotal ? ($hadirCount / $internsTotal) * $circ : 0;
                        $izinDash = $internsTotal ? ($izinCount / $internsTotal) * $circ : 0;
                        $alfaDash = $internsTotal ? ($alfaCount / $internsTotal) * $circ : 0;
                    @endphp
                    <div class="panel a4">
                        <div class="panel-header">
                            <i class="fas fa-chart-pie"></i>
                            <h2>Distribusi Kehadiran</h2>
                        </div>
                        <div class="panel-body" style="display:flex;flex-direction:column;align-items:center;">
                            <div style="position:relative;width:120px;height:120px;margin-bottom:14px;">
                                <svg width="120" height="120" viewBox="0 0 80 80"
                                    style="transform:rotate(-90deg);">
                                    <circle cx="40" cy="40" r="30" fill="none" stroke="#eef0ff"
                                        stroke-width="10" />
                                    <circle id="d-hadir" cx="40" cy="40" r="30" fill="none"
                                        stroke="#22c55e" stroke-width="10" stroke-dasharray="{{ $circ }}"
                                        stroke-dashoffset="{{ $circ }}"
                                        style="transition:stroke-dashoffset 1.2s ease;" data-dash="{{ $hadirDash }}"
                                        data-start="0" />
                                    <circle id="d-izin" cx="40" cy="40" r="30" fill="none"
                                        stroke="#f59e0b" stroke-width="10" stroke-dasharray="{{ $circ }}"
                                        stroke-dashoffset="{{ $circ }}"
                                        style="transition:stroke-dashoffset 1.2s ease .15s;"
                                        data-dash="{{ $izinDash }}" data-start="{{ $hadirDash }}" />
                                    <circle id="d-alfa" cx="40" cy="40" r="30" fill="none"
                                        stroke="#ef4444" stroke-width="10" stroke-dasharray="{{ $circ }}"
                                        stroke-dashoffset="{{ $circ }}"
                                        style="transition:stroke-dashoffset 1.2s ease .3s;"
                                        data-dash="{{ $alfaDash }}" data-start="{{ $hadirDash + $izinDash }}" />
                                </svg>
                                <div
                                    style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                                    <span class="mono"
                                        style="font-size:22px;font-weight:800;color:#0f1f6e;">{{ $hadirPct }}%</span>
                                    <span
                                        style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Hadir</span>
                                </div>
                            </div>
                            <div style="width:100%;display:flex;flex-direction:column;gap:8px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:7px;">
                                        <span
                                            style="width:10px;height:10px;background:#22c55e;border-radius:3px;display:inline-block;"></span>
                                        <span style="font-size:13px;color:#374151;">Hadir</span>
                                    </div>
                                    <span class="mono"
                                        style="font-size:13px;font-weight:700;color:#374151;">{{ $hadirCount }}</span>
                                </div>
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:7px;">
                                        <span
                                            style="width:10px;height:10px;background:#f59e0b;border-radius:3px;display:inline-block;"></span>
                                        <span style="font-size:13px;color:#374151;">Izin/Sakit</span>
                                    </div>
                                    <span class="mono"
                                        style="font-size:13px;font-weight:700;color:#374151;">{{ $izinCount }}</span>
                                </div>
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:7px;">
                                        <span
                                            style="width:10px;height:10px;background:#ef4444;border-radius:3px;display:inline-block;"></span>
                                        <span style="font-size:13px;color:#374151;">Tidak Hadir</span>
                                    </div>
                                    <span class="mono"
                                        style="font-size:13px;font-weight:700;color:#374151;">{{ $alfaCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── ABSENSI TABLE ── --}}
            <div class="panel a5">
                <div class="panel-header">
                    <i class="fas fa-clipboard-check"></i>
                    <h2>Absensi Hari Ini</h2>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f0f4ff;">
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Nama</th>
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Status</th>
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Check In</th>
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Foto In</th>
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Check Out</th>
                                <th
                                    style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#0f1f6e;text-transform:uppercase;letter-spacing:.06em;">
                                    Foto Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hadirExists = false; @endphp

                            @forelse($todayAttendances->where('status', 'hadir') as $attendance)
                                @php $hadirExists = true; @endphp
                                <tr class="att-tr" style="border-top:1px solid #f1f5f9;">
                                    <td style="padding:11px 16px;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div
                                                style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:11px;flex-shrink:0;">
                                                {{ strtoupper(substr($attendance->intern->name, 0, 1)) }}
                                            </div>
                                            <span
                                                style="font-size:13px;font-weight:600;color:#374151;">{{ $attendance->intern->name }}</span>
                                        </div>
                                    </td>
                                    <td style="padding:11px 16px;">
                                        <span class="s-badge s-hadir"><i class="fas fa-check-circle"
                                                style="font-size:9px;"></i> Hadir</span>
                                    </td>
                                    <td
                                        style="padding:11px 16px;font-size:13px;font-weight:600;color:#374151;font-family:'DM Mono',monospace;">
                                        {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '—' }}
                                    </td>
                                    <td style="padding:11px 16px;">
                                        @if ($attendance->photo_path)
                                            @php $photoUrl = route('mentor.attendance.photo', ['filename' => basename($attendance->photo_path)]); @endphp
                                            @can('view', $attendance)
                                                <img src="{{ $photoUrl }}" alt="In"
                                                    style="width:38px;height:38px;object-fit:cover;border-radius:9px;border:1.5px solid #dbeafe;cursor:pointer;transition:all .2s;"
                                                    onclick="window.open('{{ $photoUrl }}', '_blank')"
                                                    onmouseover="this.style.borderColor='#3b82f6';this.style.transform='scale(1.08)';"
                                                    onmouseout="this.style.borderColor='#dbeafe';this.style.transform='none';">
                                            @else
                                                <span style="color:#d1d5db;font-size:12px;">Tidak ada akses</span>
                                            @endcan
                                        @else
                                            <span style="color:#d1d5db;font-size:12px;">—</span>
                                        @endif
                                    </td>
                                    <td
                                        style="padding:11px 16px;font-size:13px;font-weight:600;color:#374151;font-family:'DM Mono',monospace;">
                                        {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '—' }}
                                    </td>
                                    <td style="padding:11px 16px;">
                                        @if ($attendance->photo_checkout)
                                            @php $photoOutUrl = route('mentor.attendance.photo', ['filename' => basename($attendance->photo_checkout)]); @endphp
                                            @can('view', $attendance)
                                                <img src="{{ $photoOutUrl }}" alt="Out"
                                                    style="width:38px;height:38px;object-fit:cover;border-radius:9px;border:1.5px solid #dbeafe;cursor:pointer;transition:all .2s;"
                                                    onclick="window.open('{{ $photoOutUrl }}', '_blank')"
                                                    onmouseover="this.style.borderColor='#3b82f6';this.style.transform='scale(1.08)';"
                                                    onmouseout="this.style.borderColor='#dbeafe';this.style.transform='none';">
                                            @else
                                                <span style="color:#d1d5db;font-size:12px;">Tidak ada akses</span>
                                            @endcan
                                        @else
                                            <span style="color:#d1d5db;font-size:12px;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse

                            @forelse($todayAttendances->whereIn('status', ['izin', 'sakit', 'alfa']) as $attendance)
                                <tr class="att-tr" style="border-top:1px solid #f1f5f9;">
                                    <td style="padding:11px 16px;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div
                                                style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:11px;flex-shrink:0;">
                                                {{ strtoupper(substr($attendance->intern->name, 0, 1)) }}
                                            </div>
                                            <span
                                                style="font-size:13px;font-weight:600;color:#374151;">{{ $attendance->intern->name }}</span>
                                        </div>
                                    </td>
                                    <td style="padding:11px 16px;">
                                        <span
                                            class="s-badge @if ($attendance->status == 'izin') s-izin @elseif($attendance->status == 'sakit') s-sakit @else s-alfa @endif">
                                            @if (in_array($attendance->status, ['izin', 'sakit']))
                                                <i class="fas fa-notes-medical" style="font-size:9px;"></i>
                                            @else
                                                <i class="fas fa-times-circle" style="font-size:9px;"></i>
                                            @endif
                                            {{ $attendance->status == 'alfa' ? 'Tidak Hadir' : ucfirst($attendance->status) }}
                                        </span>
                                    </td>
                                    <td
                                        style="padding:11px 16px;font-size:13px;font-weight:600;color:#374151;font-family:'DM Mono',monospace;">
                                        {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '—' }}
                                    </td>
                                    <td style="padding:11px 16px;">
                                        @if ($attendance->photo_path)
                                            @php $photoUrl = route('mentor.attendance.photo', ['filename' => basename($attendance->photo_path)]); @endphp
                                            <img src="{{ $photoUrl }}" alt="In"
                                                style="width:38px;height:38px;object-fit:cover;border-radius:9px;border:1.5px solid #dbeafe;cursor:pointer;transition:all .2s;"
                                                onclick="window.open('{{ $photoUrl }}', '_blank')"
                                                onmouseover="this.style.borderColor='#3b82f6';this.style.transform='scale(1.08)';"
                                                onmouseout="this.style.borderColor='#dbeafe';this.style.transform='none';">
                                        @else
                                            <span style="color:#d1d5db;font-size:12px;">—</span>
                                        @endif
                                    </td>
                                    <td
                                        style="padding:11px 16px;font-size:13px;font-weight:600;color:#374151;font-family:'DM Mono',monospace;">
                                        {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '—' }}
                                    </td>
                                    <td style="padding:11px 16px;">
                                        @if ($attendance->photo_checkout)
                                            @php $photoOutUrl = route('mentor.attendance.photo', ['filename' => basename($attendance->photo_checkout)]); @endphp
                                            <img src="{{ $photoOutUrl }}" alt="Out"
                                                style="width:38px;height:38px;object-fit:cover;border-radius:9px;border:1.5px solid #dbeafe;cursor:pointer;transition:all .2s;"
                                                onclick="window.open('{{ $photoOutUrl }}', '_blank')"
                                                onmouseover="this.style.borderColor='#3b82f6';this.style.transform='scale(1.08)';"
                                                onmouseout="this.style.borderColor='#dbeafe';this.style.transform='none';">
                                        @else
                                            <span style="color:#d1d5db;font-size:12px;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse

                            @forelse($todayAbsentInterns as $absentIntern)
                                <tr class="att-tr att-tr-absent" style="border-top:1px solid #fecdd3;">
                                    <td style="padding:11px 16px;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div
                                                style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:11px;flex-shrink:0;">
                                                {{ strtoupper(substr($absentIntern->name, 0, 1)) }}
                                            </div>
                                            <span
                                                style="font-size:13px;font-weight:600;color:#374151;">{{ $absentIntern->name }}</span>
                                        </div>
                                    </td>
                                    <td style="padding:11px 16px;">
                                        <span class="s-badge s-absent"><i class="fas fa-times-circle"
                                                style="font-size:9px;"></i> Belum Absen</span>
                                    </td>
                                    <td colspan="4"
                                        style="padding:11px 16px;font-size:12px;color:#94a3b8;font-style:italic;">
                                        Belum melakukan absensi hari ini
                                    </td>
                                </tr>
                            @empty
                                @if (!$hadirExists)
                                    <tr>
                                        <td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;">
                                            <i class="fas fa-inbox"
                                                style="font-size:36px;display:block;margin-bottom:10px;"></i>
                                            <p style="font-size:13px;">Belum ada data absensi hari ini.</p>
                                        </td>
                                    </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── LOGBOOK ── --}}
            <div class="panel a5 mb-2">
                <div class="panel-header">
                    <i class="fas fa-book"></i>
                    <h2>Logbook Hari Ini Perlu Di-Review</h2>
                    <span class="hd-badge">{{ count($todayLogbooksPending ?? []) }} logbook</span>
                </div>
                <div class="panel-body">
                    @if ($todayLogbooksPending && count($todayLogbooksPending) > 0)
                        <div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
                            @foreach ($todayLogbooksPending as $logbook)
                                @php
                                    $isApproved = $logbook->approval_status === 'approved';
                                    $isPending = !$isApproved;
                                @endphp
                                <div class="logbook-card">
                                    {{-- Card header --}}
                                    <div
                                        style="background:#0f1f6e;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;">
                                        <div style="display:flex;align-items:center;gap:9px;flex:1;">
                                            <div
                                                style="width:32px;height:32px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                                                {{ strtoupper(substr($logbook->intern->name, 0, 1)) }}
                                            </div>
                                            <div style="min-width:0;">
                                                <p
                                                    style="color:#fff;font-weight:700;font-size:13px;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                    {{ $logbook->intern->name }}</p>
                                                <p
                                                    style="color:#93c5fd;font-size:11px;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                    {{ $logbook->intern->institution }}</p>
                                            </div>
                                        </div>
                                        @if ($isApproved)
                                            <span
                                                style="background:#ecfdf5;color:#047857;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;margin-left:8px;">
                                                <i class="fas fa-check-circle" style="margin-right:3px;"></i> Approved
                                            </span>
                                        @else
                                            <span
                                                style="background:#fff7ed;color:#c2410c;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;margin-left:8px;">
                                                <i class="fas fa-clock" style="margin-right:3px;"></i> Pending
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Card body --}}
                                    <div style="padding:14px 16px;flex:1;">
                                        <p
                                            style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 5px 0;">
                                            Aktivitas</p>
                                        <p
                                            style="font-size:13px;color:#374151;margin:0;line-height:1.45;max-height:58px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">
                                            {{ $logbook->activity }}
                                        </p>
                                        <div
                                            style="display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b;margin-top:10px;">
                                            <i class="fas fa-calendar-day"></i>
                                            <span>{{ \Carbon\Carbon::parse($logbook->date)->locale('id')->translatedFormat('d M Y') }}</span>
                                        </div>
                                        @if ($logbook->photo_path)
                                            <div
                                                style="margin-top:6px;display:flex;align-items:center;gap:6px;font-size:12px;color:#3b82f6;">
                                                <i class="fas fa-image"></i> Ada dokumentasi foto
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Card footer --}}
                                    <div
                                        style="background:#fff;border-top:1px solid #e8eeff;padding:10px 14px;display:flex;gap:8px;">
                                        <a href="{{ route('mentor.logbook.show', $logbook->id) }}"
                                            style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px;background:#dbeafe;color:#1e40af;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:background .2s;"
                                            onmouseover="this.style.background='#bfdbfe';"
                                            onmouseout="this.style.background='#dbeafe';">
                                            <i class="fas fa-eye"></i> Lihat
                                        </a>
                                        @if ($isPending)
                                            <button type="button"
                                                style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px;background:#059669;color:#fff;border-radius:9px;font-size:12px;font-weight:700;border:none;cursor:pointer;transition:background .2s;"
                                                onclick="openApprovalModal({{ $logbook->id }})"
                                                onmouseover="this.style.background='#047857';"
                                                onmouseout="this.style.background='#059669';">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center;padding:48px 24px;color:#94a3b8;">
                            <i class="fas fa-inbox"
                                style="font-size:40px;display:block;margin-bottom:12px;color:#cbd5e1;"></i>
                            <p style="font-size:14px;font-weight:500;margin:0;">Belum ada logbook yang perlu direview hari
                                ini.
                            </p>
                            <p style="font-size:12px;margin-top:4px;">Logbook akan muncul di sini ketika anak magang
                                mengupload logbook baru atau jika ada yang belum disetujui.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ── APPROVAL MODAL ── --}}
    {{-- Wrapper uses min-height trick so fixed-like overlay works in normal flow --}}
    <div id="approvalModalOverlay"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;">
        <div
            style="background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.25);max-width:480px;width:100%;max-height:90vh;overflow-y:auto;">
            <div
                style="background:#0f1f6e;padding:22px 24px;display:flex;align-items:center;justify-content:space-between;border-radius:18px 18px 0 0;">
                <div>
                    <p
                        style="color:#93c5fd;font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin:0;font-weight:600;">
                        Persetujuan Logbook</p>
                    <h3 style="color:#fff;font-size:17px;font-weight:700;margin:6px 0 0;">Setujui Logbook</h3>
                </div>
                <button onclick="closeApprovalModal()"
                    style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;"
                    onmouseover="this.style.background='rgba(255,255,255,0.25)';"
                    onmouseout="this.style.background='rgba(255,255,255,0.15)';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding:22px 24px;">
                <form id="approvalForm" style="display:flex;flex-direction:column;gap:14px;">
                    <input type="hidden" id="logbookId" name="logbook_id">
                    @csrf
                    <div>
                        <label
                            style="display:block;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
                            Catatan Approval (Opsional)
                        </label>
                        <textarea name="note" id="approvalNote"
                            style="width:100%;padding:11px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:inherit;resize:vertical;min-height:90px;outline:none;transition:border-color .2s;"
                            placeholder="Tambahkan catatan atau feedback untuk anak magang..." onfocus="this.style.borderColor='#6366f1';"
                            onblur="this.style.borderColor='#e5e7eb';"></textarea>
                    </div>
                    <div style="display:flex;gap:10px;padding-top:10px;border-top:1px solid #e5e7eb;">
                        <button type="button" onclick="closeApprovalModal()"
                            style="flex:1;padding:11px;background:#e5e7eb;color:#374151;border:none;border-radius:9px;font-weight:700;cursor:pointer;font-size:13px;"
                            onmouseover="this.style.background='#d1d5db';" onmouseout="this.style.background='#e5e7eb';">
                            Batal
                        </button>
                        <button type="submit"
                            style="flex:1;padding:11px;background:#059669;color:#fff;border:none;border-radius:9px;font-weight:700;cursor:pointer;font-size:13px;"
                            onmouseover="this.style.background='#047857';" onmouseout="this.style.background='#059669';">
                            <i class="fas fa-check" style="margin-right:5px;"></i> Setujui Logbook
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="logbookAlertOverlay"
        style="display:none;position:fixed;inset:0;background:rgba(26,26,46,0.75);z-index:10000;align-items:center;justify-content:center;padding:24px;">
        <div style="background:#fff;border-radius:20px;max-width:420px;width:100%;overflow:hidden;border:1px solid #e5e7eb;">

            {{-- Header berwarna --}}
            <div style="background:#ff0000;padding:24px 24px 36px;position:relative;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                    <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,0.18);border:1.5px solid rgba(255,255,255,0.3);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-book" style="color:#fff;font-size:22px;"></i>
                    </div>
                    <button onclick="closeLogbookAlert()"
                        style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:14px;">
                        &times;
                    </button>
                </div>
                <p style="margin:14px 0 2px;font-size:18px;font-weight:700;color:#fff;">Logbook Baru Hari Ini</p>
                <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.7);">{{ now()->isoFormat('dddd, D MMMM YYYY') }} &mdash; review diperlukan</p>
                <div style="position:absolute;bottom:-1px;left:0;right:0;height:18px;background:#fff;border-radius:20px 20px 0 0;"></div>
            </div>

            {{-- Body --}}
            <div style="padding:20px 24px 24px;">
                <div style="display:flex;gap:10px;margin-bottom:18px;">
                    <div style="flex:1;border-radius:12px;padding:12px 14px;border:1px solid #e5e7eb;background:#f9fafb;">
                        <p style="font-size:11px;color:#9ca3af;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Menunggu review</p>
                        <p style="font-size:22px;font-weight:700;color:#b74a4a;margin:0;">{{ count($todayLogbooksPending ?? []) }}</p>
                    </div>
                    <div style="flex:1;border-radius:12px;padding:12px 14px;border:1px solid #e5e7eb;background:#f9fafb;">
                        <p style="font-size:11px;color:#9ca3af;margin:0 0 4px;text-transform:uppercase;letter-spacing:.04em;">Batas waktu</p>
                        <p style="font-size:22px;font-weight:700;color:#ba1717;margin:0;">Hari ini</p>
                    </div>
                </div>

                <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#feeded;border-radius:12px;border:1px solid #eca9a9;margin-bottom:18px;">
                    <i class="fas fa-info-circle" style="color:#b74a4a;font-size:16px;flex-shrink:0;margin-top:2px;"></i>
                    <p style="font-size:13px;color:#893434;margin:0;line-height:1.6;">Mahasiswa menunggu feedback kamu. Tinjau logbook sekarang agar progres mereka tidak terhambat.</p>
                </div>

                <div style="display:flex;gap:8px;">
                    <a href="{{ route('mentor.logbook.index') }}"
                        style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;background:#ca0808;color:#fff;border-radius:12px;text-decoration:none;font-weight:700;font-size:14px;">
                        <i class="fas fa-arrow-right" style="font-size:14px;"></i>
                        Tinjau sekarang
                    </a>
                    <button type="button" onclick="closeLogbookAlert()"
                        style="padding:11px 16px;background:transparent;color:#6b7280;border:1px solid #e5e7eb;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;">
                        Nanti
                    </button>
                </div>
            </div>

        </div>
    </div>
    @push('scripts')
        <script>
            // ── Animate bars & donut on load ──
            window.addEventListener('load', function() {
                // Hero bar
                document.getElementById('hero-bar').style.width = '{{ $hadirPct }}%';

                // Stat tile bars
                document.querySelectorAll('.att-bar-fill').forEach(function(el) {
                    el.style.width = (el.dataset.w || 0) + '%';
                });

                // Donut segments
                const circ = {{ $circ }};
                ['hadir', 'izin', 'alfa'].forEach(function(id) {
                    const el = document.getElementById('d-' + id);
                    if (!el) return;
                    const dash = parseFloat(el.dataset.dash) || 0;
                    const start = parseFloat(el.dataset.start) || 0;
                    el.style.strokeDashoffset = circ - dash;
                    el.style.strokeDasharray = dash + ' ' + (circ - dash);
                    el.style.transform = 'rotate(' + ((start / circ) * 360) + 'deg)';
                    el.style.transformOrigin = '40px 40px';
                });
            });

            // ── Tab switcher ──
            function switchTab(name, btn) {
                document.querySelectorAll('.tab-content').forEach(function(el) {
                    el.classList.remove('active');
                });
                document.querySelectorAll('.tab-btn').forEach(function(el) {
                    el.classList.remove('active');
                });
                document.getElementById('tab-' + name).classList.add('active');
                btn.classList.add('active');
            }

            // ── Logbook alert popup ──
            function openLogbookAlert() {
                const el = document.getElementById('logbookAlertOverlay');
                if (!el) return;
                el.style.display = 'flex';
            }

            function closeLogbookAlert() {
                const el = document.getElementById('logbookAlertOverlay');
                if (!el) return;
                el.style.display = 'none';
            }

            window.addEventListener('load', function() {
                const todayCount = {{ count($todayLogbooksPending ?? []) }};
                if (todayCount && todayCount > 0) {
                    setTimeout(openLogbookAlert, 700);
                }
            });

            // ── Approval modal ──
            function openApprovalModal(logbookId) {
                document.getElementById('logbookId').value = logbookId;
                document.getElementById('approvalNote').value = '';
                const overlay = document.getElementById('approvalModalOverlay');
                overlay.style.display = 'flex';
            }

            function closeApprovalModal() {
                document.getElementById('approvalModalOverlay').style.display = 'none';
            }

            document.getElementById('approvalModalOverlay').addEventListener('click', function(e) {
                if (e.target === this) closeApprovalModal();
            });

            document.getElementById('approvalForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const logbookId = document.getElementById('logbookId').value;
                const note = document.getElementById('approvalNote').value;
                const form = new FormData();
                form.append('status', 'approved');
                form.append('note', note);
                form.append('_token', document.querySelector('input[name="_token"]').value);
                form.append('_method', 'PUT');

                fetch('/mentor/logbook/' + logbookId + '/approve', {
                        method: 'POST',
                        body: form
                    })
                    .then(function(res) {
                        if (res.ok) {
                            alert('Logbook berhasil disetujui!');
                            window.location.reload();
                        } else {
                            alert('Terjadi kesalahan saat menyimpan approval.');
                        }
                    })
                    .catch(function(err) {
                        console.error('Error:', err);
                        alert('Terjadi kesalahan saat mengirim data.');
                    });
            });
        </script>
    @endpush
@endsection
