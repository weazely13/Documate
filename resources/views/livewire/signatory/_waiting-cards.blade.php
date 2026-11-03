{{-- "Waiting for your sign" on Home (students who also sign, and signing accounts), inside the sky.
     Needs: $signing (TransactionApproval collection, with workspace.user and role), $signingTotal. --}}
<section class="mx-auto {{ $width ?? 'max-w-4xl' }} text-left" aria-labelledby="home-sign-title">
    <div class="flex items-baseline justify-between gap-4">
        <h2 id="home-sign-title" class="text-base font-semibold text-slate-900 group-data-[ink=light]/greet:text-white">
            Waiting for your sign
            @if ($signingTotal)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 align-middle text-xs font-semibold tabular-nums text-amber-800">{{ $signingTotal }}</span>@endif
        </h2>
        <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:hover:text-blue-100">Signing inbox &rarr;</a>
    </div>
    @if ($signing->isNotEmpty())
        <div class="mt-3 grid grid-cols-2 gap-2.5 sm:gap-3 {{ ($width ?? '') === 'max-w-5xl' ? 'lg:grid-cols-3' : '' }}">
            @foreach ($signing as $a)
                @php
                    $who = $a->workspace->field_values['student_name'] ?? ($a->workspace->user?->full_name ?? 'Student');
                    $late = $a->opened_at && $a->opened_at->lt(now()->subDays(2));
                @endphp
                <a href="{{ route('signatory.show', $a->id) }}" wire:navigate wire:key="home-sign-{{ $a->id }}"
                   class="group flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 max-sm:flex-col max-sm:items-stretch max-sm:gap-2 max-sm:p-3 transition hover:border-slate-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                    <x-dm.avatar :user="$a->workspace->user" :name="$who" class="h-10 w-10 text-xs" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ \App\Workflow\Documents\DocumentRegistry::find($a->workspace->document_type)?->name() ?? 'Document' }}</span>
                        <span class="mt-0.5 block truncate text-xs text-slate-500">For {{ $who }} · as {{ $a->role->label ?? 'signer' }}</span>
                        <span class="mt-2 inline-flex items-center gap-1 text-xs tabular-nums {{ $late ? 'font-medium text-amber-800' : 'text-slate-500' }}">
                            <i class="bx bx-time-five text-sm" aria-hidden="true"></i>{{ $a->opened_at ? 'Waiting ' . $a->opened_at->diffForHumans(null, true) : 'Just arrived' }}
                        </span>
                    </span>
                    <i class="bx bx-chevron-right mt-2.5 text-xl text-slate-400 max-sm:!hidden" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @else
        <p class="mt-2 text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50">Nothing is waiting for your signature right now.</p>
    @endif
</section>
