{{-- Needs: $fields (WorkflowDocument::signerFields($values)). Binds to "answers.<field>" on the component.
     What the signer fills in while signing: ratings, scores, comments. Fields that share a 'group'
     (e.g. one interviewee's three scores) are shown together under the group's name. --}}
@php
    $control = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-base outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20 sm:text-sm';
    $groups = collect($fields)->groupBy(fn ($f) => $f['group'] ?? '', preserveKeys: true);
@endphp
<div class="space-y-4">
    @foreach ($groups as $group => $groupFields)
        @if ($group !== '')
            <fieldset class="rounded-xl border border-slate-200 p-3.5" wire:key="sg-{{ md5($group) }}">
                <legend class="px-1 text-sm font-semibold text-slate-900">{{ $loop->iteration }}. {{ $group }}</legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @endif

        @foreach ($groupFields as $name => $f)
            @php
                $label = $group !== '' ? trim(\Illuminate\Support\Str::after($f['label'], '—')) : $f['label'];
                $hasError = $errors->has("answers.$name");
                $describedBy = trim((! empty($f['help']) ? "sfh-$name " : '') . ($hasError ? "sfe-$name" : ''));
            @endphp
            <div wire:key="sf-{{ $name }}">
                @if (($f['type'] ?? 'text') === 'radio')
                    <p id="sfl-{{ $name }}" class="text-sm font-medium text-slate-700">
                        {{ $label }} @if ($f['required'] ?? false)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                    </p>
                    <div class="mt-1.5 grid grid-cols-5 gap-1.5" role="radiogroup" aria-labelledby="sfl-{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                        @foreach ($f['options'] as $opt)
                            <label class="flex min-h-[44px] cursor-pointer flex-col items-center justify-center rounded-lg border border-slate-200 px-1 py-1.5 text-center hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                <input type="radio" name="answers-{{ $name }}" wire:model="answers.{{ $name }}" value="{{ $opt }}" class="sr-only">
                                <span class="text-sm font-semibold text-slate-800">{{ $opt }}</span>
                                @if (! empty($f['option_labels'][$opt]))
                                    <span class="text-[10px] leading-tight text-slate-500">{{ $f['option_labels'][$opt] }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @else
                    <label for="sf-{{ $name }}" class="text-sm font-medium text-slate-700">
                        {{ $label }} @if ($f['required'] ?? false)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                    </label>
                    @php $aria = ($describedBy ? 'aria-describedby="' . e($describedBy) . '" ' : '') . ($hasError ? 'aria-invalid="true" ' : '') . (($f['required'] ?? false) ? 'aria-required="true"' : ''); @endphp
                    @if (($f['type'] ?? 'text') === 'select')
                        <select id="sf-{{ $name }}" wire:model="answers.{{ $name }}" class="{{ $control }}" {!! $aria !!}>
                            <option value="">Choose…</option>
                            @foreach ($f['options'] as $opt)
                                <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                            @endforeach
                        </select>
                    @elseif (($f['type'] ?? 'text') === 'textarea')
                        <textarea id="sf-{{ $name }}" wire:model="answers.{{ $name }}" rows="3" maxlength="{{ $f['max'] ?? 500 }}" class="{{ $control }}" {!! $aria !!}></textarea>
                    @elseif (($f['type'] ?? 'text') === 'number')
                        <input id="sf-{{ $name }}" type="number" inputmode="numeric" wire:model="answers.{{ $name }}"
                               @isset($f['min']) min="{{ $f['min'] }}" @endisset @isset($f['max_value']) max="{{ $f['max_value'] }}" @endisset
                               class="{{ $control }} max-w-[8rem]" {!! $aria !!}>
                    @else
                        <input id="sf-{{ $name }}" type="text" wire:model="answers.{{ $name }}" maxlength="{{ $f['max'] ?? 120 }}" class="{{ $control }}" {!! $aria !!}>
                    @endif
                @endif

                @if (! empty($f['help'])) <p id="sfh-{{ $name }}" class="mt-1 text-xs text-slate-500">{{ $f['help'] }}</p> @endif
                @error("answers.$name") <p id="sfe-{{ $name }}" class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endforeach

        @if ($group !== '')
                </div>
            </fieldset>
        @endif
    @endforeach
</div>
