{{--
    Satu pesan channel (scope Alpine: wsChannels; variabel x-for `row` = { msg, compact }).
    $inThread: true di panel thread — tanpa ringkasan balasan dan tanpa "Balas di thread".
--}}
<article class="ws-msg" :data-message-id="row.msg.id"
         :class="{
             'ws-msg--compact': row.compact,
             'ws-msg--mention': mentionsMe(row.msg),
             'ws-msg--pinned': row.msg.is_pinned && !row.msg.is_deleted,
             'ws-msg--flash': highlightId === row.msg.id,
             'ws-msg--pending': row.msg._status === 'sending',
             'ws-msg--editing': editingId === row.msg.id,
         }">
    <div class="ws-msg__gutter">
        <template x-if="!row.compact && row.msg.user">
            <x-workspace.avatar-js user="row.msg.user" size="md" />
        </template>
        <template x-if="!row.compact && !row.msg.user">
            <span class="ws-avatar ws-avatar--md ws-avatar--system" title="Sistem"><i class="fas fa-robot"></i></span>
        </template>
        <template x-if="row.compact">
            <time class="ws-msg__hover-time" :datetime="row.msg.created_at" :title="fullTime(row.msg.created_at)" x-text="clock(row.msg.created_at)"></time>
        </template>
    </div>

    <div class="ws-msg__main">
        <p class="ws-msg__pin" x-show="row.msg.is_pinned && !row.msg.is_deleted"><i class="fas fa-thumbtack"></i> Disematkan</p>
        <header class="ws-msg__head" x-show="!row.compact">
            <span class="font-semibold text-gray-800 truncate" x-text="row.msg.user ? row.msg.user.name : 'Simagang'"></span>
            <span class="ws-msg__role" x-show="roleLabel(row.msg.user)" x-text="roleLabel(row.msg.user)"
                  :class="'ws-msg__role--' + (row.msg.user ? row.msg.user.role : 'system')"></span>
            <time class="text-xs ws-text-faint shrink-0" :datetime="row.msg.created_at" :title="fullTime(row.msg.created_at)" x-text="clock(row.msg.created_at)"></time>
        </header>

        <template x-if="row.msg.is_deleted">
            <p class="ws-msg__deleted"><i class="fas fa-ban"></i> Pesan ini telah dihapus</p>
        </template>

        {{-- body_html dirender & disanitasi server. Tanda "(diedit)" lewat CSS ::after. --}}
        <template x-if="!row.msg.is_deleted && editingId !== row.msg.id && row.msg.body_html">
            <div class="ws-prose ws-msg__body" :class="row.msg.edited_at && 'ws-msg__body--edited'"
                 :title="row.msg.edited_at ? 'Diedit ' + fullTime(row.msg.edited_at) : null"
                 x-html="row.msg.body_html"></div>
        </template>

        {{-- Edit pesan --}}
        <template x-if="editingId === row.msg.id">
            <div class="mt-1">
                <textarea data-edit-input class="ws-input !text-sm resize-none" rows="1" x-model="editBody" maxlength="4000"
                          aria-label="Edit pesan"
                          x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                          x-on:keydown="onEditKey($event, row.msg)"></textarea>
                <div class="flex items-center gap-2 mt-2">
                    <button type="button" class="ws-btn ws-btn--primary ws-btn--sm" x-on:click="saveEdit(row.msg)" :disabled="savingEdit">
                        <i class="fas" :class="savingEdit ? 'fa-spinner fa-spin' : 'fa-check'"></i> Simpan
                    </button>
                    <button type="button" class="ws-btn ws-btn--ghost ws-btn--sm" x-on:click="cancelEdit()">Batal</button>
                    <span class="hidden sm:inline text-[11px] ws-text-faint ml-1">Enter simpan · Esc batal</span>
                </div>
            </div>
        </template>

        {{-- Lampiran --}}
        <template x-if="row.msg.attachments && row.msg.attachments.length">
            <div class="ws-msg__files">
                <template x-for="file in row.msg.attachments" :key="file.id">
                    <a :href="file.download_url" target="_blank" rel="noopener"
                       :class="file.is_image && file.thumbnail_url ? 'ws-msg__image' : 'ws-msg__file'"
                       :title="file.original_name"
                       x-on:click="file.download_url === '#' && $event.preventDefault()">
                        <template x-if="file.is_image && file.thumbnail_url">
                            <img :src="file.thumbnail_url" :alt="file.original_name" loading="lazy">
                        </template>
                        <template x-if="!(file.is_image && file.thumbnail_url)">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas text-lg" :class="fileIcon(file).icon" :style="{ color: fileIcon(file).color }"></i>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-800" x-text="file.original_name"></span>
                                    <span class="block text-xs ws-text-faint" x-text="fileSize(file.size)"></span>
                                </span>
                            </span>
                        </template>
                    </a>
                </template>
            </div>
        </template>

        {{-- Reaksi --}}
        <div class="ws-reacts" x-show="!row.msg.is_deleted && (row.msg.reactions || []).length">
            <template x-for="r in (row.msg.reactions || [])" :key="r.emoji">
                <button type="button" class="ws-react" :class="r.me && 'ws-react--me'" :aria-pressed="r.me.toString()"
                        :aria-label="reactLabel(r)" :title="reactLabel(r)" :disabled="!canReact" x-on:click="react(row.msg, r.emoji)">
                    <span x-text="r.emoji" aria-hidden="true"></span><span class="ws-react__n" x-text="r.count"></span>
                </button>
            </template>
        </div>

        @unless ($inThread)
            {{-- Ringkasan thread --}}
            <button type="button" class="ws-msg__thread" x-show="row.msg.reply_count > 0 && !row.msg.parent_id" x-on:click="openThread(row.msg)">
                <i class="far fa-comments"></i>
                <span x-text="row.msg.reply_count + ' balasan'"></span>
                <span class="ws-text-faint font-normal" x-show="row.msg.last_reply_at" x-text="'· terakhir ' + (row.msg.last_reply_at ? relative(row.msg.last_reply_at) : '')"></span>
            </button>
        @endunless

        {{-- Status kirim --}}
        <p class="text-[11px] ws-text-faint mt-0.5" x-show="row.msg._status === 'sending'">
            <i class="fas fa-circle-notch fa-spin mr-1"></i>Mengirim…
        </p>
        <p class="ws-msg__failed" x-show="row.msg._status === 'failed'" role="alert">
            <i class="fas fa-circle-exclamation"></i>
            <span x-text="'Gagal terkirim' + (row.msg._error ? ': ' + row.msg._error : '')"></span>
            <button type="button" class="font-semibold underline" x-on:click="retry(row.msg)">Coba lagi</button>
            <span aria-hidden="true">·</span>
            <button type="button" class="font-semibold underline" x-on:click="discard(row.msg)">Hapus</button>
        </p>
    </div>

    {{-- Aksi pesan --}}
    <div class="ws-msg__actions" x-show="!row.msg.is_deleted && !row.msg._status && editingId !== row.msg.id"
         x-data="{ menu: false, pick: false, up: false }" x-on:click.outside="menu = false; pick = false"
         x-on:keydown.escape="if (menu || pick) { menu = false; pick = false; $event.preventDefault(); $el.querySelector('button').focus(); }">
        {{-- Tambah reaksi --}}
        <button type="button" class="ws-icon-btn ws-icon-btn--sm" x-show="canReact" :aria-expanded="pick.toString()" aria-label="Tambah reaksi" title="Tambah reaksi"
                x-on:click="up = $el.getBoundingClientRect().bottom > $el.closest('[data-scroller],[data-thread-scroller]').getBoundingClientRect().bottom - 120; menu = false; pick = !pick">
            <i class="far fa-face-smile"></i>
        </button>
        <div class="ws-menu ws-react-pick" :class="up && 'ws-menu--up'" x-show="pick" x-cloak x-transition.opacity role="group" aria-label="Pilih reaksi">
            <template x-for="e in reactions" :key="e">
                <button type="button" class="ws-react-pick__btn" :aria-label="'Reaksi ' + e" x-on:click="pick = false; react(row.msg, e)" x-text="e"></button>
            </template>
        </div>
        <button type="button" class="ws-icon-btn ws-icon-btn--sm" :aria-expanded="menu.toString()" aria-label="Aksi pesan"
                x-on:click="up = $el.getBoundingClientRect().bottom > $el.closest('[data-scroller],[data-thread-scroller]').getBoundingClientRect().bottom - 200; pick = false; menu = !menu">
            <i class="fas fa-ellipsis"></i>
        </button>
        <div class="ws-menu" :class="up && 'ws-menu--up'" x-show="menu" x-cloak x-transition.opacity>
            @unless ($inThread)
                <button type="button" class="ws-menu__item" x-show="!row.msg.parent_id" x-on:click="menu = false; openThread(row.msg)">
                    <i class="far fa-comments w-4"></i><span x-text="row.msg.reply_count ? 'Lihat thread' : 'Balas di thread'"></span>
                </button>
            @endunless
            <button type="button" class="ws-menu__item" x-show="row.msg.body" x-on:click="menu = false; copyText(row.msg)"><i class="far fa-copy w-4"></i>Salin teks</button>
            <template x-if="row.msg.can.pin && !row.msg.parent_id">
                <button type="button" class="ws-menu__item" x-on:click="menu = false; togglePin(row.msg)">
                    <i class="fas fa-thumbtack w-4"></i><span x-text="row.msg.is_pinned ? 'Lepas sematan' : 'Sematkan di channel'"></span>
                </button>
            </template>
            <template x-if="row.msg.can.edit">
                <button type="button" class="ws-menu__item" x-on:click="menu = false; startEdit(row.msg)"><i class="fas fa-pen w-4"></i>Edit pesan</button>
            </template>
            <template x-if="row.msg.can.delete">
                <button type="button" class="ws-menu__item ws-menu__item--danger" x-on:click="menu = false; askDelete(row.msg)">
                    <i class="fas fa-trash w-4"></i><span x-text="row.msg.user && row.msg.user.id === WS.config.user.id ? 'Hapus pesan' : 'Hapus (moderasi)'"></span>
                </button>
            </template>
        </div>
    </div>
</article>
