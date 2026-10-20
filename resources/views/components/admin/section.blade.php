{{-- resources/views/components/admin/section.blade.php
     Standard titled container used for every form, table and list.
     <x-admin.section title="Organizations" description="..." padded="false"> ... </x-admin.section> --}}
@props(['title' => null, 'description' => null, 'padded' => true])

<section {{ $attributes->class('rounded-xl border border-gray-200 bg-white shadow-sm') }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3.5">
            <div class="min-w-0">
                @if ($title)<h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-xs text-gray-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5' => $padded])>{{ $slot }}</div>
</section>