{{-- Admin › Templates › a template: a design-editor canvas over the form image.
     All behaviour is in resources/js/template-editor.js (Alpine "templateEditor"); wire:ignore keeps Livewire
     from redrawing it, the server only stores what it sends. --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40';
    $iconBtn = 'grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 disabled:opacity-35 disabled:hover:bg-transparent ' . $focus;
    $input = 'h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5 py-0 text-[13px] text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/15';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
    $section = 'border-b border-slate-200 px-4 py-4';
    $sectionTitle = 'mb-3 text-[13px] font-semibold text-slate-900';
    $seg = 'flex rounded-lg border border-slate-200 bg-slate-50 p-0.5';
    $segBtn = 'flex h-7 flex-1 items-center justify-center gap-1 rounded-md text-xs font-medium transition ' . $focus;
@endphp

<div wire:ignore x-data="templateEditor(@js($editorConfig))"
     class="flex h-screen flex-col overflow-hidden bg-[#eef0f3] text-slate-800 [font-feature-settings:'tnum']">

    {{-- ===================== TOP BAR ===================== --}}
    <header class="relative z-30 flex h-12 shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-2 sm:px-3">
        <a href="{{ route('admin.templates') }}" class="{{ $iconBtn }}" title="Back to templates" aria-label="Back to templates">
            <i class="bx bx-arrow-back text-lg" aria-hidden="true"></i>
        </a>
        <div class="flex min-w-0 flex-1 items-center gap-2 md:flex-none">
            <input type="text" :value="name" @change="rename($event.target.value)" @keydown.enter="$event.target.blur()" aria-label="Template name"
                   class="h-8 w-full min-w-0 truncate rounded-lg border md:w-32 lg:w-40 xl:w-52 2xl:w-72 border-transparent bg-transparent px-2 text-sm font-semibold text-slate-900 hover:border-slate-200 focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/15">
            <span class="hidden items-center gap-1 whitespace-nowrap text-xs sm:inline-flex" role="status" aria-live="polite">
                <template x-if="saveState === 'saved'"><span class="inline-flex items-center gap-1 text-slate-400" title="Saved"><i class="bx bx-cloud-upload text-sm"></i><span class="hidden xl:inline">Saved</span></span></template>
                <template x-if="saveState === 'saving' || saveState === 'dirty'"><span class="inline-flex items-center gap-1 text-slate-400" title="Saving…"><i class="bx bx-loader-alt animate-spin text-sm"></i><span class="hidden xl:inline">Saving…</span></span></template>
                <template x-if="saveState === 'error'"><button type="button" @click="save()" class="inline-flex items-center gap-1 font-medium text-rose-600 hover:underline"><i class="bx bx-error-circle text-sm"></i>Not saved · retry</button></template>
            </span>
        </div>

        {{-- Tools and fields (on phones they live in the bottom toolbar) --}}
        <div class="mx-auto hidden min-w-0 shrink items-center gap-1 md:flex">
            <div class="flex items-center gap-0.5 rounded-lg bg-slate-100 p-0.5">
                <button type="button" @click="tool = 'select'" :class="tool === 'select' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                        class="grid h-7 w-7 place-items-center rounded-md {{ $focus }}" title="Select (V)" aria-label="Select tool"><i class="bx bx-pointer text-base"></i></button>
                <button type="button" @click="tool = 'hand'" :class="tool === 'hand' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                        class="grid h-7 w-7 place-items-center rounded-md {{ $focus }}" title="Pan (H, or hold Space)" aria-label="Pan tool"><i class="bx bx-move text-base"></i></button>
            </div>
            <span class="mx-1 h-5 w-px bg-slate-200" aria-hidden="true"></span>
<div class="flex items-center gap-0.5" data-tour="fields">
            <template x-for="(t, key) in types" :key="key">
                <button type="button" @click="add(key)" :title="'Add a ' + t.label.toLowerCase() + ' field'"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2 text-[13px] font-medium text-slate-700 transition hover:bg-slate-100 {{ $focus }}">
                    <span class="grid h-5 w-5 place-items-center rounded" :class="t.tint"><i class="bx text-sm" :class="t.icon"></i></span>
                    <span class="hidden 2xl:inline" x-text="t.label"></span>
                </button>
            </template>
            </div>
            <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" title="Fields filled in automatically" data-tour="auto"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2 text-[13px] font-medium text-slate-700 transition hover:bg-slate-100 {{ $focus }}">
                    <span class="grid h-5 w-5 place-items-center rounded bg-[#fef3c7] text-[#8a5a00]"><i class="bx bx-bolt-circle text-sm"></i></span>
                    <span class="hidden 2xl:inline">Automatic</span><i class="bx bx-chevron-down text-sm text-slate-400"></i>
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 top-full z-40 mt-1 max-h-[70vh] w-64 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 text-[13px] shadow-lg">
                    <p class="px-3 pb-1 pt-2 text-xs text-slate-500">Filled in from the student's profile</p>
                    <template x-for="g in automaticGroups" :key="g.title">
                        <div class="py-0.5">
                            <p class="px-3 pb-0.5 pt-1.5 text-[11px] font-semibold text-slate-400" x-text="g.title"></p>
                            <template x-for="it in g.items" :key="it.key">
                                <button type="button" @click="add('sys:' + it.key); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-left hover:bg-slate-50">
                                    <i class="bx bx-bolt-circle text-base text-amber-500"></i><span class="truncate" x-text="it.label"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <span class="mx-1 h-5 w-px bg-slate-200" aria-hidden="true"></span>
            <button type="button" @click="undo()" :disabled="!past.length" class="{{ $iconBtn }}" title="Undo (Ctrl+Z)" aria-label="Undo"><i class="bx bx-undo text-lg"></i></button>
            <button type="button" @click="redo()" :disabled="!future.length" class="{{ $iconBtn }}" title="Redo (Ctrl+Shift+Z)" aria-label="Redo"><i class="bx bx-redo text-lg"></i></button>
        </div>

        {{-- View and publish --}}
        <div class="flex shrink-0 items-center gap-1.5">
            <button type="button" @click="undo()" :disabled="!past.length" class="{{ $iconBtn }} md:!hidden" aria-label="Undo"><i class="bx bx-undo text-lg"></i></button>
            <button type="button" @click="redo()" :disabled="!future.length" class="{{ $iconBtn }} md:!hidden" aria-label="Redo"><i class="bx bx-redo text-lg"></i></button>
            <div x-data="{ open: false }" class="relative hidden shrink-0 items-center rounded-lg border border-slate-200 xl:flex" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="zoomBy(1 / 1.15)" class="grid h-8 w-7 place-items-center text-slate-500 hover:text-slate-900" title="Zoom out (Ctrl+-)" aria-label="Zoom out"><i class="bx bx-minus text-base"></i></button>
                <button type="button" @click="open = !open" class="h-8 w-14 text-center text-xs font-medium tabular-nums text-slate-700 hover:bg-slate-50" x-text="Math.round(zoom * 100) + '%'" aria-label="Zoom level"></button>
                <button type="button" @click="zoomBy(1.15)" class="grid h-8 w-7 place-items-center text-slate-500 hover:text-slate-900" title="Zoom in (Ctrl+=)" aria-label="Zoom in"><i class="bx bx-plus text-base"></i></button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 top-full z-40 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1 text-[13px] shadow-lg">
                    <button type="button" @click="fit(); open = false" class="flex w-full items-center justify-between rounded-lg px-3 py-2 hover:bg-slate-50">Zoom to fit <kbd class="text-[11px] text-slate-400">Ctrl 0</kbd></button>
                    <template x-for="z in [0.5, 1, 1.5, 2]" :key="z">
                        <button type="button" @click="zoomTo(z); open = false" class="flex w-full items-center justify-between rounded-lg px-3 py-2 hover:bg-slate-50"><span x-text="(z * 100) + '%'"></span><kbd x-show="z === 1" class="text-[11px] text-slate-400">Ctrl 1</kbd></button>
                    </template>
                </div>
            </div>
            <button type="button" @click="startTour()" class="{{ $iconBtn }}" title="Take the tour of the editor" aria-label="Take the tour of the editor">
                <i class="bx bx-help-circle text-lg"></i>
            </button>
            <button type="button" data-tour="preview" @click="openPreview(@js(route('admin.templates.preview', $template->template_id)))"
                    class="inline-flex h-8 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 text-[13px] font-medium text-slate-700 transition hover:bg-slate-50 {{ $focus }}"
                    title="See the form as students fill it in (opens a new tab)" aria-label="Student preview">
                <i class="bx bx-show text-base"></i><span class="hidden whitespace-nowrap lg:inline">Preview</span>
            </button>
            <button type="button" data-tour="publish" @click="toggleStatus()"
                    :class="status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                    class="inline-flex h-8 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 text-[13px] font-medium transition {{ $focus }}"
                    :title="status === 'active' ? 'Students can use this form. Click to make it private.' : 'Hidden from students. Click to publish.'">
                <i class="bx text-base" :class="status === 'active' ? 'bx-globe' : 'bx-lock-alt'"></i>
                <span class="hidden sm:inline" x-text="status === 'active' ? 'Public' : 'Private'"></span>
            </button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
        {{-- ===================== LEFT: LAYERS ===================== --}}
        <aside class="flex-col bg-white md:static md:z-auto md:flex md:h-auto md:w-60 md:shrink-0 md:rounded-none md:border-r md:border-t-0 md:border-slate-200 md:shadow-none"
               :class="mobilePanel === 'layers' ? 'fixed inset-x-0 bottom-0 z-40 flex h-[70dvh] rounded-t-2xl border-t border-slate-200 shadow-2xl' : 'hidden'" aria-label="Layers" data-tour="layers">
            <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-slate-300 md:hidden" aria-hidden="true"></div>
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h2 class="text-[13px] font-semibold text-slate-900">Layers <span class="ml-1 font-normal tabular-nums text-slate-400" x-text="fields.length"></span></h2>
                <button type="button" @click="sel = fields.map(f => f.id)" class="text-xs font-medium text-[#2A57B4] hover:underline" x-show="fields.length">Select all</button>
            </div>
            <div class="px-3 pt-3">
                <div class="relative">
                    <i class="bx bx-search pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" x-model="layerFilter" placeholder="Find a field" aria-label="Find a field" class="{{ $input }} pl-8">
                </div>
            </div>
            <div class="px-3 pt-2">
                <label class="sr-only" for="layer-group">Group</label>
                <div class="relative">
                    <i class="bx bx-collection pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <select id="layer-group" class="{{ $input }} pl-8" :value="layerGroup" @change="pickGroup($event.target.value)" title="Show and select one group">
                        <option value="">All fields</option>
                        <template x-for="g in groups" :key="g">
                            <option :value="g" :selected="layerGroup === g" x-text="g + ' (' + groupCount(g) + ')'"></option>
                        </template>
                        <option value="__none" :selected="layerGroup === '__none'" x-text="'Not in a group (' + groupCount('__none') + ')'"></option>
                    </select>
                </div>
            </div>
            <ul class="min-h-0 flex-1 overflow-y-auto px-2 py-2" role="listbox" aria-multiselectable="true">
                <template x-for="f in layers" :key="f.id">
                    <li role="option" :aria-selected="isSel(f.id)" draggable="true"
                        @dragstart="layerDragStart($event, f.id)" @dragover.prevent="layerDragOver($event, f.id)" @drop.prevent="layerDrop()" @dragend="layerDragEnd()"
                        @click="($event.shiftKey || $event.ctrlKey || $event.metaKey) ? (sel = isSel(f.id) ? sel.filter(s => s !== f.id) : [...sel, f.id]) : (sel = [f.id])"
                        :class="[isSel(f.id) ? 'bg-[#e2e9f7] text-[#1E4090]' : 'text-slate-700 hover:bg-slate-50', layerDrag && layerDrag.includes(f.id) ? 'opacity-40' : '']"
                        class="group relative flex cursor-grab items-center gap-2 rounded-lg px-2 py-1.5 active:cursor-grabbing">
                        <span x-show="layerOver && layerOver.id === f.id" class="pointer-events-none absolute inset-x-1 h-0.5 rounded-full bg-[#2A57B4]" :class="layerOver?.after ? '-bottom-px' : '-top-px'"></span>
                        <i class="bx bx-grid-vertical -ml-1 text-sm text-slate-300 opacity-0 group-hover:opacity-100" aria-hidden="true"></i>
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded" :class="types[f.type]?.tint"><i class="bx text-sm" :class="f.source_type === 'system' ? 'bx-bolt-circle' : types[f.type]?.icon"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-medium" x-text="displayLabel(f)"></span>
                            <span class="block truncate font-mono text-[11px] text-slate-400" x-text="varName(f)"></span>
                        </span>
                        <span x-show="f.required" class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500" title="Required"></span>
                    </li>
                </template>
                <li x-show="!layers.length" class="px-3 py-8 text-center text-xs text-slate-400" x-text="fields.length ? 'No field matches.' : 'No fields yet.'"></li>
            </ul>
            <div class="flex items-center justify-between gap-2 border-t border-slate-200 px-3 py-2 text-xs text-slate-500">
                <span>Drag to reorder · top is in front</span>
                <span class="flex gap-0.5">
                    <button type="button" @click="moveLayer('up')" :disabled="!sel.length" class="grid h-7 w-7 place-items-center rounded-md hover:bg-slate-100 disabled:opacity-35" title="Bring forward" aria-label="Bring forward"><i class="bx bx-chevron-up text-base"></i></button>
                    <button type="button" @click="moveLayer('down')" :disabled="!sel.length" class="grid h-7 w-7 place-items-center rounded-md hover:bg-slate-100 disabled:opacity-35" title="Send backward" aria-label="Send backward"><i class="bx bx-chevron-down text-base"></i></button>
                </span>
            </div>
        </aside>

        {{-- ===================== CANVAS ===================== --}}
        <main x-ref="viewport" data-tour="canvas" class="relative min-w-0 flex-1 touch-none select-none overflow-hidden outline-none [background-image:radial-gradient(#cdd3dc_1px,transparent_1px)] [background-size:18px_18px]"
              :class="viewportCursor"
              @pointerdown="onPointerDown($event)" @wheel.prevent="onWheel($event)" @contextmenu.prevent="onContextMenu($event)"
              aria-label="Page canvas">

            {{-- The page (scaled) --}}
            <div x-ref="page" class="absolute left-0 top-0 origin-top-left bg-white shadow-[0_1px_3px_rgba(15,23,42,0.12),0_12px_32px_-12px_rgba(15,23,42,0.35)]"
                 :style="`width:${page.width}px;height:${page.height}px;transform:translate(${panX}px,${panY}px) scale(${zoom})`">
                <img x-show="imageUrl" :src="imageUrl" alt="" draggable="false" class="pointer-events-none absolute inset-0 h-full w-full select-none">
                <template x-if="!imageUrl">
                    <div class="absolute inset-0 grid place-items-center text-sm text-slate-400">No background yet. Add one in Page on the right.</div>
                </template>

                <template x-for="f in fields" :key="f.id">
                    <div :data-field="f.id"
                         class="absolute overflow-hidden"
                         :class="isSel(f.id) ? 'bg-[#2A57B4]/[0.07]' : 'bg-[#2A57B4]/[0.035] hover:bg-[#2A57B4]/[0.08]'"
                         :style="fieldStyle(f) + `outline:${(isSel(f.id) ? 0 : 1) / zoom}px dashed rgba(42,87,180,0.45);outline-offset:0;cursor:${tool === 'select' && !spaceDown ? 'move' : 'inherit'}`">
                        <div class="pointer-events-none w-full overflow-hidden px-2 py-1" :class="hasPreviewValue(f) ? '' : 'opacity-45'" :style="textStyle(f)" x-text="previewText(f)"></div>
                    </div>
                </template>
            </div>

            {{-- Screen-space overlay: selection, handles, guides, marquee (constant size at any zoom) --}}
            <div class="pointer-events-none absolute inset-0">
                <template x-for="f in selected" :key="'sel-' + f.id">
                    <div class="absolute border-[1.5px] border-[#2A57B4]"
                         :style="`left:${panX + f.x * zoom}px;top:${panY + f.y * zoom}px;width:${f.width * zoom}px;height:${f.height * zoom}px`"></div>
                </template>

                <template x-if="sel.length > 1 && bbox()">
                    <div class="absolute border border-dashed border-[#2A57B4]"
                         :style="`left:${panX + bbox().x * zoom - 4}px;top:${panY + bbox().y * zoom - 4}px;width:${bbox().w * zoom + 8}px;height:${bbox().h * zoom + 8}px`">
                        <span class="absolute -top-6 left-0 whitespace-nowrap rounded-md bg-[#2A57B4] px-1.5 py-0.5 text-[11px] font-medium text-white" x-text="sel.length + ' fields'"></span>
                    </div>
                </template>

                <template x-if="one && !gesture">
                    <div class="absolute" :style="`left:${panX + one.x * zoom}px;top:${panY + one.y * zoom}px;width:${one.width * zoom}px;height:${one.height * zoom}px`">
                        <span class="absolute bottom-full left-0 mb-1.5 inline-flex max-w-[22rem] items-center gap-1 whitespace-nowrap rounded-md bg-[#2A57B4] px-1.5 py-0.5 text-[11px] font-medium text-white shadow-sm">
                            <i class="bx text-xs" :class="one.source_type === 'system' ? 'bx-bolt-circle' : types[one.type]?.icon"></i>
                            <span class="truncate" x-text="displayLabel(one)"></span>
                            <span x-show="one.required" class="text-white/80">· required</span>
                        </span>
                        <template x-for="h in ['nw','n','ne','e','se','s','sw','w']" :key="h">
                            <span :data-handle="h" class="pointer-events-auto absolute h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-[2px] border-[1.5px] border-[#2A57B4] bg-white shadow-sm"
                                  :style="{
                                      left: h.includes('w') ? '0%' : (h.includes('e') ? '100%' : '50%'),
                                      top: h.includes('n') ? '0%' : (h.includes('s') ? '100%' : '50%'),
                                      cursor: h + '-resize'
                                  }"></span>
                        </template>
                    </div>
                </template>

                <template x-if="gesture && one && (gesture.kind === 'move' || gesture.kind === 'resize')">
                    <span class="absolute whitespace-nowrap rounded-md bg-slate-900 px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-white"
                          :style="`left:${panX + one.x * zoom}px;top:${panY + (one.y + one.height) * zoom + 8}px`"
                          x-text="gesture.kind === 'move' ? `X ${one.x}  Y ${one.y}` : `${one.width} × ${one.height}`"></span>
                </template>

                <template x-for="gx in guides.x" :key="'gx' + gx"><div class="absolute bottom-0 top-0 w-px bg-rose-500" :style="`left:${panX + gx * zoom}px`"></div></template>
                <template x-for="gy in guides.y" :key="'gy' + gy"><div class="absolute left-0 right-0 h-px bg-rose-500" :style="`top:${panY + gy * zoom}px`"></div></template>

                <template x-if="marquee">
                    <div class="absolute border border-[#2A57B4] bg-[#2A57B4]/10"
                         :style="`left:${panX + marquee.x * zoom}px;top:${panY + marquee.y * zoom}px;width:${marquee.w * zoom}px;height:${marquee.h * zoom}px`"></div>
                </template>
            </div>

            {{-- First-run hint --}}
            <div x-show="!fields.length" x-cloak class="pointer-events-none absolute inset-x-0 bottom-6 flex justify-center">
                <p class="rounded-xl bg-slate-900/85 px-4 py-2.5 text-[13px] text-white shadow-lg">Add a field from the toolbar, then drag it onto the blank on the form.</p>
            </div>

            {{-- Right-click menu --}}
            <div x-show="menu" x-cloak @pointerdown.stop class="absolute z-40 w-52 rounded-xl border border-slate-200 bg-white p-1 text-[13px] shadow-xl"
                 :style="menu ? `left:${Math.max(4, Math.min(menu.x, $refs.viewport.clientWidth - 216))}px;top:${Math.max(4, Math.min(menu.y, $refs.viewport.clientHeight - 360))}px` : ''" role="menu">
                @php $mi = 'flex w-full items-center justify-between gap-3 rounded-lg px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50 disabled:text-slate-300 disabled:hover:bg-transparent'; @endphp
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="cut(); menu = null">Cut <kbd class="text-[11px] text-slate-400">Ctrl X</kbd></button>
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="copy(); menu = null">Copy <kbd class="text-[11px] text-slate-400">Ctrl C</kbd></button>
                <button type="button" class="{{ $mi }}" @click="paste(menu.at); menu = null">Paste here <kbd class="text-[11px] text-slate-400">Ctrl V</kbd></button>
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="duplicate(); menu = null">Duplicate <kbd class="text-[11px] text-slate-400">Ctrl D</kbd></button>
                <div class="my-1 h-px bg-slate-100"></div>
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="openGroupDialog()"><span class="flex items-center gap-2"><i class="bx bx-collection text-base text-slate-400"></i>Group…</span> <kbd class="text-[11px] text-slate-400">Ctrl G</kbd></button>
                <button type="button" class="{{ $mi }}" :disabled="!selected.some(f => f.group_name)" @click="ungroup()"><span class="flex items-center gap-2"><i class="bx bx-collection text-base text-slate-300"></i>Remove from group</span> <kbd class="text-[11px] text-slate-400">Ctrl ⇧ G</kbd></button>
                <div class="my-1 h-px bg-slate-100"></div>
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="order('front'); menu = null">Bring to front</button>
                <button type="button" class="{{ $mi }}" :disabled="!sel.length" @click="order('back'); menu = null">Send to back</button>
                <button type="button" class="{{ $mi }}" @click="sel = fields.map(f => f.id); menu = null">Select all <kbd class="text-[11px] text-slate-400">Ctrl A</kbd></button>
                <div class="my-1 h-px bg-slate-100"></div>
                <button type="button" class="{{ $mi }} !text-rose-600" :disabled="!sel.length" @click="remove(); menu = null">Delete <kbd class="text-[11px] text-rose-300">Del</kbd></button>
            </div>


            {{-- Toast --}}
            <div x-show="toast" x-cloak x-transition.opacity class="pointer-events-none absolute inset-x-0 bottom-6 flex justify-center">
                <p class="rounded-xl bg-slate-900 px-4 py-2 text-[13px] font-medium text-white shadow-lg" x-text="toast"></p>
            </div>
        </main>

        {{-- ===================== RIGHT: DESIGN / INSTRUCTIONS ===================== --}}
        <aside class="flex-col bg-white md:static md:z-auto md:flex md:h-auto md:w-72 md:shrink-0 md:rounded-none md:border-l md:border-t-0 md:border-slate-200 md:shadow-none"
               :class="mobilePanel === 'design' || mobilePanel === 'steps' ? 'fixed inset-x-0 bottom-0 z-40 flex h-[70dvh] rounded-t-2xl border-t border-slate-200 shadow-2xl' : 'hidden'" aria-label="Properties" data-tour="design">
            <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-slate-300 md:hidden" aria-hidden="true"></div>
            <div class="flex shrink-0 gap-1 border-b border-slate-200 px-2" role="tablist">
                <button type="button" role="tab" @click="rightTab = 'design'" :aria-selected="rightTab === 'design'"
                        :class="rightTab === 'design' ? 'border-[#2A57B4] text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="-mb-px border-b-2 px-2.5 py-3 text-[13px] font-semibold {{ $focus }}">Design</button>
                <button type="button" role="tab" @click="rightTab = 'steps'" :aria-selected="rightTab === 'steps'" data-tour="steps"
                        :class="rightTab === 'steps' ? 'border-[#2A57B4] text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="-mb-px border-b-2 px-2.5 py-3 text-[13px] font-semibold {{ $focus }}">Instructions</button>
            </div>

            <div x-show="rightTab === 'design'" class="min-h-0 flex-1 overflow-y-auto">

                {{-- ── Nothing selected: the page ── --}}
                <template x-if="!sel.length">
                    <div>
                        <div class="{{ $section }}">
                            <h3 class="{{ $sectionTitle }}">Page</h3>
                            <div class="flex items-center gap-3">
                                <div class="h-20 w-[3.6rem] shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                    <img x-show="imageUrl" :src="imageUrl" alt="" class="h-full w-full object-cover object-top">
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium text-slate-900">Background</p>
                                    <p class="text-xs text-slate-500">The form itself: an image, or a page of a PDF.</p>
                                    <button type="button" @click="$refs.bgInput.click()" class="mt-2 inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <i class="bx bx-image-add text-sm"></i> Replace
                                    </button>
                                </div>
                            </div>
                            <input x-ref="bgInput" type="file" accept="image/png,image/jpeg,image/webp,application/pdf,.pdf" class="hidden" @change="pickBackground($event)">
                        </div>
                        <div class="{{ $section }}">
                            <h3 class="{{ $sectionTitle }}">About this form</h3>
                            <textarea rows="3" maxlength="300" class="{{ $input }} h-auto resize-none py-1.5" :value="description" @input="saveDescription($event.target.value)"
                                      placeholder="e.g. Permission from a parent or guardian for an off-campus activity."></textarea>
                            <p class="mt-1 text-[11px] text-slate-400">Students see this under the form's name when they pick a form.</p>
                        </div>
                        <div class="{{ $section }}">
                            <h3 class="{{ $sectionTitle }}">Paper</h3>
                            <label class="{{ $label }}">Size</label>
                            <select class="{{ $input }}" :value="paper.size" @change="$event.target.value !== 'Custom' && setPaper($event.target.value, paper.orientation)">
                                <option value="A4">A4 · 21 × 29.7 cm</option>
                                <option value="Letter">Letter · 8.5 × 11 in</option>
                                <option value="Legal">Legal · 8.5 × 14 in</option>
                                <option value="A3">A3 · 29.7 × 42 cm</option>
                                <template x-if="paper.size === 'Custom'"><option value="Custom">Custom</option></template>
                            </select>
                            <p x-show="paper.size === 'Custom'" class="mt-1 text-xs text-slate-500" x-text="`${paper.width} × ${paper.height} cm`"></p>
                            <label class="{{ $label }} mt-3">Orientation</label>
                            <div class="{{ $seg }}">
                                <button type="button" @click="setPaper(paper.size, 'portrait', paper.width, paper.height)" :class="paper.orientation === 'portrait' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}"><i class="bx bx-rectangle rotate-90 text-sm"></i>Portrait</button>
                                <button type="button" @click="setPaper(paper.size, 'landscape', paper.width, paper.height)" :class="paper.orientation === 'landscape' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}"><i class="bx bx-rectangle text-sm"></i>Landscape</button>
                            </div>
                        </div>
                        <div class="{{ $section }} border-b-0">
                            <h3 class="{{ $sectionTitle }}">Shortcuts</h3>
                            <dl class="space-y-1.5 text-xs text-slate-600">
                                @foreach ([
                                    ['Select several', 'Shift + click, or drag on the paper'],
                                    ['Copy · paste · duplicate', 'Ctrl C · Ctrl V · Ctrl D'],
                                    ['Undo · redo', 'Ctrl Z · Ctrl Shift Z'],
                                    ['Nudge', 'Arrows (Shift for 10 px)'],
                                    ['Pan', 'Space + drag, or scroll'],
                                    ['Scroll sideways', 'Shift + scroll, or ← → with nothing selected'],
                                    ['Group · ungroup', 'Ctrl G · Ctrl Shift G, or right-click'],
                                    ['Zoom', 'Ctrl + scroll · Ctrl 0 to fit'],
                                    ['Move without snapping', 'Hold Alt'],
                                ] as [$what, $keys])
                                    <div class="flex justify-between gap-3"><dt>{{ $what }}</dt><dd class="text-right text-slate-400">{{ $keys }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </template>

                {{-- ── One or more fields ── --}}
                <template x-if="sel.length">
                    <div>
                        {{-- Heading --}}
                        <div class="{{ $section }} flex items-center gap-3">
                            <template x-if="one">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg" :class="types[one.type]?.tint"><i class="bx text-lg" :class="one.source_type === 'system' ? 'bx-bolt-circle' : types[one.type]?.icon"></i></span>
                            </template>
                            <template x-if="!one">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#e2e9f7] text-[#1E4090]"><i class="bx bx-select-multiple text-lg"></i></span>
                            </template>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-semibold text-slate-900" x-text="one ? displayLabel(one) : (sel.length + ' fields selected')"></p>
                                <p class="truncate text-xs text-slate-400" x-text="one ? (types[one.type]?.label + (one.source_type === 'system' ? ' · from the profile' : ' · ' + varName(one))) : 'Changes apply to all of them'"></p>
                            </div>
                            <button type="button" @click="remove()" class="{{ $iconBtn }} hover:!bg-rose-50 hover:!text-rose-600" title="Delete (Del)" aria-label="Delete"><i class="bx bx-trash text-base"></i></button>
                        </div>

                        {{-- Arrange --}}
                        <div class="{{ $section }}">
                            <h3 class="{{ $sectionTitle }}" x-text="sel.length > 1 ? 'Align' : 'Align to page'"></h3>
                            <div class="grid grid-cols-6 gap-1">
                                @foreach ([['left', 'bx-align-left', 'Left'], ['center', 'bx-align-middle', 'Centre'], ['right', 'bx-align-right', 'Right'], ['top', 'bx-vertical-top', 'Top'], ['middle', 'bx-vertical-center', 'Middle'], ['bottom', 'bx-vertical-bottom', 'Bottom']] as [$edge, $icon, $title])
                                    <button type="button" @click="align('{{ $edge }}')" class="grid h-8 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900" title="Align {{ strtolower($title) }}"><i class="bx {{ $icon }} text-base"></i></button>
                                @endforeach
                            </div>
                            <div x-show="sel.length > 1" class="mt-2 grid grid-cols-2 gap-1">
                                <button type="button" @click="distribute('x')" :disabled="sel.length < 3" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40" title="Equal gaps across (3 or more)"><i class="bx bx-dots-horizontal text-base"></i>Space across</button>
                                <button type="button" @click="distribute('y')" :disabled="sel.length < 3" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40" title="Equal gaps down (3 or more)"><i class="bx bx-dots-vertical text-base"></i>Space down</button>
                                <button type="button" @click="matchSize('w')" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50" title="Same width as the first selected"><i class="bx bx-move-horizontal text-base"></i>Same width</button>
                                <button type="button" @click="matchSize('h')" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50" title="Same height as the first selected"><i class="bx bx-move-vertical text-base"></i>Same height</button>
                            </div>
                        </div>

                        {{-- Position and size (one field) --}}
                        <template x-if="one">
                            <div class="{{ $section }}">
                                <h3 class="{{ $sectionTitle }}">Position and size</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ([['x', 'X'], ['y', 'Y'], ['width', 'W'], ['height', 'H']] as [$key, $short])
                                        <label class="relative block">
                                            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400">{{ $short }}</span>
                                            <input type="number" :value="Math.round(one.{{ $key }})" @change="setNumber('{{ $key }}', $event.target.value, {{ in_array($key, ['width', 'height']) ? 4 : 'null' }})" class="{{ $input }} pl-7 tabular-nums">
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </template>

                        {{-- Field: what it asks and where the answer comes from (one field) --}}
                        <template x-if="one">
                            <div class="{{ $section }}">
                                <h3 class="{{ $sectionTitle }}">Field</h3>
                                <label class="{{ $label }}">Label shown to students</label>
                                <input type="text" class="{{ $input }}" :value="displayLabel(one)" @input="set('text', $event.target.value)" placeholder="e.g. Name of parent or guardian">

                                <label class="{{ $label }} mt-3">Type</label>
                                <div class="{{ $seg }}" :class="isLocked(one) ? 'pointer-events-none opacity-50' : ''">
                                    <template x-for="(t, key) in types" :key="key">
                                        <button type="button" @click="setType(key)" :class="one.type === key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}" :title="t.label"><i class="bx text-sm" :class="t.icon"></i></button>
                                    </template>
                                </div>

                                <template x-if="one.source_type === 'system'">
                                    <p class="mt-3 flex items-start gap-2 rounded-lg bg-[#fef3c7] px-3 py-2 text-xs text-[#8a5a00]">
                                        <i class="bx bx-bolt-circle mt-px text-sm" aria-hidden="true"></i>
                                        <span>Filled in for the student from <span class="font-semibold" x-text="isLocked(one) ? 'the current term' : 'their profile'"></span>: <span class="font-semibold" x-text="systemLabel(one.system_key)"></span>. Add others from <span class="font-semibold">Automatic</span> in the toolbar.</span>
                                    </p>
                                </template>
                                <template x-if="one.source_type !== 'system'">
                                    <div class="mt-3">
                                        <label class="{{ $label }}">Variable name</label>
                                        <input type="text" class="{{ $input }} font-mono" :value="one.name" @change="set('name', $event.target.value.trim().replace(/[^A-Za-z0-9_]+/g, '_') || uniqueName(one.text))" spellcheck="false">
                                        <p class="mt-1 text-[11px] text-slate-400">The answer is stored under this name. Keep it unique on this form.</p>
                                    </div>
                                </template>

                                <template x-if="one.type === 'date' && one.source_type !== 'system'">
                                    <div class="mt-3">
                                        <label class="{{ $label }}">Date</label>
                                        <div class="{{ $seg }}">
                                            <button type="button" @click="set('date_mode', 'current')" :class="one.date_mode !== 'user' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}">Today's date</button>
                                            <button type="button" @click="set('date_mode', 'user')" :class="one.date_mode === 'user' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}">Student picks</button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="one.source_type !== 'system' && one.type !== 'date'">
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <label class="block" x-show="one.type !== 'paragraph'"><span class="{{ $label }}">Max characters</span>
                                            <input type="number" min="1" class="{{ $input }}" :value="one.max_length ?? ''" placeholder="No limit" @change="set('max_length', $event.target.value ? Math.max(1, +$event.target.value) : null)"></label>
                                        <label class="block" x-show="one.type === 'paragraph'"><span class="{{ $label }}">Max lines</span>
                                            <input type="number" min="1" class="{{ $input }}" :value="one.max_lines ?? ''" placeholder="No limit" @change="set('max_lines', $event.target.value ? Math.max(1, +$event.target.value) : null)"></label>
                                    </div>
                                </template>

                                <template x-if="one.source_type !== 'system' && !(one.type === 'date' && one.date_mode !== 'user')">
                                    <div class="mt-3">
                                        <label class="{{ $label }}">Sample text <span class="font-normal text-slate-400">(preview only)</span></label>
                                        <textarea rows="2" class="{{ $input }} h-auto resize-none py-1.5" :value="one.placeholder ?? ''" :maxlength="one.max_length || null"
                                                  :inputmode="one.type === 'number' ? 'numeric' : null"
                                                  @input="if (one.type === 'number') $event.target.value = $event.target.value.replace(/[^0-9]/g, ''); set('placeholder', $event.target.value || null)"
                                                  :placeholder="one.type === 'number' ? 'Numbers only, e.g. 2100123' : 'Type to see how an answer fits'"></textarea>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Rules: required and group (one or many) --}}
                        <div class="{{ $section }}">
                            <h3 class="{{ $sectionTitle }}">On the student's form</h3>
                            <label class="flex cursor-pointer items-center justify-between gap-3">
                                <span class="text-[13px] text-slate-700">Required</span>
                                <button type="button" role="switch" :aria-checked="selected.every(f => f.required)" @click="toggleRequired()"
                                        :class="selected.every(f => f.required) ? 'bg-[#2A57B4]' : (selected.some(f => f.required) ? 'bg-[#2A57B4]/40' : 'bg-slate-200')"
                                        class="relative h-5 w-9 shrink-0 rounded-full transition {{ $focus }}">
                                    <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition" :class="selected.every(f => f.required) ? 'translate-x-4' : ''"></span>
                                </button>
                            </label>
                            <label class="{{ $label }} mt-3">Group</label>
                            <input type="text" list="field-groups" class="{{ $input }}" :value="shared('group_name') ?? ''" :placeholder="mixed('group_name') ? 'Mixed' : 'e.g. Parent or guardian'"
                                   @change="set('group_name', $event.target.value.trim() || null)">
                            <datalist id="field-groups"><template x-for="g in groups" :key="g"><option :value="g"></option></template></datalist>
                            <p class="mt-1 text-[11px] text-slate-400">Fields in one group are asked together.</p>
                        </div>

                        {{-- Text style (one or many) --}}
                        <div class="{{ $section }} border-b-0">
                            <h3 class="{{ $sectionTitle }}">Text</h3>
                            <select class="{{ $input }}" @change="set('font_family', $event.target.value)">
                                <template x-if="mixed('font_family')"><option value="" selected disabled>Mixed</option></template>
                                <template x-for="font in ['Arial', 'Times New Roman', 'Georgia', 'Courier New']" :key="font">
                                    <option :value="font" :selected="shared('font_family') === font" x-text="font"></option>
                                </template>
                            </select>
                            <div class="mt-2 grid grid-cols-[1fr_5rem] gap-2">
                                <select class="{{ $input }}" @change="set('font_weight', $event.target.value)">
                                    <template x-if="mixed('font_weight')"><option value="" selected disabled>Mixed</option></template>
                                    <template x-for="w in [['100','Thin'],['normal','Regular'],['bold','Bold'],['italic','Italic']]" :key="w[0]">
                                        <option :value="w[0]" :selected="shared('font_weight') === w[0]" x-text="w[1]"></option>
                                    </template>
                                </select>
                                <label class="relative block">
                                    <input type="number" min="4" max="200" class="{{ $input }} pr-6 tabular-nums" :value="shared('font_size')" :placeholder="mixed('font_size') ? '–' : ''"
                                           @change="setNumber('font_size', $event.target.value, 4, 200)" aria-label="Font size">
                                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-slate-400">px</span>
                                </label>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <div class="{{ $seg }}">
                                    @foreach ([['left', 'bx-align-left'], ['center', 'bx-align-middle'], ['right', 'bx-align-right']] as [$a, $icon])
                                        <button type="button" @click="set('alignment', '{{ $a }}')" :class="shared('alignment') === '{{ $a }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }}" title="Align {{ $a }}"><i class="bx {{ $icon }} text-sm"></i></button>
                                    @endforeach
                                </div>
                                <div class="{{ $seg }}">
                                    @foreach ([['none', 'Aa', 'As typed'], ['uppercase', 'AA', 'All caps'], ['sentence', 'Aa.', 'Sentence case'], ['smallcaps', 'Sᴄ', 'Small caps']] as [$c, $txt, $title])
                                        <button type="button" @click="set('text_case', '{{ $c }}')" :class="shared('text_case') === '{{ $c }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="{{ $segBtn }} text-[11px]" title="{{ $title }}">{{ $txt }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <label class="flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-1.5 shadow-sm" title="Colour">
                                    <input type="color" class="h-5 w-5 shrink-0 cursor-pointer rounded border-0 bg-transparent p-0" :value="shared('text_color') || '#000000'" @input="set('text_color', $event.target.value, 'color')">
                                    <span class="truncate font-mono text-[11px] text-slate-500" x-text="mixed('text_color') ? 'Mixed' : (shared('text_color') || '').toUpperCase()"></span>
                                </label>
                                <label class="relative block" title="Line height">
                                    <i class="bx bx-line-chart pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                    <input type="number" step="0.1" min="0.5" max="5" class="{{ $input }} pl-7 tabular-nums" :value="shared('line_height')" @change="setNumber('line_height', $event.target.value, 0.5, 5)" aria-label="Line height">
                                </label>
                                <label class="relative block" title="Letter spacing">
                                    <i class="bx bx-font-size pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                    <input type="number" step="0.5" min="-10" max="40" class="{{ $input }} pl-7 tabular-nums" :value="shared('letter_spacing')" @change="setNumber('letter_spacing', $event.target.value, -10, 40)" aria-label="Letter spacing">
                                </label>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ── Instructions for students ── --}}
            <div x-show="rightTab === 'steps'" x-cloak class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                <p class="mb-3 text-xs text-slate-500">Steps students read before they fill in and print this form. Use the pencil to edit a step; drag the handle to reorder.</p>
                <ol class="space-y-1.5">
                    <template x-for="(s, i) in steps" :key="s.instruction_id">
                        <li class="group relative flex items-start gap-1.5 rounded-lg border bg-white p-2 shadow-sm transition"
                            :class="stepDrag === i ? 'border-[#2A57B4]/40 opacity-50' : 'border-slate-200'"
                            @dragover.prevent="stepOver = i" @drop.prevent="stepDrop(i)">
                            <span x-show="stepOver === i && stepDrag !== null && stepDrag !== i" class="pointer-events-none absolute inset-x-1 -top-1 h-0.5 rounded-full bg-[#2A57B4]"></span>
                            <span draggable="true" @dragstart="stepDrag = i; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', String(i))" @dragend="stepDrag = null; stepOver = null"
                                  class="grid h-6 w-5 shrink-0 cursor-grab place-items-center rounded text-slate-300 hover:bg-slate-100 hover:text-slate-500 active:cursor-grabbing" title="Drag to reorder" aria-hidden="true">
                                <i class="bx bx-grid-vertical text-base"></i>
                            </span>
                            <span class="mt-0.5 w-4 shrink-0 text-xs font-semibold tabular-nums text-slate-400" x-text="i + 1"></span>
                            <template x-if="stepEditing !== s.instruction_id">
                                <p class="min-w-0 flex-1 whitespace-pre-line break-words p-0.5 text-[13px] leading-5 text-slate-800" x-text="s.description" @dblclick="editStep(s)"></p>
                            </template>
                            <template x-if="stepEditing === s.instruction_id">
                                <textarea :id="'step-edit-' + s.instruction_id" rows="1" :value="s.description" aria-label="Step text"
                                          @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                          @keydown.enter.prevent="$el.blur()" @keydown.escape.prevent="stepEditing = null"
                                          @blur="saveStep(s, $el.value)"
                                          class="min-w-0 flex-1 resize-none overflow-hidden rounded border-0 bg-slate-50 p-0.5 text-[13px] leading-5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/25"></textarea>
                            </template>
                            <button type="button" @click="editStep(s)" x-show="stepEditing !== s.instruction_id"
                                    class="grid h-6 w-6 shrink-0 place-items-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-[#2A57B4]" aria-label="Edit step" title="Edit"><i class="bx bx-pencil text-sm"></i></button>
                            <button type="button" @click="removeStep(s)" class="grid h-6 w-6 shrink-0 place-items-center rounded text-slate-400 opacity-0 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100 focus:opacity-100" aria-label="Delete step"><i class="bx bx-trash text-sm"></i></button>
                        </li>
                    </template>
                </ol>
                <p x-show="!steps.length" class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-xs text-slate-400">No steps yet. Add the first one below.</p>
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-2">
                    <label for="new-step" class="sr-only">New step</label>
                    <textarea id="new-step" x-model="stepDraft" rows="2" @keydown.enter.prevent="addStep()" placeholder="e.g. Have your parent or guardian sign the bottom of the form."
                              class="{{ $input }} h-auto resize-none py-1.5"></textarea>
                    <button type="button" @click="addStep()" :disabled="!stepDraft.trim() || stepBusy"
                            class="mt-2 inline-flex h-8 w-full items-center justify-center gap-1 rounded-lg bg-[#2A57B4] text-[13px] font-medium text-white hover:bg-[#24499A] disabled:opacity-50">
                        <i class="bx text-base" :class="stepBusy ? 'bx-loader-alt animate-spin' : 'bx-plus'"></i>Add step
                    </button>
                    <p class="mt-1.5 text-center text-[11px] text-slate-400">Press Enter to add it.</p>
                </div>
            </div>
        </aside>
    </div>

    {{-- ===================== PHONES: backdrop, add sheet and bottom toolbar ===================== --}}
    <div x-show="mobilePanel" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/30 md:hidden" @click="mobilePanel = null" aria-hidden="true"></div>
    <div x-show="mobilePanel === 'add'" x-cloak class="fixed inset-x-0 bottom-0 z-40 max-h-[70dvh] overflow-y-auto rounded-t-2xl border-t border-slate-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl md:hidden" role="dialog" aria-label="Add a field">
        <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300" aria-hidden="true"></div>
        <p class="mb-2 text-[13px] font-semibold text-slate-900">Add a field</p>
        <div class="grid grid-cols-4 gap-2">
            <template x-for="(t, key) in types" :key="key">
                <button type="button" @click="add(key); mobilePanel = null" class="flex flex-col items-center gap-1 rounded-xl border border-slate-200 py-3 text-xs font-medium text-slate-700 active:bg-slate-50">
                    <span class="grid h-8 w-8 place-items-center rounded-lg" :class="t.tint"><i class="bx text-lg" :class="t.icon"></i></span><span x-text="t.label"></span>
                </button>
            </template>
        </div>
        <template x-for="g in automaticGroups" :key="'m' + g.title">
            <div class="mt-3">
                <p class="mb-1 text-[11px] font-semibold text-slate-400" x-text="g.title"></p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="it in g.items" :key="it.key">
                        <button type="button" @click="add('sys:' + it.key); mobilePanel = null" class="inline-flex h-9 items-center gap-1 rounded-lg bg-[#fef3c7] px-2.5 text-xs font-medium text-[#8a5a00]"><i class="bx bx-bolt-circle"></i><span x-text="it.label"></span></button>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <nav class="grid h-14 shrink-0 grid-cols-5 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Editor tools">
        @foreach ([['layers', 'bx-layer', 'Layers'], ['add', 'bx-plus-circle', 'Add'], ['design', 'bx-slider-alt', 'Design'], ['steps', 'bx-list-ol', 'Steps']] as [$k, $icon, $lbl])
            <button type="button" @click="mobilePanel = mobilePanel === '{{ $k }}' ? null : '{{ $k }}'; if (mobilePanel === 'design' || mobilePanel === 'steps') rightTab = mobilePanel === 'steps' ? 'steps' : 'design'"
                    :class="mobilePanel === '{{ $k }}' ? 'text-[#2A57B4]' : 'text-slate-500'" class="flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium">
                <i class="bx {{ $icon }} text-xl"></i>{{ $lbl }}
            </button>
        @endforeach
        <button type="button" @click="fit()" class="flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-slate-500"><i class="bx bx-expand text-xl"></i><span x-text="Math.round(zoom * 100) + '%'"></span></button>
    </nav>

    {{-- ===================== TOUR ===================== --}}
    <template x-if="tour !== null">
        <div class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-labelledby="tour-title">
            {{-- The spotlight: everything but the highlighted part is dimmed --}}
            <div class="absolute rounded-xl transition-all duration-300 ease-out"
                 :style="tourRect ? `left:${tourRect.x - 6}px;top:${tourRect.y - 6}px;width:${tourRect.w + 12}px;height:${tourRect.h + 12}px;box-shadow:0 0 0 9999px rgba(15,23,42,.55);outline:2px solid rgba(255,255,255,.9)` : 'inset:0;background:rgba(15,23,42,.55)'"></div>
            <div class="absolute rounded-2xl bg-white p-4 shadow-2xl transition-all duration-300 ease-out" :style="tourCardStyle">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium tabular-nums text-[#2A57B4]" x-text="`Step ${tour + 1} of ${tourSteps.length}`"></span>
                    <button type="button" @click="endTour()" class="text-xs font-medium text-slate-500 hover:text-slate-800">Skip tour</button>
                </div>
                <h2 id="tour-title" class="mt-1.5 text-base font-semibold text-slate-900" x-text="tourSteps[tour].title"></h2>
                <p class="mt-1 text-[13px] leading-relaxed text-slate-600" x-text="tourSteps[tour].text"></p>
                <div class="mt-4 flex items-center justify-between gap-2">
                    <span class="flex gap-1" aria-hidden="true">
                        <template x-for="(st, i) in tourSteps" :key="i"><span class="h-1.5 rounded-full transition-all" :class="i === tour ? 'w-4 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"></span></template>
                    </span>
                    <span class="flex gap-2">
                        <button type="button" x-show="tour > 0" @click="tourBack()" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-3 text-[13px] font-medium text-slate-700 hover:bg-slate-50">Back</button>
                        <button type="button" @click="tourNext()" x-init="$nextTick(() => $el.focus())" class="inline-flex h-8 items-center rounded-lg bg-[#2A57B4] px-3.5 text-[13px] font-medium text-white hover:bg-[#24499A]"
                                x-text="tour === tourSteps.length - 1 ? 'Done' : 'Next'"></button>
                    </span>
                </div>
            </div>
        </div>
    </template>

    {{-- ===================== GROUP ===================== --}}
    <div x-show="groupDialog" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4" @keydown.escape.window="groupDialog = null" @click.self="groupDialog = null">
        <form class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="group-title" @submit.prevent="applyGroup()">
            <h2 id="group-title" class="text-base font-semibold text-slate-900">Group <span x-text="sel.length"></span> <span x-text="sel.length === 1 ? 'field' : 'fields'"></span></h2>
            <p class="mt-1 text-[13px] text-slate-500">Fields in one group are asked together, under the group's name, on the student's form.</p>
            <label for="group-name" class="mb-1 mt-4 block text-xs font-medium text-slate-500">Group name</label>
            <input id="group-name" type="text" list="field-groups" class="{{ $input }}" placeholder="e.g. Parent or guardian" :value="groupDialog?.name || ''"
                   @input="groupDialog && (groupDialog.name = $event.target.value)">
            <template x-if="groups.length">
                <div class="mt-2 flex flex-wrap gap-1">
                    <template x-for="g in groups" :key="g">
                        <button type="button" @click="groupDialog.name = g" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 hover:bg-[#e2e9f7] hover:text-[#1E4090]" x-text="g"></button>
                    </template>
                </div>
            </template>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="groupDialog = null" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" :disabled="!(groupDialog?.name || '').trim()" class="inline-flex h-9 items-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] disabled:opacity-50">Group</button>
            </div>
        </form>
    </div>

    {{-- ===================== PDF PAGE PICKER ===================== --}}
    <div x-show="pdf || pdfBusy || pdfError" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4" @keydown.escape.window="closePdf(); pdfError = ''">
        <div class="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="pdf-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 id="pdf-title" class="text-base font-semibold text-slate-900">Use a page of the PDF</h2>
                    <p class="text-[13px] text-slate-500" x-text="pdf ? `${pdf.numPages} ${pdf.numPages === 1 ? 'page' : 'pages'} · the page becomes the form's background image` : 'Reading the PDF…'"></p>
                </div>
                <button type="button" @click="closePdf(); pdfError = ''" class="{{ $iconBtn }}" aria-label="Close"><i class="bx bx-x text-xl"></i></button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <p x-show="pdfError" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-[13px] text-rose-700" x-text="pdfError"></p>
                <div x-show="pdfBusy && !pdfPages.length" class="py-10 text-center text-[13px] text-slate-500"><i class="bx bx-loader-alt animate-spin"></i> Reading the PDF…</div>
                <ul class="grid grid-cols-3 gap-4 sm:grid-cols-4">
                    <template x-for="p in pdfPages" :key="p.n">
                        <li><button type="button" @click="usePdfPage(p.n)" :disabled="pdfBusy" class="group block w-full text-center disabled:opacity-60">
                            <span class="block overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition group-hover:border-[#2A57B4] group-hover:ring-2 group-hover:ring-[#2A57B4]/20"><img :src="p.src" alt="" class="w-full"></span>
                            <span class="mt-1.5 block text-xs font-medium text-slate-600" x-text="'Page ' + p.n"></span>
                        </button></li>
                    </template>
                </ul>
                <p x-show="pdf && pdf.numPages > 24" class="mt-3 text-xs text-slate-500">Showing the first 24 pages.</p>
            </div>
            <p x-show="pdfBusy && pdfPages.length" class="border-t border-slate-200 px-5 py-3 text-[13px] text-slate-500"><i class="bx bx-loader-alt animate-spin"></i> Converting the page…</p>
        </div>
    </div>
</div>
