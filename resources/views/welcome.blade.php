<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aero - Cloud Servers by Cloudito</title>
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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-white">
    {{-- Navigation --}}
    <nav class="fixed w-full bg-white/80 backdrop-blur-lg border-b border-gray-100 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-brand-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Aero</span>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Inloggen</a>
                        <a href="{{ route('register') }}" class="bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-700 transition">Registreren</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-tight">
                    Cloud Servers<br>
                    <span class="text-brand-200">op topsnelheid</span>
                </h1>
                <p class="mt-6 text-xl text-brand-100 leading-relaxed">
                    Deploy je VPS in seconden met Aero. Krachtige servers, eenvoudig beheer en eerlijke prijzen. Powered by Cloudito.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto bg-white text-brand-700 font-semibold px-8 py-3.5 rounded-xl hover:bg-brand-50 transition shadow-lg">
                        Gratis Starten
                    </a>
                    <a href="#pricing" class="w-full sm:w-auto border-2 border-white/30 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-white/10 transition">
                        Bekijk Pakketten
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Waarom Aero?</h2>
                <p class="mt-4 text-lg text-gray-500">Alles wat je nodig hebt voor je cloud infrastructuur</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Instant Deployment</h3>
                    <p class="text-gray-500">Server online in minder dan 60 seconden. Kies je OS, configuratie en je bent klaar.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">DDoS Bescherming</h3>
                    <p class="text-gray-500">Ingebouwde DDoS mitigatie voor al je servers. Altijd beschermd, zonder extra kosten.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Flexibel Schalen</h3>
                    <p class="text-gray-500">Upgrade of downgrade op elk moment. Betaal alleen voor wat je gebruikt.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Eenvoudige Prijzen</h2>
                <p class="mt-4 text-lg text-gray-500">Geen verborgen kosten, geen verrassingen</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="bg-white p-8 rounded-2xl border-2 border-gray-100 hover:border-brand-200 transition">
                    <h3 class="text-lg font-semibold text-gray-900">Starter</h3>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-gray-900">&euro;4,99</span>
                        <span class="text-gray-400">/maand</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 1 vCPU Core</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 1 GB RAM</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 20 GB NVMe SSD</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 2 TB Verkeer</li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block text-center bg-brand-600 text-white font-medium py-2.5 rounded-lg hover:bg-brand-700 transition">Bestellen</a>
                </div>
                <div class="bg-white p-8 rounded-2xl border-2 border-brand-500 relative shadow-xl shadow-brand-100">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-xs font-semibold px-3 py-1 rounded-full">Populair</div>
                    <h3 class="text-lg font-semibold text-gray-900">Professional</h3>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-gray-900">&euro;9,99</span>
                        <span class="text-gray-400">/maand</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 2 vCPU Cores</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 4 GB RAM</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 60 GB NVMe SSD</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 4 TB Verkeer</li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block text-center bg-brand-600 text-white font-medium py-2.5 rounded-lg hover:bg-brand-700 transition">Bestellen</a>
                </div>
                <div class="bg-white p-8 rounded-2xl border-2 border-gray-100 hover:border-brand-200 transition">
                    <h3 class="text-lg font-semibold text-gray-900">Enterprise</h3>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-gray-900">&euro;24,99</span>
                        <span class="text-gray-400">/maand</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 4 vCPU Cores</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 8 GB RAM</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 120 GB NVMe SSD</li>
                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 10 TB Verkeer</li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block text-center bg-brand-600 text-white font-medium py-2.5 rounded-lg hover:bg-brand-700 transition">Bestellen</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                    </div>
                    <span class="text-white font-semibold">Aero by Cloudito</span>
                </div>
                <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} Cloudito. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </footer>
</body>
</html>
