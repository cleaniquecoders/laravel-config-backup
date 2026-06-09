<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Config Backup Workbench' }}</title>

    {{-- Compiled Tailwind v4 + Flux stylesheet (built via `npm run build:css`). --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased">
    <main class="mx-auto max-w-6xl p-6 lg:p-10">
        {{ $slot }}
    </main>

    @fluxScripts
</body>
</html>
