@php use App\Workflow\Documents\DocumentRegistry; @endphp
@if ($notices->isNotEmpty())
    <div x-data="{
            gone: (() => { try { return JSON.parse(localStorage.getItem('documate-hidden-notices') || '[]'); } catch (e) { return []; } })(),
            hide(id) { this.gone.push(id); try { localStorage.setItem('documate-hidden-notices', JSON.stringify(this.gone)); } catch (e) {} }
         }"
         class="pointer-events-none fixed right-4 top-20 z-30 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2">
        @foreach ($notices as $n)
            @php
                $nd = DocumentRegistry::get($n->document_type);
                $nv = $n->field_values ?? [];
                $rose = $nd->noticeTone() === 'rose';
            @endphp
            <div x-show="!gone.includes({{ $n->workspace_id }})" x-cloak x-transition
                 wire:key="overlay-notice-{{ $n->workspace_id }}"
                 class="pointer-events-auto relative rounded-2xl border bg-white p-3 pr-9 shadow-lg {{ $rose ? 'border-rose-200' : 'border-blue-200' }}">
                <a href="{{ route('student.transactions.show', $n->workspace_id) }}" wire:navigate class="block">
                    <p class="text-sm font-bold {{ $rose ? 'text-rose-700' : 'text-[#2A57B4]' }}">{{ $nd->noticeTitle($nv) }}</p>
                    <p class="mt-0.5 text-xs leading-snug text-slate-600">{{ $nd->noticeLine($nv) }}</p>
                </a>
                <button type="button" @click="hide({{ $n->workspace_id }})" aria-label="Dismiss"
                        class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">&times;</button>
            </div>
        @endforeach
    </div>
@endif