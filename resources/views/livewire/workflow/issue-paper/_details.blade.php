{{-- Needs: $student, $rows (label => value). Optional: $heading
     The student's details as printed on the form: values on lines, captions under them, from the account. --}}
@php $D = \App\Support\DocSheet::class; @endphp
@if (! $student)
    <p class="rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">Pick the student above: their details print here.</p>
@else
    <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
        @foreach ($rows as $label => $value)
            <div wire:key="dt-{{ \Illuminate\Support\Str::slug($label) }}" class="min-w-0 flex flex-col-reverse">
                <dt class="{{ $D::CAPTION }} inline-flex items-center gap-1"><i class="bx bx-lock-alt not-italic" aria-hidden="true"></i>{{ $label }}</dt>
                <dd class="{{ $D::VALUE }}">{{ trim((string) $value) !== '' ? $value : '—' }}</dd>
            </div>
        @endforeach
    </dl>
@endif
