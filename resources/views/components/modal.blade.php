@props(['name', 'show' => false, 'maxWidth' => '2xl', 'focusable' => false])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth] ?? $maxWidth;
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' && (show = true)"
    x-on:close-modal.window="$event.detail == '{{ $name }}' && (show = false)"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
>
    <div x-show="show" class="fixed inset-0 transform transition-all" x-on:click="show = false" x-transition.opacity>
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div x-show="show" @if ($focusable) x-trap.noscroll.inert="show" @endif class="mb-6 transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:mx-auto {{ $maxWidthClass }}" x-transition>
        {{ $slot }}
    </div>
</div>
