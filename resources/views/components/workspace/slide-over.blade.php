{{--
    Panel geser dari kanan (dipakai detail tugas di board/list). Di HP menjadi layar penuh.
    Cara membuka sama dengan modal: $dispatch('ws-open-modal', 'nama') atau x-model (x-modelable).
    State internal bernama `wsOpen` agar tidak menutupi properti induk saat di-bind dengan x-model.
    Slot: "header" (opsional, menggantikan judul), default (isi, scroll sendiri), "footer" (opsional).
    Fokus & keyboard diurus directive x-ws-dialog (core.js), sama dengan modal.
--}}
@props(['name', 'title' => null])

@php($titleId = 'ws-slideover-title-' . $name)

<div x-data="{ wsOpen: false }"
     x-modelable="wsOpen"
     {{ $attributes }}
     x-on:ws-open-modal.window="if ($event.detail === @js($name)) wsOpen = true"
     x-on:ws-close-modal.window="if (!$event.detail || $event.detail === @js($name)) wsOpen = false">
    <template x-teleport="body">
        <div x-show="wsOpen" x-cloak class="ws-font">
            <div class="ws-overlay" x-show="wsOpen" x-transition.opacity x-on:click="wsOpen = false"></div>
            <div x-show="wsOpen"
                   x-transition:enter="transform transition ease-out duration-300"
                   x-transition:enter-start="translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transform transition ease-in duration-200"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="translate-x-full"
                   class="ws-slideover"
                   x-ws-dialog="wsOpen" x-on:ws-dialog-escape="wsOpen = false"
                   role="dialog" aria-modal="true" @if ($title) aria-labelledby="{{ $titleId }}" @endif>
                <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-100">
                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @elseif ($title)
                            <h2 id="{{ $titleId }}" class="text-lg font-bold text-gray-800 truncate">{{ $title }}</h2>
                        @endisset
                    </div>
                    <button type="button" class="ws-icon-btn shrink-0" x-on:click="wsOpen = false" aria-label="Tutup panel">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-5">
                    {{ $slot }}
                </div>
                @isset($footer)
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-wrap justify-end gap-2">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </template>
</div>
