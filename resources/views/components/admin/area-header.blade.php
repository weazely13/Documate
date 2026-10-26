{{-- resources/views/components/admin/area-header.blade.php
     Header for an admin area made of several pages: one title, then tabs that link between the pages.
     <x-admin.area-header name="documents" active="uploads">
         <x-slot:actions> ...buttons for this page... </x-slot:actions>
     </x-admin.area-header> --}}
@props(['name', 'active'])

@php
    $sections = [
        'documents' => [
            'title' => 'Templates & Uploads',
            'tabs' => [
                'templates' => ['Templates', route('admin.templates'), 'Paper forms students fill in, print and bring to the office.'],
                'uploads' => ['Uploads', route('admin.document-uploads.index'), 'Photos of paper forms students had signed at the office.'],
            ],
        ],
    ];

    $section = $sections[$name];
    $current = $section['tabs'][$active];
@endphp

<header {{ $attributes->class('mb-6') }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">{{ $section['title'] }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $current[2] }}</p>
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    <nav aria-label="{{ $section['title'] }} sections" class="-mx-4 mt-5 flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200 px-4 sm:mx-0 sm:px-0">
        @foreach ($section['tabs'] as $key => [$label, $url])
            <a href="{{ $url }}" wire:navigate
               @if ($key === $active) aria-current="page" @endif
               class="-mb-px shrink-0 border-b-2 px-1 pb-3 text-sm font-medium transition
                   {{ $key === $active
                       ? 'border-[#2A57B4] text-[#2A57B4]'
                       : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</header>
