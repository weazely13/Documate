<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'DocuMate' }} · DocuMate</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- The URL contains a secret token: never leak it through Referer, never index it. --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <div class="mx-auto max-w-xl px-4 py-8">
        <div class="mb-5 flex items-center gap-3">
            <img src="{{ asset('images/favicon.png') }}" class="h-9 w-9 object-contain" alt="DocuMate">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[#2A57B4]">DocuMate</p>
                <p class="text-[11px] text-slate-500">Leyte Normal University · Office of the VP for Student Development</p>
            </div>
        </div>

        {{ $slot }}

        <p class="mt-6 text-center text-xs text-slate-400">
            Secure signing page. Never share this link.
        </p>
    </div>
    <x-toast-stack />
    @livewireScripts
</body>
</html>