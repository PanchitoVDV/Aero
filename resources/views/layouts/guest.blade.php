<!DOCTYPE html>
<html lang="nl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aero') - Cloudito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900">
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <a href="/" class="flex items-center gap-3 mb-8">
            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
            </div>
            <div>
                <span class="text-2xl font-bold text-white">Aero</span>
                <span class="text-sm text-white/60 block -mt-1">by Cloudito</span>
            </div>
        </a>

        <div class="w-full max-w-md">
            @yield('content')
        </div>

        <p class="mt-8 text-sm text-white/40">&copy; {{ date('Y') }} Cloudito. Alle rechten voorbehouden.</p>
    </div>
</body>
</html>
