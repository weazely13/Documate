<div wire:key="f-{{ $name }}">
    <label class="text-sm font-medium text-slate-700">
        {{ $f['label'] }} @if ($editable)<span class="text-rose-500">*</span>@endif
    </label>

    @if ($editable)
        <div class="mt-1.5 space-y-2">
            @foreach ($values[$name] as $i => $row)
                <div wire:key="slot-{{ $name }}-{{ $i }}" class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <div>
                            <label class="text-[11px] font-medium text-slate-500">Date</label>
                            <input type="date" min="{{ now()->toDateString() }}" wire:model="values.{{ $name }}.{{ $i }}.date" class="{{ $input }}">
                            @error("values.$name.$i.date") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-slate-500">Start time</label>
                            <input type="time" wire:model="values.{{ $name }}.{{ $i }}.from" class="{{ $input }}">
                            @error("values.$name.$i.from") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-slate-500">End time (optional)</label>
                            <input type="time" wire:model="values.{{ $name }}.{{ $i }}.to" class="{{ $input }}">
                            @error("values.$name.$i.to") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if (count($values[$name]) > 1)
                        <button type="button" wire:click="removeSlot('{{ $name }}', {{ $i }})"
                                class="mt-2 text-xs font-semibold text-rose-600 hover:underline">Remove this date</button>
                    @endif
                </div>
            @endforeach
        </div>

        @if (count($values[$name]) < ($f['max_slots'] ?? 10))
            <button type="button" wire:click="addSlot('{{ $name }}')"
                    class="mt-2 rounded-lg border border-dashed border-[#2A57B4]/50 px-3 py-2 text-sm font-semibold text-[#2A57B4] hover:bg-blue-50">
                + Add another date
            </button>
        @endif
        @if (! empty($f['help'])) <p class="mt-1 text-xs text-slate-400">{{ $f['help'] }}</p> @endif
        @error("values.$name") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    @else
        <ul class="mt-1 space-y-1 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-800">
            @foreach (explode("\n", $document->formatValue($f, $values[$name] ?? null)) as $line)
                <li>{{ $line ?: '—' }}</li>
            @endforeach
        </ul>
    @endif
</div>