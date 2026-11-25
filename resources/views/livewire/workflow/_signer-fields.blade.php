{{-- Needs: $fields (WorkflowDocument::signerFields($values)). Binds to "answers.<field>" on the component.
     What the signer fills out while signing (ratings, scores, comments), as blanks on the form (App\Support\DocSheet).
     Fields that share a 'group' (e.g. one interviewee's three scores) sit together under the group's name. --}}
@php
    $D = \App\Support\DocSheet::class;
    $groups = collect($fields)->groupBy(fn ($f) => $f['group'] ?? '', preserveKeys: true);
@endphp
<div class="space-y-6">
    @foreach ($groups as $group => $groupFields)
        @if ($group !== '')
            <fieldset wire:key="sg-{{ md5($group) }}">
                <legend class="text-[15px] font-semibold text-slate-900">{{ $loop->iteration }}. {{ $group }}</legend>
                <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
        @else
            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
        @endif

        @foreach ($groupFields as $name => $f)
            @php
                $type = $f['type'] ?? 'text';
                $label = $group !== '' ? trim(\Illuminate\Support\Str::after($f['label'], '—')) : $f['label'];
                $required = (bool) ($f['required'] ?? false);
                $hasError = $errors->has("answers.$name");
                $describedBy = $hasError ? "sfe-$name" : '';
                $aria = ($describedBy ? 'aria-describedby="' . e($describedBy) . '" ' : '') . ($hasError ? 'aria-invalid="true" ' : '') . ($required ? 'aria-required="true"' : '');
                $req = $required ? '<span class="not-italic text-rose-600" aria-hidden="true"> *</span><span class="sr-only">(required)</span>' : '';
                $wide = $group === '' && in_array($type, ['radio', 'textarea'], true);
            @endphp
            <div wire:key="sf-{{ $name }}" class="group/f min-w-0 {{ $wide ? 'sm:col-span-2' : '' }}">
                @if ($type === 'radio')
                    <p id="sfl-{{ $name }}" class="text-[15px] text-slate-800">{{ $label }}{!! $req !!}</p>
                    <div class="mt-1.5 grid grid-cols-5 gap-1.5 {{ $hasError ? 'rounded-lg bg-rose-50 p-1' : '' }}" role="radiogroup" aria-labelledby="sfl-{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                        @foreach ($f['options'] as $opt)
                            <label class="flex min-h-11 cursor-pointer flex-col items-center justify-center rounded-lg border border-slate-200 px-1 py-1.5 text-center transition-colors hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-[#2A57B4] has-[:checked]:text-white has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/30">
                                <input type="radio" name="answers-{{ $name }}" wire:model="answers.{{ $name }}" value="{{ $opt }}" class="sr-only">
                                <span class="text-sm font-semibold">{{ $opt }}</span>
                                @if (! empty($f['option_labels'][$opt]))
                                    <span class="text-[10px] leading-tight opacity-80">{{ $f['option_labels'][$opt] }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @elseif ($type === 'textarea')
                    <textarea id="sf-{{ $name }}" wire:model="answers.{{ $name }}" rows="3" maxlength="{{ $f['max'] ?? 500 }}"
                              class="{{ $D::LINED }} {{ $hasError ? 'bg-rose-50/60' : '' }}" {!! $aria !!}></textarea>
                    <label for="sf-{{ $name }}" class="{{ $D::CAPTION }}">{{ $label }}{!! $req !!}</label>
                @else
                    <div class="{{ $D::LINE }} {{ $hasError ? $D::ERROR_LINE : '' }} {{ $type === 'number' ? 'max-w-[9rem]' : '' }}">
                        @if ($type === 'select')
                            <select id="sf-{{ $name }}" wire:model="answers.{{ $name }}" class="{{ $D::INPUT }} cursor-pointer pr-8" {!! $aria !!}>
                                <option value="">Choose…</option>
                                @foreach ($f['options'] as $opt)
                                    <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                                @endforeach
                            </select>
                        @elseif ($type === 'number')
                            <input id="sf-{{ $name }}" type="number" inputmode="numeric" wire:model="answers.{{ $name }}"
                                   @isset($f['min']) min="{{ $f['min'] }}" @endisset @isset($f['max_value']) max="{{ $f['max_value'] }}" @endisset
                                   class="{{ $D::INPUT }} tabular-nums" {!! $aria !!}>
                            @isset($f['max_value'])<span class="shrink-0 pr-1 text-xs text-slate-400" aria-hidden="true">/ {{ $f['max_value'] }}</span>@endisset
                        @else
                            <input id="sf-{{ $name }}" type="text" wire:model="answers.{{ $name }}" maxlength="{{ $f['max'] ?? 120 }}" class="{{ $D::INPUT }}" {!! $aria !!}>
                        @endif
                    </div>
                    <label for="sf-{{ $name }}" class="{{ $D::CAPTION }}">{{ $label }}{!! $req !!}</label>
                @endif

                @error("answers.$name") <p id="sfe-{{ $name }}" class="{{ $D::ERROR }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
            </div>
        @endforeach

        @if ($group !== '')
                </div>
            </fieldset>
        @else
            </div>
        @endif
    @endforeach
</div>
