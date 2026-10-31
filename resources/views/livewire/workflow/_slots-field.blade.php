@php
    $slotInput = 'mt-1 block w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-base outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20 sm:py-2.5 sm:text-sm';
@endphp
<div wire:key="f-{{ $name }}">
    <fieldset id="f-{{ $name }}" tabindex="-1" @if (! empty($f['help'])) aria-describedby="h-{{ $name }}" @endif>
        <legend class="block text-[13px] font-medium text-slate-700">
            {{ $f['label'] }} @if ($editable)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
        </legend>
        @if ($editable && ! empty($f['help'])) <p id="h-{{ $name }}" class="mt-1 text-xs leading-relaxed text-slate-500">{{ $f['help'] }}</p> @endif

        @if ($editable)
            <ol class="mt-2 space-y-2">
                @foreach ($values[$name] as $i => $row)
                    <li wire:key="slot-{{ $name }}-{{ $i }}" class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-600">Date {{ $i + 1 }}</p>
                            @if (count($values[$name]) > 1)
                                <button type="button" wire:click="removeSlot('{{ $name }}', {{ $i }})"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                                        aria-label="Remove date {{ $i + 1 }}">
                                    <i class="bx bx-trash" aria-hidden="true"></i> Remove
                                </button>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <label for="slot-{{ $name }}-{{ $i }}-date" class="text-[11px] font-medium text-slate-600">Date</label>
                                <input id="slot-{{ $name }}-{{ $i }}-date" type="date" min="{{ now()->toDateString() }}" wire:model="values.{{ $name }}.{{ $i }}.date" class="{{ $slotInput }}"
                                       aria-required="true" @error("values.$name.$i.date") aria-invalid="true" @enderror>
                                @error("values.$name.$i.date") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="slot-{{ $name }}-{{ $i }}-from" class="text-[11px] font-medium text-slate-600">Start time</label>
                                <input id="slot-{{ $name }}-{{ $i }}-from" type="time" wire:model="values.{{ $name }}.{{ $i }}.from" class="{{ $slotInput }}"
                                       aria-required="true" @error("values.$name.$i.from") aria-invalid="true" @enderror>
                                @error("values.$name.$i.from") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="slot-{{ $name }}-{{ $i }}-to" class="text-[11px] font-medium text-slate-600">End time (optional)</label>
                                <input id="slot-{{ $name }}-{{ $i }}-to" type="time" wire:model="values.{{ $name }}.{{ $i }}.to" class="{{ $slotInput }}"
                                       @error("values.$name.$i.to") aria-invalid="true" @enderror>
                                @error("values.$name.$i.to") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            @if (count($values[$name]) < ($f['max_slots'] ?? 10))
                <button type="button" wire:click="addSlot('{{ $name }}')"
                        class="mt-2 inline-flex w-full items-center justify-center gap-1 rounded-lg border border-dashed border-[#2A57B4]/50 px-3 py-2.5 text-sm font-medium text-[#2A57B4] hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30">
                    <i class="bx bx-plus" aria-hidden="true"></i> Add another date
                </button>
            @endif
            @error("values.$name") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
        @else
            <ul class="mt-1 space-y-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800">
                @foreach (explode("\n", $document->formatValue($f, $values[$name] ?? null)) as $line)
                    <li>{{ $line ?: '—' }}</li>
                @endforeach
            </ul>
        @endif
    </fieldset>
</div>
