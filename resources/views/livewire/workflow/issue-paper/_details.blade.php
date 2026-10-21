{{-- Needs: $student, $rows (label => value). Optional: $heading --}}
<div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5">
    <div class="flex items-center justify-between gap-2">
        <p class="text-[11px] font-bold uppercase tracking-wide text-[#2A57B4]">{{ $heading ?? "Filled in from the student's profile" }}</p>
        <span class="rounded bg-white px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 ring-1 ring-blue-100">Read-only</span>
    </div>
    @if (! $student)
        <p class="mt-2 text-sm text-slate-500">Select a student above and these fill in automatically.</p>
    @else
        <dl class="mt-2.5 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
            @foreach ($rows as $label => $value)
                <div wire:key="dt-{{ \Illuminate\Support\Str::slug($label) }}">
                    <dt class="text-[11px] font-medium text-slate-400">{{ $label }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ trim((string) $value) !== '' ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>