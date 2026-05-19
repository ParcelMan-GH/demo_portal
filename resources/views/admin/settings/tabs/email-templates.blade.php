<div x-data="emailTemplatesManager(@js($tabData))" @@email-template-create.window="openCreate()" class="space-y-5">
    <span class="sr-only">Customer Email Templates</span>

    <div x-show="toast.message" x-cloak
         x-transition
         class="fixed right-5 top-24 z-[120] max-w-md rounded-2xl border bg-white px-4 py-3 shadow-2xl shadow-slate-900/15"
         :class="toast.type === 'success' ? 'border-emerald-200' : 'border-rose-200'">
        <p class="text-sm font-extrabold" :class="toast.type === 'success' ? 'text-emerald-700' : 'text-rose-700'" x-text="toast.title"></p>
        <p class="mt-0.5 text-sm font-semibold text-slate-600" x-text="toast.message"></p>
    </div>

    <div class="border-b border-slate-100 pb-4">
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="w-full xl:max-w-md">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model.debounce.150ms="filters.search" @@input="page = 1" placeholder="Search template, key, subject..."
                           class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                <button type="button" @@click="showFilters = !showFilters"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                </button>
            </div>
        </div>

        <div x-show="showFilters" x-transition class="mb-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4" style="display:none">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Category</label>
                    <select x-model="filters.category" @@change="page = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All categories</option>
                        <template x-for="category in categories" :key="category">
                            <option :value="category" x-text="category"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient</label>
                    <select x-model="filters.recipient" @@change="page = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All recipients</option>
                        <template x-for="type in recipientTypes" :key="type">
                            <option :value="type" x-text="label(type)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                    <select x-model="filters.status" @@change="page = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All statuses</option>
                        <option value="enabled">Enabled</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-[1080px] w-full divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Template</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Category</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                        <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Updated</th>
                        <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    <template x-if="filteredTemplates.length === 0">
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No email templates match the current filters</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="template in paginatedTemplates" :key="template.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" x-text="template.name"></p>
                                    <p class="mt-0.5 font-mono text-[11px] font-bold text-slate-500" x-text="template.key"></p>
                                </div>
                            </td>
                            <td class="max-w-[320px] px-4 py-3 text-sm font-semibold text-slate-600">
                                <p class="truncate" x-text="template.subject || '-'"></p>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-600" x-text="template.category || '-'"></td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-600" x-text="template.recipient_label || '-'"></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold"
                                      :class="template.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                      x-text="template.is_enabled ? 'Enabled' : 'Disabled'"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500" x-text="template.updated_at || '-'"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @@click="preview(template)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Preview</button>
                                    <button type="button" @@click="edit(template)" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 transition hover:bg-orange-100">Edit</button>
                                    <button type="button" @@click="toggle(template)" class="rounded-lg px-3 py-2 text-xs font-bold transition"
                                            :class="template.is_enabled ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-emerald-600 text-white hover:bg-emerald-700'"
                                            x-text="template.is_enabled ? 'Disable' : 'Enable'"></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 bg-slate-50/40 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-600">Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="filteredTemplates.length"></span></p>
                <div class="flex items-center gap-3">
                    <select x-model.number="perPage" @@change="page = 1" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs font-bold text-slate-700">
                        <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option>
                    </select>
                    <button type="button" @@click="prevPage()" :disabled="page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Prev</button>
                    <span class="text-sm font-bold text-slate-600">Page <span x-text="page"></span> of <span x-text="totalPages"></span></span>
                    <button type="button" @@click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="createModal.open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div @click.outside="createModal.open = false" class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-600">New Template</p>
                    <h3 class="text-2xl font-black text-slate-950">Create Email Template</h3>
                </div>
                <button type="button" @click="createModal.open = false" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="max-h-[calc(92vh-11rem)] space-y-4 overflow-y-auto p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Template Name</span>
                        <input x-model="createModal.form.name" @@input="syncCreateKey()" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Vendor Follow Up">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Template Key</span>
                        <input x-model="createModal.form.key" @@input="createModal.keyTouched = true" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 font-mono text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="vendor_follow_up">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Category</span>
                        <input x-model="createModal.form.category" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Vendor">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Recipient Type</span>
                        <select x-model="createModal.form.recipient_type" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="vendor">Vendor</option>
                            <option value="recipient">Recipient</option>
                            <option value="driver">Driver</option>
                            <option value="recipient_vendor">Recipient / Vendor</option>
                            <option value="platform_user">Platform User</option>
                        </select>
                    </label>
                </div>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Subject</span>
                    <input x-model="createModal.form.subject" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Hello @{{ vendor_name }}">
                </label>
                <div>
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">HTML Body</span>
                    <div class="overflow-hidden rounded-2xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
                            <button type="button" @click="editorCommand('createHtmlEditor', 'bold')" class="rounded-lg px-3 py-2 text-sm font-black text-slate-700 hover:bg-white">B</button>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'italic')" class="rounded-lg px-3 py-2 text-sm font-black italic text-slate-700 hover:bg-white">I</button>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'underline')" class="rounded-lg px-3 py-2 text-sm font-black underline text-slate-700 hover:bg-white">U</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'formatBlock', 'h2')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">H2</button>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'formatBlock', 'p')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">P</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'insertUnorderedList')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">List</button>
                            <button type="button" @click="editorCommand('createHtmlEditor', 'insertOrderedList')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">1. List</button>
                            <button type="button" @click="editorLink('createHtmlEditor')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">Link</button>
                            <button type="button" @click="clearEditorFormat('createHtmlEditor')" class="ml-auto rounded-lg px-3 py-2 text-xs font-black text-slate-500 hover:bg-white">Clear</button>
                        </div>
                        <div x-ref="createHtmlEditor"
                             contenteditable="true"
                             @input="createModal.form.body_html = $event.currentTarget.innerHTML"
                             class="min-h-56 max-h-80 overflow-y-auto px-4 py-3 text-sm font-semibold leading-7 text-slate-800 outline-none empty:before:text-slate-400 empty:before:content-['Write_the_email_body...']"></div>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Plain Text Fallback</span>
                    <textarea x-model="createModal.form.body_text" rows="5" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Hello @{{ vendor_name }}"></textarea>
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Variables</span>
                    <input x-model="createModal.variablesText" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 font-mono text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="vendor_name, shipment_number, warehouse_name">
                    <span class="mt-2 block text-xs font-semibold text-slate-500">Comma-separated variable names supported by this custom template.</span>
                </label>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-900">Enabled</p>
                            <p class="text-sm font-semibold text-slate-500">Disabled templates are saved but skipped by sending workflows.</p>
                        </div>
                        <button type="button" @click="createModal.form.is_enabled = !createModal.form.is_enabled"
                                class="relative h-9 w-16 rounded-full transition"
                                :class="createModal.form.is_enabled ? 'bg-emerald-500' : 'bg-slate-300'">
                            <span class="absolute top-1 h-7 w-7 rounded-full bg-white shadow transition" :class="createModal.form.is_enabled ? 'left-8' : 'left-1'"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" @click="createModal.open = false" class="rounded-2xl border-2 border-slate-200 bg-white px-6 py-3 text-sm font-black text-slate-700">Cancel</button>
                <button type="button" @click="create()" :disabled="saving" class="rounded-2xl bg-orange-600 px-7 py-3 text-sm font-black text-white shadow-lg shadow-orange-200 disabled:opacity-50" x-text="saving ? 'Creating...' : 'Create Template'"></button>
            </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="editModal.open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div @click.outside="editModal.open = false" class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-600">Edit Template</p>
                    <h3 class="text-2xl font-black text-slate-950" x-text="editModal.form.name"></h3>
                </div>
                <button type="button" @click="editModal.open = false" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="max-h-[calc(92vh-11rem)] space-y-4 overflow-y-auto p-6">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Subject</span>
                    <input x-model="editModal.form.subject" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </label>
                <div>
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">HTML Body</span>
                    <div class="overflow-hidden rounded-2xl border-2 border-slate-200 bg-white focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
                            <button type="button" @click="editorCommand('editHtmlEditor', 'bold')" class="rounded-lg px-3 py-2 text-sm font-black text-slate-700 hover:bg-white">B</button>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'italic')" class="rounded-lg px-3 py-2 text-sm font-black italic text-slate-700 hover:bg-white">I</button>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'underline')" class="rounded-lg px-3 py-2 text-sm font-black underline text-slate-700 hover:bg-white">U</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'formatBlock', 'h2')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">H2</button>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'formatBlock', 'p')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">P</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'insertUnorderedList')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">List</button>
                            <button type="button" @click="editorCommand('editHtmlEditor', 'insertOrderedList')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">1. List</button>
                            <button type="button" @click="editorLink('editHtmlEditor')" class="rounded-lg px-3 py-2 text-xs font-black text-slate-700 hover:bg-white">Link</button>
                            <button type="button" @click="clearEditorFormat('editHtmlEditor')" class="ml-auto rounded-lg px-3 py-2 text-xs font-black text-slate-500 hover:bg-white">Clear</button>
                        </div>
                        <div x-ref="editHtmlEditor"
                             contenteditable="true"
                             @input="editModal.form.body_html = $event.currentTarget.innerHTML"
                             class="min-h-60 max-h-96 overflow-y-auto px-4 py-3 text-sm font-semibold leading-7 text-slate-800 outline-none empty:before:text-slate-400 empty:before:content-['Write_the_email_body...']"></div>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Plain Text Fallback</span>
                    <textarea x-model="editModal.form.body_text" rows="6" class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                </label>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-900">Enabled</p>
                            <p class="text-sm font-semibold text-slate-500">When disabled, this template skips sending and writes no email log.</p>
                        </div>
                        <button type="button" @click="editModal.form.is_enabled = !editModal.form.is_enabled"
                                class="relative h-9 w-16 rounded-full transition"
                                :class="editModal.form.is_enabled ? 'bg-emerald-500' : 'bg-slate-300'">
                            <span class="absolute top-1 h-7 w-7 rounded-full bg-white shadow transition" :class="editModal.form.is_enabled ? 'left-8' : 'left-1'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Supported Variables</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="variable in editModal.form.variables" :key="variable">
                            <code class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" x-text="'{' + '{ ' + variable + ' }' + '}'"></code>
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" @click="editModal.open = false" class="rounded-2xl border-2 border-slate-200 bg-white px-6 py-3 text-sm font-black text-slate-700">Cancel</button>
                <button type="button" @click="save()" :disabled="saving" class="rounded-2xl bg-orange-600 px-7 py-3 text-sm font-black text-white shadow-lg shadow-orange-200 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Template'"></button>
            </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="previewModal.open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div @click.outside="previewModal.open = false" class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-600">Preview</p>
                    <h3 class="text-2xl font-black text-slate-950" x-text="previewModal.template?.name || 'Email Preview'"></h3>
                </div>
                <button type="button" @click="previewModal.open = false" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="max-h-[calc(92vh-9rem)] overflow-y-auto bg-slate-100 p-6">
                <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Subject</p>
                    <p class="mt-1 text-lg font-black text-slate-950" x-text="previewModal.preview.subject"></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="prose max-w-none" x-html="previewModal.preview.body_html"></div>
                </div>
                <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <summary class="cursor-pointer text-sm font-black text-slate-700">Plain text fallback</summary>
                    <pre class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-950 p-4 text-sm text-white" x-text="previewModal.preview.body_text"></pre>
                </details>
            </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('emailTemplatesManager', (data) => ({
        templates: data.templates || [],
        categories: data.categories || [],
        recipientTypes: data.recipientTypes || [],
        filters: { search: '', category: '', recipient: '', status: '' },
        showFilters: false,
        page: 1,
        perPage: 25,
        toast: { message: '', title: '', type: 'success' },
        saving: false,
        createModal: { open: false, variablesText: '', keyTouched: false, form: {} },
        editModal: { open: false, form: {} },
        previewModal: { open: false, template: null, preview: { subject: '', body_html: '', body_text: '' } },

        get enabledCount() {
            return this.templates.filter((template) => template.is_enabled).length;
        },

        get filteredTemplates() {
            const search = this.filters.search.trim().toLowerCase();
            return this.templates.filter((template) => {
                const matchesSearch = !search || [template.name, template.key, template.subject, template.category, template.recipient_label].some((value) => String(value || '').toLowerCase().includes(search));
                const matchesCategory = !this.filters.category || template.category === this.filters.category;
                const matchesRecipient = !this.filters.recipient || template.recipient_type === this.filters.recipient;
                const matchesStatus = !this.filters.status || (this.filters.status === 'enabled' ? template.is_enabled : !template.is_enabled);
                return matchesSearch && matchesCategory && matchesRecipient && matchesStatus;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredTemplates.length / Number(this.perPage || 25)));
        },

        get paginatedTemplates() {
            if (this.page > this.totalPages) this.page = this.totalPages;
            const start = (this.page - 1) * Number(this.perPage || 25);
            return this.filteredTemplates.slice(start, start + Number(this.perPage || 25));
        },

        get pageFrom() {
            if (this.filteredTemplates.length === 0) return 0;
            return ((this.page - 1) * Number(this.perPage || 25)) + 1;
        },

        get pageTo() {
            return Math.min(this.page * Number(this.perPage || 25), this.filteredTemplates.length);
        },

        label(value) {
            return String(value || '').replaceAll('_', ' / ').replace(/\b\w/g, (char) => char.toUpperCase());
        },

        clearFilters() {
            this.filters = { search: '', category: '', recipient: '', status: '' };
            this.page = 1;
        },

        prevPage() {
            if (this.page > 1) this.page -= 1;
        },

        nextPage() {
            if (this.page < this.totalPages) this.page += 1;
        },

        setEditorHtml(ref, html) {
            this.$nextTick(() => {
                if (this.$refs[ref]) this.$refs[ref].innerHTML = html || '';
            });
        },

        syncEditorHtml(ref) {
            const html = this.$refs[ref]?.innerHTML || '';
            if (ref === 'createHtmlEditor') this.createModal.form.body_html = html;
            if (ref === 'editHtmlEditor') this.editModal.form.body_html = html;
        },

        editorCommand(ref, command, value = null) {
            this.$refs[ref]?.focus();
            document.execCommand(command, false, value);
            this.syncEditorHtml(ref);
        },

        editorLink(ref) {
            const url = window.prompt('Enter link URL');
            if (!url) return;
            this.editorCommand(ref, 'createLink', url);
        },

        clearEditorFormat(ref) {
            this.editorCommand(ref, 'removeFormat');
        },

        openCreate() {
            this.createModal = {
                open: true,
                variablesText: '',
                keyTouched: false,
                form: {
                    key: '',
                    name: '',
                    category: '',
                    recipient_type: 'vendor',
                    subject: '',
                    body_html: '',
                    body_text: '',
                    is_enabled: false,
                },
            };
            this.setEditorHtml('createHtmlEditor', '');
        },

        syncCreateKey() {
            if (this.createModal.keyTouched || this.createModal.form.key) return;
            this.createModal.form.key = String(this.createModal.form.name || '')
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        createVariables() {
            return this.createModal.variablesText
                .split(',')
                .map((variable) => variable.trim())
                .filter(Boolean);
        },

        async create() {
            this.saving = true;
            this.syncEditorHtml('createHtmlEditor');
            try {
                const response = await fetch(this.endpoint('store'), {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        key: this.createModal.form.key,
                        name: this.createModal.form.name,
                        category: this.createModal.form.category,
                        recipient_type: this.createModal.form.recipient_type,
                        subject: this.createModal.form.subject,
                        body_html: this.createModal.form.body_html,
                        body_text: this.createModal.form.body_text,
                        variables: this.createVariables(),
                        is_enabled: !!this.createModal.form.is_enabled,
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not create template.');
                this.templates.unshift(result.template);
                this.categories = [...new Set([...this.categories, result.template.category])].sort();
                this.recipientTypes = [...new Set([...this.recipientTypes, result.template.recipient_type])].sort();
                this.createModal.open = false;
                this.page = 1;
                this.notify('Template created.', result.message || 'Email template created.', 'success');
            } catch (error) {
                this.notify('Could not create.', error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        edit(template) {
            this.editModal.form = JSON.parse(JSON.stringify(template));
            this.editModal.open = true;
            this.setEditorHtml('editHtmlEditor', this.editModal.form.body_html);
        },

        async save() {
            this.saving = true;
            this.syncEditorHtml('editHtmlEditor');
            try {
                const response = await fetch(this.endpoint('update', this.editModal.form.id), {
                    method: 'PUT',
                    headers: this.headers(),
                    body: JSON.stringify({
                        subject: this.editModal.form.subject,
                        body_html: this.editModal.form.body_html,
                        body_text: this.editModal.form.body_text,
                        is_enabled: !!this.editModal.form.is_enabled,
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not save template.');
                this.replaceTemplate(result.template);
                this.editModal.open = false;
                this.notify('Template saved.', result.message || 'Email template updated.', 'success');
            } catch (error) {
                this.notify('Could not save.', error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async toggle(template) {
            try {
                const response = await fetch(this.endpoint('toggle', template.id), {
                    method: 'PATCH',
                    headers: this.headers(),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not update status.');
                this.replaceTemplate(result.template);
                this.notify('Status updated.', result.message, 'success');
            } catch (error) {
                this.notify('Could not update.', error.message, 'error');
            }
        },

        async preview(template) {
            this.previewModal.template = template;
            this.previewModal.open = true;
            this.previewModal.preview = { subject: 'Loading...', body_html: '<p>Loading preview...</p>', body_text: '' };
            try {
                const response = await fetch(this.endpoint('preview', template.id), { headers: { 'Accept': 'application/json' } });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not load preview.');
                this.previewModal.preview = result.preview;
            } catch (error) {
                this.previewModal.preview = { subject: 'Preview failed', body_html: `<p>${error.message}</p>`, body_text: error.message };
            }
        },

        replaceTemplate(template) {
            const index = this.templates.findIndex((item) => item.id === template.id);
            if (index >= 0) this.templates.splice(index, 1, template);
        },

        endpoint(type, id) {
            const routes = {
                store: @json(route('admin.settings.email-templates.store')),
                update: @json(route('admin.settings.email-templates.update', ['emailTemplate' => '__ID__'])),
                toggle: @json(route('admin.settings.email-templates.toggle', ['emailTemplate' => '__ID__'])),
                preview: @json(route('admin.settings.email-templates.preview', ['emailTemplate' => '__ID__'])),
            };
            return id ? routes[type].replace('__ID__', id) : routes[type];
        },

        headers() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            };
        },

        notify(title, message, type = 'success') {
            this.toast = { title, message, type };
            setTimeout(() => { this.toast.message = ''; }, 4200);
        },
    }));
});
</script>
@endpush
