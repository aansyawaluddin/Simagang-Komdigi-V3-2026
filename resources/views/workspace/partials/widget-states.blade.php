{{-- Keadaan memuat & gagal untuk widget dashboard (scope Alpine: wsDashboard). $rows = jumlah baris skeleton. --}}
<template x-if="loading">
    <div class="space-y-3 py-1" aria-busy="true" aria-label="Memuat">
        @for ($i = 0; $i < $rows; $i++)
            <div class="flex items-center gap-3 animate-pulse">
                <span class="w-16 h-6 rounded-full bg-slate-100 shrink-0"></span>
                <span class="flex-1 space-y-1.5">
                    <span class="block h-3 rounded bg-slate-100" style="width: {{ 80 - $i * 9 }}%"></span>
                    <span class="block h-2.5 rounded bg-slate-100 w-1/3"></span>
                </span>
            </div>
        @endfor
    </div>
</template>
<template x-if="!loading && error">
    <div class="ws-widget__empty">
        <i class="fas fa-plug-circle-exclamation"></i>
        <span>Ringkasan workspace gagal dimuat.</span>
        <button type="button" class="font-semibold text-indigo-600 hover:underline" x-on:click="load()">Coba lagi</button>
    </div>
</template>
