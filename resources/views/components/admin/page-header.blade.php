{{-- resources/views/components/admin/page-header.blade.php
     <x-admin.page-header title="Users & Access" :crumbs="['Admin' => route('admin.dashboard'), 'Users & Access' => null]">
         <x-slot:actions> ...buttons... </x-slot:actions>
     </x-admin.page-header> --}}
@props(['title', 'description' => null, 'crumbs' => []])

<header class="mb-6">
    @if ($crumbs)
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                @foreach ($crumbs as $text => $url)
                    <li class="flex items-center gap-1.5">
                        @if ($url && ! $loop->last)
                            <a href="{{ $url }}" class="hover:text-[#2A57B4] hover:underline">{{ $text }}</a>
                        @else
                            <span class="{{ $loop->last ? 'font-medium text-gray-700' : '' }}" @if($loop->last) aria-current="page" @endif>{{ $text }}</span>
                        @endif
                        @unless ($loop->last)
                            <svg class="h-3 w-3 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        @endunless
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight sm:text-2xl text-gray-900">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto">{{ $actions }}</div>
        @endisset
    </div>
</header>