{{--
    Modal generik. Dua cara membuka:
    1. Event:  $dispatch('ws-open-modal', 'nama-modal')   /  $dispatch('ws-close-modal', 'nama-modal')
    2. Binding: <x-workspace.modal name="review" x-model="reviewOpen">  (pakai x-modelable)
    State internal sengaja bernama `wsOpen` (bukan `open`): x-model dievaluasi di scope elemen ini,
    jadi nama yang sama dengan properti induk akan menutupi (shadow) properti induk tersebut.
    Konten di-teleport ke <body> supaya position:fixed tidak terjebak di elemen ber-transform.
    Tanpa `title`, beri `labelledby` = id heading di dalam slot supaya dialog punya nama (aksesibilitas).
    Fokus & keyboard (fokus masuk, Tab berputar, Esc, fokus kembali) diurus directive x-ws-dialog (core.js).
--}}
@props(['name', 'title' => null, 'maxWidth' => 'md', 'labelledby' => null])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'];
    $titleId = 'ws-modal-title-' . $name;
@endphp

<div x-data="{ wsOpen: false }"
     x-modelable="wsOpen"
     {{ $attributes }}
     x-on:ws-open-modal.window="if ($event.detail === @js($name)) wsOpen = true"
     x-on:ws-close-modal.window="if (!$event.detail || $event.detail === @js($name)) wsOpen = false">
    <template x-teleport="body">
        <div x-show="wsOpen" x-cloak class="ws-font">
            <div class="ws-overlay" x-show="wsOpen" x-transition.opacity x-on:click="wsOpen = false"></div>
            <div class="ws-modal">
                <div x-show="wsOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="ws-modal__box {{ $widths[$maxWidth] ?? $widths['md'] }}"
                     x-ws-dialog="wsOpen" x-on:ws-dialog-escape="wsOpen = false"
                     role="dialog" aria-modal="true" aria-labelledby="{{ $title ? $titleId : $labelledby }}">
                    @if ($title)
                        <div class="shrink-0 flex items-center justify-between gap-4 px-6 pt-6">
                            <h2 id="{{ $titleId }}" class="text-lg font-bold text-gray-800">{{ $title }}</h2>
                            <button type="button" class="ws-icon-btn" x-on:click="wsOpen = false" aria-label="Tutup">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                    @endif
                    <div class="ws-modal__body px-6 py-5">
                        {{ $slot }}
                    </div>
                    @isset($footer)
                        <div class="shrink-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/70">
                            {{ $footer }}
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    </template>
</div>
