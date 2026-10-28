{{--
    Komposer pesan (wsComposer). $mode: 'channel' | 'thread'. $members: kandidat @mention.
    $show: ekspresi Alpine kapan komposer tampil.
--}}
<div class="ws-composer" x-show="{{ $show }}" x-data="wsComposer(@js(['members' => $members, 'mode' => $mode]))"
     @if ($mode === 'thread') data-thread-composer @endif>
    <div class="ws-composer__box" :class="dragging && 'ws-composer__box--drag'"
         x-on:dragover.prevent="dragging = true" x-on:dragleave.self="dragging = false" x-on:drop.prevent="onDrop($event)">

        {{-- Saran @mention / #tugas --}}
        <div class="ws-suggest" id="ws-suggest-{{ $mode }}" x-show="suggest.open" x-cloak role="listbox"
             :aria-label="suggest.type === '@' ? 'Sebut anggota' : 'Rujuk tugas'">
            <p class="ws-menu__label" x-text="suggest.type === '@' ? 'Sebut anggota' : 'Rujuk tugas'"></p>
            <template x-for="(item, i) in suggest.items" :key="item.key">
                <button type="button" class="ws-suggest__item" role="option" :id="'ws-suggest-{{ $mode }}-' + i" tabindex="-1"
                        :class="i === suggest.index && 'ws-suggest__item--active'" :aria-selected="(i === suggest.index).toString()"
                        x-on:mousedown.prevent="choose(item)" x-on:mouseenter="suggest.index = i">
                    <template x-if="item.user"><x-workspace.avatar-js user="item.user" size="xs" /></template>
                    <template x-if="!item.user">
                        <span class="ws-suggest__icon"><i class="fas" :class="item.task ? 'fa-clipboard-list' : 'fa-users'"></i></span>
                    </template>
                    <span class="font-semibold text-sm text-gray-800 shrink-0" :class="item.task && 'ws-mono'" x-text="(item.task ? '' : '@') + item.label"></span>
                    <span class="text-xs ws-text-faint truncate" x-text="item.hint"></span>
                </button>
            </template>
        </div>

        {{-- Lampiran yang akan dikirim --}}
        <ul class="ws-composer__files" x-show="files.length" x-cloak>
            <template x-for="f in files" :key="f.key">
                <li class="ws-composer__file" :class="f.status === 'error' && 'ws-composer__file--error'">
                    <i class="fas" :class="f.status === 'uploading' ? 'fa-circle-notch fa-spin text-indigo-500' : f.status === 'error' ? 'fa-circle-exclamation text-red-500' : 'fa-paperclip ws-text-faint'"></i>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-medium text-gray-800" x-text="f.name"></span>
                        <span class="block text-[11px]" :class="f.status === 'error' ? 'text-red-600' : 'ws-text-faint'"
                              x-text="f.status === 'error' ? f.error : f.status === 'uploading' ? f.progress + '%' : fileSize(f.size)"></span>
                    </span>
                    <button type="button" class="ws-icon-btn ws-icon-btn--sm !w-6 !h-6 shrink-0" x-on:click="removeFile(f.key)" :aria-label="'Hapus ' + f.name">
                        <i class="fas fa-xmark"></i>
                    </button>
                </li>
            </template>
        </ul>

        <textarea x-ref="input" data-composer-input rows="1" class="ws-composer__input" x-model="body" maxlength="4000"
                  :placeholder="placeholder" aria-label="{{ $mode === 'thread' ? 'Tulis balasan' : 'Tulis pesan' }}"
                  aria-controls="ws-suggest-{{ $mode }}" aria-autocomplete="list"
                  :aria-activedescendant="suggest.open ? 'ws-suggest-{{ $mode }}-' + suggest.index : null"
                  x-on:input="autosize(); detect()" x-on:keydown="onKeydown($event)" x-on:click="detect()"
                  x-on:blur="close()" x-on:paste="onPaste($event)"></textarea>

        <div class="ws-composer__bar">
            <input type="file" class="sr-only" multiple x-ref="file" :accept="accept" x-on:change="onFiles($event)" tabindex="-1" aria-hidden="true">
            <button type="button" class="ws-icon-btn ws-icon-btn--sm" x-on:click="pick()" title="Lampirkan file (maks. 10 MB)" aria-label="Lampirkan file">
                <i class="fas fa-paperclip"></i>
            </button>
            <button type="button" class="ws-icon-btn ws-icon-btn--sm" x-on:click="insertMention()" title="Sebut anggota" aria-label="Sebut anggota">
                <i class="fas fa-at"></i>
            </button>
            <div class="relative" x-on:click.outside="emojiOpen = false"
                 x-on:keydown.escape="if (emojiOpen) { emojiOpen = false; $event.preventDefault(); $el.querySelector('button').focus(); }">
                <button type="button" class="ws-icon-btn ws-icon-btn--sm" :aria-expanded="emojiOpen.toString()" x-on:click="emojiOpen = !emojiOpen"
                        title="Sisipkan emoji" aria-label="Sisipkan emoji">
                    <i class="far fa-face-smile"></i>
                </button>
                <div class="ws-emoji" x-show="emojiOpen" x-cloak x-transition.opacity role="dialog" aria-label="Pilih emoji">
                    <template x-for="g in emojiGroups" :key="g[0]">
                        <div>
                            <p class="ws-menu__label" x-text="g[0]"></p>
                            <div class="ws-emoji__grid">
                                <template x-for="e in g[1]" :key="e">
                                    <button type="button" class="ws-emoji__btn" :aria-label="'Emoji ' + e" x-on:click="insertEmoji(e)" x-text="e"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            @if ($mode === 'channel')
                <span class="hidden lg:inline text-[11px] ws-text-faint ml-1 truncate" x-show="!touch && !panel">
                    <kbd class="ws-kbd">Enter</kbd> kirim · <kbd class="ws-kbd">Shift</kbd>+<kbd class="ws-kbd">Enter</kbd> baris baru · <code>**tebal**</code> <code>`kode`</code>
                </span>
            @endif
            <button type="button" class="ws-btn ws-btn--primary ws-btn--sm ml-auto" :disabled="!canSend" x-on:click="submit()"
                    aria-label="{{ $mode === 'thread' ? 'Kirim balasan' : 'Kirim pesan' }}">
                <i class="fas fa-paper-plane"></i><span class="hidden sm:inline">{{ $mode === 'thread' ? 'Balas' : 'Kirim' }}</span>
            </button>
        </div>
    </div>
</div>
