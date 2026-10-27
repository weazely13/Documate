{{-- resources/views/components/admin/form-modal.blade.php
     Create/edit/assign dialog for the Users and Accounts Manager. Render it inside an @if so the
     Livewire component decides when it is open. A bottom sheet on phones, a centred dialog above.
     <x-admin.form-modal title="Add program" submit="saveProgram" close="closeForm">
         ...fields...
         <x-slot:footer> ...buttons... </x-slot:footer>
     </x-admin.form-modal>
     With `submit`, the body and footer sit in one <form>, so a type="submit" footer button works.
     `state` is the dialog's Alpine x-data, shared by the body and the footer. --}}
@props(['title', 'subtitle' => null, 'submit' => null, 'close' => 'closeForm', 'width' => 'sm:max-w-lg', 'state' => '{}'])

@php $tag = $submit ? 'form' : 'div'; @endphp

@teleport('body')
<div class="fixed inset-0 z-[9999] flex items-end justify-center bg-gray-900/50 backdrop-blur-sm sm:items-center sm:p-4"
     x-data="{{ $state }}" x-on:keydown.escape.window="$wire.{{ $close }}()"
     wire:click.self="{{ $close }}" role="dialog" aria-modal="true" aria-labelledby="form-modal-title">
    <{{ $tag }} @if ($submit) wire:submit="{{ $submit }}" @endif
        class="flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl {{ $width }}">

        {{-- Grab handle: shows the sheet can be dismissed on phones --}}
        <div class="flex justify-center pt-2.5 sm:hidden" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-gray-300"></span></div>

        <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 pb-4 pt-3 sm:pt-5">
            <div class="min-w-0">
                <h3 id="form-modal-title" class="text-lg font-bold tracking-tight text-gray-900">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $close }}" aria-label="Close"
                    class="-mr-1.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800">
                <i class='bx bx-x text-2xl' aria-hidden="true"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:flex-row sm:justify-end">
                {{ $footer }}
            </div>
        @endisset
    </{{ $tag }}>
</div>
@endteleport
