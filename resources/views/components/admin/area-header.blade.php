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

{{-- Just the tabs (the page title is in the top bar), with any actions at the end of the row. --}}
<header {{ $attributes->class('mb-6') }}>
    <h1 class="sr-only">{{ $section['title'] }}: {{ $current[0] }}</h1>
    <div class="flex items-end justify-between gap-3 border-b border-gray-200">
        <nav aria-label="{{ $section['title'] }} sections" class="-mx-4 flex min-w-0 gap-6 overflow-x-auto whitespace-nowrap px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
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
        @isset($actions)
            <div class="flex shrink-0 items-center gap-2 pb-2">{{ $actions }}</div>
        @endisset
    </div>
</header>
