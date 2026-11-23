<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($title) ? $title . ' · ' : '' }}DocuMate template editor</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-[100dvh] overflow-hidden bg-[#eef0f3] antialiased">
    {{-- The editor runs from resources/js/template-editor.js (Alpine "templateEditor"). --}}
    {{ $slot }}

    @livewireScripts
</body>
</html>
