{{-- One field's editor on a fill-on-the-PDF page (Alpine "pdfFill", resources/js/app.js).
     When the printed page has a blank for this field (data-f="name" in the PDF template), the editor opens from
     that blank, just under it, and moves with the page as it scrolls (phones: the full width of the screen).
     Otherwise (the field is not printed, or the page is read-only) it simply sits in the list under the page.
     <x-doc.pop name="subject" :pop="$editable"> …the field… </x-doc.pop> --}}
@props(['name', 'pop' => true])
@if ($pop)
    @php $n = \Illuminate\Support\Js::from($name); @endphp
    <div data-editor="{{ $name }}"
         x-show="! placed[{{ $n }}] || active === {{ $n }}"
         x-cloak
         :class="placed[{{ $n }}] ? 'fixed z-30 max-h-[min(30rem,70dvh)] overflow-y-auto rounded-xl bg-white px-4 pt-2.5 pb-3 shadow-[0_18px_44px_-14px_rgba(13,37,61,0.45)] ring-1 ring-slate-200 md:w-[23rem] md:pt-3' : 'contents'"
         {{-- An object, not a string: a string would replace the style attribute and undo x-show's display:none --}}
         :style="placed[{{ $n }}] ? { top: pop.top + 'px', left: pop.left + 'px', width: pop.width ? pop.width + 'px' : '' } : { top: '', left: '', width: '' }"
         :role="placed[{{ $n }}] ? 'dialog' : null"
         @keydown.escape.stop="close()"
         {{ $attributes }}>
        <div x-show="placed[{{ $n }}]" class="mb-1 flex items-center justify-between gap-2">
            <span class="text-[11px] font-medium tabular-nums text-slate-400" x-text="counter({{ $n }})"></span>
            <button type="button" @click="close()" class="-mr-1.5 grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i class="bx bx-x text-xl" aria-hidden="true"></i>
            </button>
        </div>

        {{ $slot }}

        <div x-show="placed[{{ $n }}]" class="mt-3 grid grid-cols-[auto_minmax(0,1fr)] gap-2">
            <button type="button" @click="step(-1)" :disabled="! hasStep(-1)"
                    class="inline-flex min-h-11 items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 md:min-h-9">
                <i class="bx bx-chevron-left text-lg" aria-hidden="true"></i><span class="sr-only">Previous blank</span>
            </button>
            <button type="button" @click="hasStep(1) ? step(1) : close()"
                    class="inline-flex min-h-11 items-center justify-center gap-1 rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] md:min-h-9">
                <span x-text="hasStep(1) ? 'Next' : 'Done'"></span><i class="bx bx-chevron-right text-lg" aria-hidden="true" x-show="hasStep(1)"></i>
            </button>
        </div>
    </div>
@else
    {{ $slot }}
@endif
