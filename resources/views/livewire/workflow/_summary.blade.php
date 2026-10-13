{{-- Needs: $ws, $document. Shows the student and every answer on the form. --}}
@php $values = $ws->field_values ?? []; @endphp
<dl class="space-y-3">
    <div>
        <dt class="text-xs font-medium text-slate-400">Student</dt>
        <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $values['student_name'] ?? '-' }}</dd>
        <dd class="text-sm text-slate-600">{{ $values['student_program'] ?? '-' }}</dd>
        @if ($ws->user?->student_number)
            <dd class="text-xs text-slate-400">{{ $ws->user->student_number }}</dd>
        @endif
    </div>
    @foreach ($document->allFields() as $name => $f)
        <div wire:key="sum-{{ $name }}">
            <dt class="text-xs font-medium text-slate-400">{{ $f['label'] }}</dt>
            <dd class="mt-0.5 whitespace-pre-wrap text-sm text-slate-800">{{ $document->formatValue($f, $values[$name] ?? null) ?: '-' }}</dd>
        </div>
    @endforeach
</dl>
