{{-- Admin › Templates & Uploads › Templates: the paper forms, like a design tool's home.
     Create form template from a paper size (or a PDF), then browse every form as a page thumbnail. --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';
    $input = 'h-9 w-full rounded-lg border border-slate-200 bg-white px-3 py-0 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/15 [@media(pointer:coarse)]:h-11';
    $chip = 'inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-[13px] font-medium transition ' . $focus;
    $menuItem = 'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[13px] text-slate-700 hover:bg-slate-50';
    $starters = [
        'A4' => ['21 × 29.7 cm', 'aspect-[210/297]'],
        'Letter' => ['8.5 × 11 in', 'aspect-[216/279]'],
        'Legal' => ['8.5 × 14 in', 'aspect-[216/356]'],
        'A3' => ['29.7 × 42 cm', 'aspect-[297/420]'],
    ];
    $sizeLabel = fn ($v) => ! $v ? 'No page' : ($v->document_size === 'Custom' ? "{$v->custom_width} × {$v->custom_height} cm" : $v->document_size) . ($v->orientation === 'landscape' ? ' · landscape' : '');
@endphp

<div class="mx-auto w-full max-w-7xl pb-16">
    <x-admin.area-header name="documents" active="templates" />

    @if (session('message'))
        <p class="mb-4 rounded-lg bg-[#d9f3e1] px-4 py-2.5 text-sm text-[#1f6b3a]" role="status">{{ session('message') }}</p>
    @endif

    {{-- ===== Create form template===== --}}
    <section class="mb-8 rounded-2xl bg-gradient-to-br from-[#e6f0fc] via-[#eee9f9] to-[#fdeef4] p-4 sm:p-6" aria-labelledby="start-h">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 id="start-h" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">Create form template</h2>
                <p class="text-sm text-slate-600">Upload the new form template as an image or PDF, then add the required fields students need to complete.</p>
            </div>
            <p class="text-xs text-slate-500">{{ $totalCount }} {{ Str::plural('template', $totalCount) }} · {{ $publicCount }} public</p>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-1 sm:grid sm:grid-cols-3 sm:overflow-visible lg:grid-cols-6">
            <button type="button" wire:click="openCreate" aria-label="New template"
                    class="group flex w-32 shrink-0 flex-col items-center rounded-xl bg-[#2A57B4] p-3 text-center text-white shadow-sm transition hover:bg-[#24499A] hover:shadow-md sm:w-auto {{ $focus }}">
                <span class="grid h-24 w-[4.25rem] place-items-center rounded-md border-2 border-dashed border-white/50 transition group-hover:border-white">
                    <i class="bx bx-plus text-4xl" aria-hidden="true"></i>
                </span>
                <span class="mt-2.5 text-sm font-semibold">New template</span>
                <span class="text-xs text-white/75">Image or PDF</span>
            </button>
            <button type="button" wire:click="openCreate" data-pdf-start
                    class="group flex w-32 shrink-0 flex-col items-center rounded-xl bg-white/70 p-3 text-center ring-1 ring-white transition hover:bg-white hover:shadow-md sm:w-auto {{ $focus }}">
                <span class="grid h-24 w-[4.25rem] place-items-center rounded-md border-2 border-dashed border-[#2A57B4]/40 bg-white text-[#2A57B4] transition group-hover:border-[#2A57B4]">
                    <i class="bx bxs-file-pdf text-3xl" aria-hidden="true"></i>
                </span>
                <span class="mt-2.5 text-sm font-semibold text-slate-900">From a PDF</span>
                <span class="text-xs text-slate-500">Size taken from the page</span>
            </button>
            @foreach ($starters as $size => [$dims, $aspect])
                <button type="button" wire:click="openCreate('{{ $size }}')"
                        class="group flex w-32 shrink-0 flex-col items-center rounded-xl bg-white/70 p-3 text-center ring-1 ring-white transition hover:bg-white hover:shadow-md sm:w-auto {{ $focus }}">
                    <span class="flex h-24 items-end">
                        <span class="{{ $aspect }} block h-full max-h-24 rounded-[3px] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.12),0_6px_16px_-8px_rgba(15,23,42,0.35)] ring-1 ring-slate-200 transition group-hover:-translate-y-1 group-hover:ring-[#2A57B4]/40"
                              style="{{ $size === 'A3' ? 'height: 6rem' : ($size === 'Legal' ? 'height: 5.8rem' : 'height: 4.6rem') }}"></span>
                    </span>
                    <span class="mt-2.5 text-sm font-semibold text-slate-900">{{ $size }}</span>
                    <span class="text-xs text-slate-500">{{ $dims }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- ===== Browse ===== --}}
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative lg:w-72">
            <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search templates" aria-label="Search templates" class="{{ $input }} pl-10">
        </div>
        <div class="-mx-1 flex min-w-0 flex-1 gap-1.5 overflow-x-auto px-1 pb-0.5">
            <button type="button" wire:click="$set('filterCategory', '')" aria-pressed="{{ $filterCategory === '' ? 'true' : 'false' }}"
                    class="{{ $chip }} {{ $filterCategory === '' ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">All</button>
            @foreach ($categories as $c)
                <button type="button" wire:click="$set('filterCategory', '{{ $c['value'] }}')" aria-pressed="{{ $filterCategory === $c['value'] ? 'true' : 'false' }}"
                        class="{{ $chip }} {{ $filterCategory === $c['value'] ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $c['label'] }} <span class="tabular-nums {{ $filterCategory === $c['value'] ? 'text-white/70' : 'text-slate-400' }}">{{ $c['count'] }}</span>
                </button>
            @endforeach
            @if ($uncategorised)
                <button type="button" wire:click="$set('filterCategory', 'none')" aria-pressed="{{ $filterCategory === 'none' ? 'true' : 'false' }}"
                        class="{{ $chip }} {{ $filterCategory === 'none' ? 'border-amber-500 bg-amber-500 text-white' : 'border-amber-200 bg-[#fef3c7] text-[#8a5a00] hover:bg-amber-100' }}">
                    <i class="bx bx-error-circle text-sm" aria-hidden="true"></i> No category <span class="tabular-nums">{{ $uncategorised }}</span>
                </button>
            @endif
        </div>
        <label class="flex shrink-0 items-center gap-2 text-sm text-slate-500">
            <span class="hidden sm:inline">Sort</span>
            <select wire:model.live="sort" class="h-9 rounded-lg border border-slate-200 bg-white pl-3 pr-8 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/15">
                <option value="recent">Recently edited</option>
                <option value="name">Name A–Z</option>
                <option value="used">Most used</option>
            </select>
        </label>
    </div>

    @if ($templates->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-16 text-center">
            <i class="bx bx-layout text-4xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-medium text-slate-700">{{ $totalCount ? 'No template matches.' : 'No form templates created yet.' }}</p>
            <p class="text-sm text-slate-500">{{ $totalCount ? 'Try another search or category.' : 'Create your first form template.' }}</p>
        </div>
    @else
        {{-- Same card as My documents, Issued documents and Uploads: the whole page, its status on the corner, then the details --}}
        <ul class="grid grid-cols-2 content-start gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($templates as $t)
                @php
                    $v = $t->currentVersion;
                    $fields = (int) ($fieldCounts[$t->current_version_id] ?? 0);
                    $public = $t->status === 'active';
                    $cat = $t->category?->value ?? $t->category;
                @endphp
                <li wire:key="tpl-{{ $t->template_id }}" class="group relative flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] motion-reduce:hover:translate-y-0">
                    <div class="relative">
                        <a href="{{ route('admin.templates.editor', $t->template_id) }}"
                           class="relative block aspect-[842/1171] overflow-hidden rounded-t-xl border-b border-slate-200 bg-white {{ $focus }}"
                           aria-label="Open {{ $t->name }}">
                            @if ($v?->image_path)
                                <img src="{{ Storage::disk('public')->url($v->image_path) }}" alt="" loading="lazy"
                                     class="absolute inset-0 h-full w-full bg-white object-contain">
                            @else
                                <span class="absolute inset-0 grid place-items-center bg-[#f0f4fb] text-[#1E4090]"><i class="bx bx-image text-4xl"></i></span>
                            @endif
                            <span class="absolute inset-x-0 bottom-0 flex translate-y-2 justify-center pb-3 opacity-0 transition group-hover:translate-y-0 group-hover:opacity-100">
                                <span class="inline-flex h-8 items-center gap-1 rounded-lg bg-[#2A57B4] px-3.5 text-[13px] font-medium text-white shadow-md"><i class="bx bx-edit-alt text-sm"></i> Open editor</span>
                            </span>
                        </a>

                        <span class="pointer-events-none absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium shadow-sm ring-1 {{ $public ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                            <i class="bx {{ $public ? 'bx-globe' : 'bx-lock-alt' }} text-xs" aria-hidden="true"></i>{{ $public ? 'Public' : 'Private' }}
                        </span>

                        {{-- More --}}
                        <div x-data="{ open: false, cats: false }" class="absolute right-2 top-2" @click.outside="open = false; cats = false" @keydown.escape="open = false; cats = false">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="More for {{ $t->name }}"
                                    class="grid h-8 w-8 place-items-center rounded-lg bg-white/95 text-slate-600 opacity-0 shadow-sm ring-1 ring-slate-200 transition hover:text-slate-900 focus:opacity-100 group-hover:opacity-100 [@media(pointer:coarse)]:opacity-100 {{ $focus }}"
                                    :class="open && '!opacity-100'">
                                <i class="bx bx-dots-horizontal-rounded text-lg" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-30 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-1 shadow-xl" role="menu">
                                <a href="{{ route('admin.templates.editor', $t->template_id) }}" class="{{ $menuItem }}" role="menuitem"><i class="bx bx-edit-alt text-base text-slate-400"></i>Open editor</a>
                                <button type="button" wire:click="duplicate({{ $t->template_id }})" @click="open = false" class="{{ $menuItem }}" role="menuitem"><i class="bx bx-copy text-base text-slate-400"></i>Duplicate</button>
                                <button type="button" wire:click="toggleStatus({{ $t->template_id }})" @click="open = false" class="{{ $menuItem }}" role="menuitem">
                                    <i class="bx {{ $public ? 'bx-lock-alt' : 'bx-globe' }} text-base text-slate-400"></i>{{ $public ? 'Make private' : 'Make public' }}
                                </button>
                                <button type="button" @click="cats = !cats" class="{{ $menuItem }} justify-between" role="menuitem" :aria-expanded="cats">
                                    <span class="flex items-center gap-2"><i class="bx bx-purchase-tag text-base text-slate-400"></i>Category</span><i class="bx bx-chevron-down text-base text-slate-400 transition" :class="cats && 'rotate-180'"></i>
                                </button>
                                <div x-show="cats" x-cloak class="mb-1 ml-3 border-l border-slate-200 pl-1">
                                    @foreach ($allCategories as $c)
                                        <button type="button" wire:click="setCategory({{ $t->template_id }}, '{{ $c->value }}')" @click="open = false" class="{{ $menuItem }} !py-1.5">
                                            <i class="bx bx-check text-base {{ $cat === $c->value ? 'text-[#2A57B4]' : 'invisible' }}"></i>{{ $c->label() }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="my-1 h-px bg-slate-100"></div>
                                <button type="button" wire:click="confirmDelete({{ $t->template_id }})" @click="open = false" class="{{ $menuItem }} !text-rose-600 hover:!bg-rose-50" role="menuitem"><i class="bx bx-trash text-base"></i>Delete</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-3 sm:p-3.5">
                        <a href="{{ route('admin.templates.editor', $t->template_id) }}" class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-[#2A57B4]" title="{{ $t->name }}">{{ $t->name }}</a>
                        <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ $sizeLabel($v) }} · {{ $fields }} {{ Str::plural('field', $fields) }}@if ($t->student_workspaces_count) · used {{ number_format($t->student_workspaces_count) }}×@endif
                        </p>
                        <p class="mt-auto flex pt-1.5 items-center justify-between gap-2 text-xs">
                            @if ($cat)
                                <span class="truncate text-slate-500">{{ \App\Enums\DocumentCategory::from($cat)->label() }}</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[#8a5a00]"><i class="bx bx-error-circle"></i>No category</span>
                            @endif
                            <span class="shrink-0 text-slate-400">{{ $t->updated_at?->diffForHumans(short: true) }}</span>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ===== New template ===== --}}
    @if ($showCreate)
        @teleport('body')
        <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4" wire:keydown.escape.window="closeCreate">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl" role="dialog" aria-modal="true" aria-labelledby="new-tpl-h"
                 x-data="{
                     dragging: false, busy: false, error: '', preview: null, pdf: null, pages: [],
                     async pick(file) {
                         if (!file) return;
                         this.error = '';
                         const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
                         if (!isPdf && !/^image\//.test(file.type)) { this.error = 'Use a PNG or JPG image, or a PDF.'; return; }
                         if (!$wire.name) $wire.name = file.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
                         if (isPdf) {
                             this.busy = true;
                             try {
                                 this.pdf?.destroy();
                                 this.pdf = await window.DocumatePdf.open(file);
                                 this.pages = [];
                                 for (let n = 1; n <= Math.min(this.pdf.numPages, 24); n++) this.pages.push({ n, src: await this.pdf.thumbnail(n, 120) });
                                 if (this.pdf.numPages === 1) await this.usePage(1);
                             } catch (e) { console.error(e); this.error = 'This PDF could not be read.'; }
                             this.busy = false;
                             return;
                         }
                         if (file.size > 2 * 1024 * 1024) { this.error = 'The image is over 2 MB. Save it smaller, or use the PDF.'; return; }
                         // The paper follows the picture's shape (a standard size when it matches one)
                         const paper = await window.DocumatePaper.fromImage(file);
                         if (paper) await $wire.usePaper(paper.size, paper.orientation, paper.widthCm, paper.heightCm);
                         this.preview = URL.createObjectURL(file);
                         this.upload(file);
                     },
                     async usePage(n) {
                         this.busy = true;
                         try {
                             const paper = await this.pdf.paper(n);
                             await $wire.usePaper(paper.size, paper.orientation, paper.widthCm, paper.heightCm);
                             const file = await this.pdf.toImageFile(n);
                             this.preview = URL.createObjectURL(file);
                             this.pages = [];
                             this.upload(file);
                         } catch (e) { this.error = e.message || 'This page could not be converted.'; this.busy = false; }
                     },
                     upload(file) {
                         this.busy = true;
                         $wire.upload('image', file, () => { this.busy = false; }, () => { this.busy = false; this.error = 'The upload failed. Try again.'; });
                     },
                 }">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 id="new-tpl-h" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">New template</h2>
                        <p class="text-sm text-slate-500">Add the form and its required fields and proceed to the form editor.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 {{ $focus }}" aria-label="Close"><i class="bx bx-x text-2xl"></i></button>
                </div>

                <div class="grid min-h-0 flex-1 overflow-y-auto md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                    {{-- The form itself --}}
                    <div class="border-b border-slate-200 bg-[#eef0f3] p-5 md:border-b-0 md:border-r">
                        <template x-if="pages.length">
                            <div>
                                <p class="mb-3 text-sm font-medium text-slate-700">Which page is the form? <span class="font-normal text-slate-500" x-text="'(' + pdf.numPages + ' pages)'"></span></p>
                                <ul class="grid grid-cols-3 gap-3">
                                    <template x-for="p in pages" :key="p.n">
                                        <li><button type="button" @click="usePage(p.n)" :disabled="busy" class="group block w-full text-center disabled:opacity-60">
                                            <span class="block overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:ring-2 group-hover:ring-[#2A57B4]"><img :src="p.src" alt="" class="w-full"></span>
                                            <span class="mt-1 block text-xs text-slate-600" x-text="'Page ' + p.n"></span>
                                        </button></li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <template x-if="!pages.length">
                            <label class="relative flex h-full min-h-[18rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-4 text-center transition"
                                   :class="dragging ? 'border-[#2A57B4] bg-[#e2e9f7]' : 'border-slate-300 bg-white/60 hover:border-[#2A57B4]/60'"
                                   @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pick($event.dataTransfer.files[0])">
                                <input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp,application/pdf,.pdf" @change="pick($event.target.files[0]); $event.target.value = ''">
                                <template x-if="preview">
                                    <img :src="preview" alt="The form" class="max-h-[22rem] max-w-full bg-white object-contain shadow-[0_1px_2px_rgba(15,23,42,0.12),0_10px_24px_-12px_rgba(15,23,42,0.4)]">
                                </template>
                                <template x-if="!preview">
                                    <span class="flex flex-col items-center">
                                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-[#dcecfa] text-[#2A57B4]"><i class="bx bx-cloud-upload text-2xl"></i></span>
                                        <span class="mt-3 text-sm font-semibold text-slate-900">Drag your file here or select one from your device.</span>
                                        <span class="mt-1 text-xs text-slate-500">PDF, PNG or JPG. A PDF page is converted into an image for you.</span>
                                    </span>
                                </template>
                                <span x-show="preview" class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-[#2A57B4]"><i class="bx bx-refresh"></i>Choose another file</span>
                                <span x-show="busy" class="absolute inset-0 grid place-items-center rounded-xl bg-white/70 text-sm text-slate-600"><span><i class="bx bx-loader-alt animate-spin"></i> Working…</span></span>
                            </label>
                        </template>
                        <p x-show="error" x-cloak class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="error"></p>
                        @error('image') <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    {{-- Details --}}
                    <div class="space-y-4 p-5">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Name</span>
                            <input type="text" wire:model="name" placeholder="e.g. Parent's consent form" class="{{ $input }} @error('name') !border-rose-400 @enderror">
                            @error('name') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Category</span>
                            <select wire:model="category" class="{{ $input }} @error('category') !border-rose-400 @enderror">
                                <option value="">Select the form category…</option>
                                @foreach ($allCategories as $c)
                                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                                @endforeach
                            </select>
                            @error('category') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">About this form <span class="font-normal text-slate-400">(optional)</span></span>
                            <textarea wire:model="description" rows="2" maxlength="300" placeholder="e.g. Permission from a parent or guardian for an off-campus activity."
                                      class="{{ str_replace('h-9 ', '', $input) }} resize-none py-2"></textarea>
                            <span class="mt-1 block text-xs text-slate-500">Add a brief description that is visible below the form name when selecting a form.</span>
                        </label>

                        <div>
                            <span class="mb-1 block text-sm font-medium text-slate-700">Paper</span>
                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach (['A4', 'Letter', 'Legal', 'A3'] as $size)
                                    <button type="button" wire:click="$set('paper_size', '{{ $size }}')"
                                            class="h-9 rounded-lg border text-[13px] font-medium transition {{ $focus }} {{ $paper_size === $size ? 'border-[#2A57B4] bg-[#e2e9f7] text-[#1E4090]' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">{{ $size }}</button>
                                @endforeach
                            </div>
                            @if ($paper_size === 'Custom')
                                <p class="mt-2 flex items-center gap-1.5 rounded-lg bg-[#e2e9f7] px-3 py-2 text-xs text-[#1E4090]">
                                    <i class="bx bx-ruler text-sm" aria-hidden="true"></i> {{ $custom_width }} × {{ $custom_height }} cm, taken from the file. Pick a size above to change it.
                                </p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Automatically set based on the uploaded file: the PDF page size or the image dimensions.</p>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-sm font-medium text-slate-700">Orientation</span>
                            <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                                @foreach (['portrait' => ['bx-rectangle rotate-90', 'Portrait'], 'landscape' => ['bx-rectangle', 'Landscape']] as $o => [$icon, $lbl])
                                    <button type="button" wire:click="$set('orientation', '{{ $o }}')"
                                            class="flex h-8 flex-1 items-center justify-center gap-1.5 rounded-md text-[13px] font-medium transition {{ $focus }} {{ $orientation === $o ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                                        <i class="bx {{ $icon }} text-base"></i>{{ $lbl }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-500"> For optimal print quality, upload a clear scan at 200 DPI or higher. For PDF files, the page size is automatically set.</p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" wire:click="closeCreate" class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 {{ $focus }}">Cancel</button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,image" :disabled="busy"
                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] disabled:opacity-50 {{ $focus }}">
                        <span wire:loading.remove wire:target="save">Create and open editor</span>
                        <span wire:loading wire:target="save"><i class="bx bx-loader-alt animate-spin"></i> Creating…</span>
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ===== Delete ===== --}}
    @if ($confirming)
        @teleport('body')
        <div class="fixed inset-0 z-[10000] grid place-items-center bg-slate-900/50 p-4" wire:keydown.escape.window="$set('confirmDeleteId', null)">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="del-h">
                <h2 id="del-h" class="text-base font-semibold text-slate-900">Delete "{{ $confirming->name }}"?</h2>
                <p class="mt-1.5 text-sm text-slate-600">Students can no longer start this form. {{ $confirming->student_workspaces_count ? 'The ' . number_format($confirming->student_workspaces_count) . ' ' . Str::plural('transaction', $confirming->student_workspaces_count) . ' made from it stay in Transaction Records with their form and PDF.' : 'Nobody has used it yet.' }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('confirmDeleteId', null)" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 {{ $focus }}">Cancel</button>
                    <button type="button" wire:click="deleteTemplate" class="inline-flex h-9 items-center rounded-lg bg-rose-600 px-4 text-sm font-medium text-white hover:bg-rose-700 {{ $focus }}">Delete template</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
