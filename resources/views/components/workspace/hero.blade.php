{{-- Slot default = area aksi di kanan (tombol). Slot "meta" = baris info kecil di bawah subjudul. --}}
@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div {{ $attributes->class(['ws-hero shadow-xl']) }}>
    <div class="px-5 py-6 sm:px-6 sm:py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200/80 mb-2">{{ $eyebrow }}</p>
                @endif
                <h1 class="text-xl sm:text-3xl font-bold leading-tight text-white mb-1 break-words">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-sm sm:text-base text-blue-200">{{ $subtitle }}</p>
                @endif
                @isset($meta)
                    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs sm:text-sm text-blue-100">{{ $meta }}</div>
                @endisset
            </div>
            @if ($slot->isNotEmpty())
                <div class="ws-hero__actions flex flex-wrap items-center gap-2 shrink-0">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</div>
