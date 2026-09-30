@extends('admin.layouts.app')

@section('title', 'Vendor Messages')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Vendor Messages')

@section('content')
{{--
    Split-pane support inbox.

    Polls the thread list every 20s and the open conversation every 8s. Polling
    rather than a socket: Reverb is not installed, and a support inbox that
    receives a handful of messages a day does not justify a daemon, a port and a
    supervisor entry. The open thread polls faster than the list because that is
    the one the reader is watching.

    Marking read happens when a thread is opened, not on a timer, so the badge
    clears only for conversations actually looked at.
--}}
<div x-data="vendorInbox()" x-init="boot()" class="flex h-[calc(100vh-9rem)] gap-4">

    {{-- LEFT: thread list --}}
    <div class="flex w-80 flex-shrink-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-3">
            <input type="text" x-model.debounce.350ms="search" @input="loadThreads()"
                   placeholder="Search vendor, business or phone…"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <label class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                <input type="checkbox" x-model="unreadOnly" @change="loadThreads()" class="rounded border-slate-300">
                Unread only
                <span x-show="meta.unread_threads" class="ml-auto rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-700"
                      x-text="meta.unread_threads + ' waiting'"></span>
            </label>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="loadingThreads">
                <div class="p-6 text-center text-sm text-slate-400">Loading…</div>
            </template>
            <template x-if="!loadingThreads && !threads.length">
                <div class="p-6 text-center text-sm text-slate-400">
                    No conversations yet — search above, or pick a vendor from
                    the list below.
                </div>
            </template>

            <template x-for="t in threads" :key="t.id">
                <button type="button" @click="openThread(t.id)"
                        class="flex w-full flex-col gap-1 border-b border-slate-100 px-3 py-3 text-left hover:bg-slate-50"
                        :class="activeId === t.id ? 'bg-orange-50' : ''">
                    <div class="flex items-start justify-between gap-2">
                        <span class="truncate text-sm font-semibold text-slate-800"
                              x-text="t.vendor.business_name || t.vendor.name || 'Vendor'"></span>
                        <span class="flex-shrink-0 text-[10px] text-slate-400" x-text="ago(t.last_message_at)"></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-slate-500">
                            <template x-if="t.last_sender_type === 'admin'">
                                <span class="text-slate-400">You: </span>
                            </template>
                            <template x-if="t.has_attachment && !t.last_message">
                                <span>📷 Photo</span>
                            </template>
                            <span x-text="t.last_message || ''"></span>
                        </span>
                        <span x-show="t.unread_count > 0"
                              class="flex-shrink-0 rounded-full bg-orange-600 px-2 py-0.5 text-[10px] font-bold text-white"
                              x-text="t.unread_count"></span>
                    </div>
                    <div class="truncate text-[11px] text-slate-400" x-text="t.vendor.phone || ''"></div>
                </button>
            </template>

            {{-- Vendors with no conversation yet. Without this the inbox is a
                 dead end on a fresh deployment: no threads means an empty list
                 and no way to start one, so support could not contact anybody
                 until that person happened to write in first. --}}
            <template x-if="directory.length">
                <div>
                    <div class="border-y border-slate-100 bg-slate-50 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Start a conversation
                    </div>
                    <template x-for="v in directory" :key="v.vendor_id">
                        <button type="button" @click="startThread(v.vendor_id)"
                                class="flex w-full items-center gap-3 border-b border-slate-100 px-3 py-3 text-left hover:bg-slate-50">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600"
                                  x-text="(v.business_name || v.name || '?').charAt(0).toUpperCase()"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800"
                                      x-text="v.business_name || v.name || 'Vendor'"></span>
                                <span class="block truncate text-[11px] text-slate-400" x-text="v.phone || 'No phone on file'"></span>
                            </span>
                            <span class="flex-shrink-0 text-[11px] font-semibold text-orange-600">Message</span>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- RIGHT: conversation --}}
    <div class="flex flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
        <template x-if="!activeId">
            <div class="flex flex-1 flex-col items-center justify-center gap-2 text-slate-400">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8m-8 4h5m-9 6l3.5-3H18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12z"/></svg>
                <p class="text-sm">Select a conversation</p>
            </div>
        </template>

        <template x-if="activeId">
            <div class="flex h-full flex-col">
                {{-- vendor header --}}
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-slate-800"
                             x-text="vendor.business_name || vendor.name || 'Vendor'"></div>
                        <div class="truncate text-xs text-slate-500">
                            <span x-text="vendor.name || ''"></span>
                            <template x-if="vendor.phone">
                                <a :href="'tel:' + vendor.phone" class="ml-2 font-semibold text-orange-600" x-text="vendor.phone"></a>
                            </template>
                        </div>
                    </div>
                    <a x-show="vendor.shipments_url" :href="vendor.shipments_url"
                       class="flex-shrink-0 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Active shipments
                    </a>
                </div>

                {{-- messages --}}
                <div x-ref="scroller" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
                    <template x-for="m in messages" :key="m.id">
                        <div class="flex flex-col" :class="m.is_from_vendor ? 'items-start' : 'items-end'">
                            <div class="max-w-[70%] rounded-2xl px-3.5 py-2.5 text-sm"
                                 :class="m.is_from_vendor ? 'bg-white text-slate-800 border border-slate-200' : 'bg-orange-600 text-white'">
                                <template x-if="m.attachment_url">
                                    <a :href="m.attachment_url" target="_blank" class="mb-1 block">
                                        <img :src="m.attachment_url" class="max-h-48 rounded-lg" alt="attachment">
                                    </a>
                                </template>
                                <span class="whitespace-pre-wrap" x-text="m.message || ''"></span>
                            </div>
                            <span class="mt-1 text-[10px] text-slate-400"
                                  x-text="m.is_from_vendor ? 'Vendor · ' + stamp(m.created_at) : 'You · ' + stamp(m.created_at)"></span>
                        </div>
                    </template>
                    <template x-if="!messages.length">
                        <div class="py-10 text-center text-sm text-slate-400">No messages in this thread yet.</div>
                    </template>
                </div>

                {{-- composer --}}
                <div class="border-t border-slate-200 p-3">
                    <div x-show="error" x-cloak class="mb-2 rounded-lg bg-rose-50 p-2 text-xs text-rose-700" x-text="error"></div>

                    <template x-if="attachmentName">
                        <div class="mb-2 flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600">
                            <span class="truncate" x-text="attachmentName"></span>
                            <button type="button" @click="clearAttachment()" class="ml-auto font-semibold text-slate-500">Remove</button>
                        </div>
                    </template>

                    <div class="flex items-end gap-2">
                        <label class="flex h-10 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200">
                            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16l5-5 4 4 3-3 6 6M3 6h18v14H3z"/></svg>
                            <input type="file" class="hidden" accept="image/*,application/pdf" @change="pickAttachment($event)">
                        </label>
                        <textarea x-model="reply" rows="1" @keydown.enter.prevent="send()"
                                  placeholder="Type your reply… (Enter to send)"
                                  class="max-h-32 min-h-[40px] flex-1 resize-y rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <button type="button" @click="send()" :disabled="sending || (!reply.trim() && !attachment)"
                                class="h-10 flex-shrink-0 rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white disabled:opacity-40"
                                x-text="sending ? 'Sending…' : 'Send Reply'"></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    /* Re-read the token and retry once on 419, so a session rollover under a
       long-open inbox does not silently discard a typed reply. */
    const send = async (url, method, body) => {
        const isForm = body instanceof FormData;

        /* Content-Type is decided INSIDE the builder. Setting it afterwards
           mutates a throwaway object, so a JSON body would go out unlabelled —
           PHP would not populate the input bag and every reply would fail
           validation on a message that was plainly typed. */
        const opts = () => ({
            method,
            headers: {
                'Accept': 'application/json',
                ...(isForm || !body ? {} : { 'Content-Type': 'application/json' }),
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            /* Never set Content-Type for FormData: the browser must add the
               multipart boundary itself. */
            body: isForm ? body : (body ? JSON.stringify(body) : undefined),
        });

        let res = await fetch(url, opts());
        if (res.status === 419) res = await fetch(url, opts());

        const json = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, json };
    };

    Alpine.data('vendorInbox', () => ({
        threads: [], directory: [], meta: {}, loadingThreads: true,
        search: '', unreadOnly: false,
        activeId: null, vendor: {}, messages: [],
        reply: '', attachment: null, attachmentName: '', sending: false, error: '',
        listTimer: null, threadTimer: null,

        boot() {
            this.loadThreads();
            this.listTimer = setInterval(() => this.loadThreads(true), 20000);

            return () => {
                clearInterval(this.listTimer);
                clearInterval(this.threadTimer);
            };
        },

        stamp(v) {
            if (!v) return '';
            const d = new Date(v);
            return Number.isNaN(d.getTime()) ? '' : d.toLocaleString();
        },

        ago(v) {
            if (!v) return '';
            const mins = Math.floor((Date.now() - new Date(v).getTime()) / 60000);
            if (Number.isNaN(mins)) return '';
            if (mins < 1) return 'now';
            if (mins < 60) return mins + 'm';
            if (mins < 1440) return Math.floor(mins / 60) + 'h';
            return Math.floor(mins / 1440) + 'd';
        },

        async loadThreads(silent = false) {
            if (!silent) this.loadingThreads = true;
            const params = new URLSearchParams();
            if (this.search.trim()) params.set('search', this.search.trim());
            if (this.unreadOnly) params.set('unread_only', '1');

            const { json } = await send(@json($endpoints['list']) + '?' + params.toString(), 'GET');
            if (json.data) {
                this.threads = json.data;
                this.meta = json.meta || {};
            }
            this.directory = json.directory || [];
            this.loadingThreads = false;
        },

        /* Starts a conversation with a vendor who has never written in. The
           thread is created server-side on demand, so no empty thread rows pile
           up for vendors nobody has contacted. */
        async startThread(vendorId) {
            const { ok, json } = await send(@json($endpoints['open']), 'POST', { vendor_id: vendorId });
            if (!ok || !json.data) return;

            await this.loadThreads(true);
            await this.openThread(json.data.thread_id);
        },

        async openThread(id) {
            this.activeId = id;
            this.error = '';
            this.clearAttachment();
            clearInterval(this.threadTimer);

            await this.loadThread();
            this.threadTimer = setInterval(() => this.loadThread(true), 8000);

            // Mark read on open, not on a timer: the badge should clear for
            // conversations someone actually looked at.
            const { json } = await send(@json($endpoints['read']) + '/' + id + '/read', 'PATCH');
            if (json.data) {
                const t = this.threads.find((x) => x.id === id);
                if (t) t.unread_count = 0;
                this.loadThreads(true);
            }
        },

        async loadThread(silent = false) {
            if (!this.activeId) return;

            const { json } = await send(@json($endpoints['show']) + '/' + this.activeId, 'GET');
            if (!json.data) return;

            this.vendor = json.data.vendor || {};

            const next = json.data.messages || [];
            const changed = next.length !== this.messages.length
                || (next.length && this.messages.length && next[next.length - 1].id !== this.messages[this.messages.length - 1].id);

            if (changed || !silent) {
                this.messages = next;
                this.$nextTick(() => {
                    const el = this.$refs.scroller;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        },

        pickAttachment(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.attachment = file;
            this.attachmentName = file.name;
        },

        clearAttachment() {
            this.attachment = null;
            this.attachmentName = '';
        },

        async send() {
            const text = this.reply.trim();
            if (!text && !this.attachment) return;

            this.sending = true;
            this.error = '';

            let body;
            if (this.attachment) {
                body = new FormData();
                if (text) body.append('message', text);
                body.append('attachment', this.attachment);
            } else {
                body = { message: text };
            }

            const { ok, json } = await send(@json($endpoints['show']) + '/' + this.activeId + '/messages', 'POST', body);
            this.sending = false;

            if (!ok) {
                const errs = json.errors || {};
                this.error = errs.message?.[0] || errs.attachment?.[0] || json.message || 'Could not send.';
                return;
            }

            this.reply = '';
            this.clearAttachment();
            await this.loadThread();
            this.loadThreads(true);
        },
    }));
});
</script>
@endpush
