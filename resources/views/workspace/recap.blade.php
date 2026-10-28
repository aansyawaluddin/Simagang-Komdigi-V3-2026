{{--
    Rekap tugas siap cetak (PRD Fase 3). Variabel:
      $recap  { intern: { id, name }, period: { start, end }, summary: { done, active, on_time, avg_score, max_score, min_score },
               tasks: [{ id, code, title, workspace: { id, name }, due_at, completed_at, score, on_time, url }] }
    "Cetak / Simpan PDF" memakai dialog cetak browser; gaya cetak menyembunyikan navigasi aplikasi.
--}}
@extends('workspace.layout')

@section('title', 'Rekap Tugas · ' . $recap['intern']['name'] . ' - Sistem Magang')

@push('styles')
    <style>
        @media print {
            @page { margin: 16mm; }
            body { background: #fff !important; }
            #sidebar, .ws-topbar, header.lg\:hidden, .ws-no-print, .ws-toasts { display: none !important; }
            .h-screen { height: auto !important; }
            .overflow-hidden, main { overflow: visible !important; }
            .ws-page { background: #fff !important; }
            .ws-recap-doc { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
            .ws-recap-doc table { font-size: 11px; }
        }
    </style>
@endpush

@section('workspace-content')
    @php
        $s = $recap['summary'];
        $fmt = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->locale('id')->translatedFormat('j M Y') : '–';
        $onTimePct = $s['done'] ? round($s['on_time'] / $s['done'] * 100) : null;
    @endphp

    <div class="max-w-5xl mx-auto">
        <div class="ws-no-print flex flex-wrap items-center gap-2 mb-4">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 py-1">
                <i class="fas fa-arrow-left text-xs"></i> Kembali
            </a>
            <button type="button" class="ws-btn ws-btn--primary ws-btn--sm ml-auto" x-data x-on:click="window.print()">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
        </div>

        <article class="ws-panel ws-recap-doc">
            <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-5 border-b border-slate-200">
                <div>
                    <p class="ws-section-label mb-1">Lampiran laporan akhir magang</p>
                    <h1 class="text-2xl font-bold text-gray-900">Rekap Tugas Workspace</h1>
                    <p class="text-sm text-slate-600 mt-1">{{ $recap['intern']['name'] }} · periode {{ $fmt($recap['period']['start']) }} – {{ $fmt($recap['period']['end']) }}</p>
                </div>
                <p class="text-xs text-slate-500">Dicetak {{ now()->locale('id')->translatedFormat('j F Y, H.i') }}</p>
            </header>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-5">
                @foreach ([['Tugas selesai', $s['done']], ['Tepat waktu', $onTimePct === null ? '–' : $onTimePct . '%'], ['Rata-rata nilai', $s['avg_score'] ?? '–'], ['Rentang nilai', $s['min_score'] !== null ? $s['min_score'] . '–' . $s['max_score'] : '–']] as [$label, $value])
                    <div class="ws-tile">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="text-2xl font-extrabold text-gray-900 mt-1">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if (count($recap['tasks']))
                <div class="overflow-x-auto">
                    <table class="ws-recap">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Tugas</th>
                                <th scope="col">Workspace</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Selesai</th>
                                <th scope="col">Ketepatan</th>
                                <th scope="col">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recap['tasks'] as $i => $t)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <th scope="row" class="!whitespace-normal">
                                        <span class="ws-mono text-slate-500 mr-1">{{ $t['code'] }}</span>
                                        <span class="font-semibold text-gray-800">{{ $t['title'] }}</span>
                                    </th>
                                    <td>{{ $t['workspace']['name'] }}</td>
                                    <td>{{ $fmt($t['due_at']) }}</td>
                                    <td>{{ $fmt($t['completed_at']) }}</td>
                                    <td>{!! $t['on_time'] ? '<span class="text-emerald-700 font-semibold">Tepat waktu</span>' : '<span class="text-red-700 font-semibold">Terlambat</span>' !!}</td>
                                    <td class="font-bold">{{ $t['score'] ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-workspace.empty-state icon="fa-inbox" title="Belum ada tugas selesai" description="Tugas yang sudah disetujui mentor akan tercantum di rekap ini." />
            @endif

            <p class="mt-5 text-xs text-slate-500">Nilai diberikan mentor saat menyetujui tugas (skala 0–100). Tugas yang masih berjalan ({{ $s['active'] }}) tidak termasuk dalam rekap.</p>
        </article>
    </div>
@endsection
