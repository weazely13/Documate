{{-- resources/views/components/admin/alert.blade.php
     <x-admin.alert :message="session('org_message')" /> --}}
@props(['message' => null, 'type' => 'info'])

@if ($message)
    @php
        $styles = [
            'info'    => 'border-blue-200 bg-blue-50 text-blue-800',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
            'danger'  => 'border-red-200 bg-red-50 text-red-800',
        ][$type] ?? 'border-blue-200 bg-blue-50 text-blue-800';
    @endphp
    <div x-data="{ open: true }" x-show="open" role="status"
         {{ $attributes->class(["mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm", $styles]) }}>
        <span>{{ $message }}</span>
        <button type="button" @click="open = false" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
    </div>
@endif