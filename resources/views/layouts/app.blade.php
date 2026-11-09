<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIMUTASI-GIS') — Sistem Pemetaan & Rekomendasi Mutasi ASN</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            50: '#f0f5fa',
                            100: '#e1ecf5',
                            600: '#1b4d7e',
                            800: '#14385c',
                            900: '#0f243c',
                        },
                        emerald: {
                            600: '#059669',
                            700: '#047857',
                        },
                    }
                }
            }
        }
    </script>

    <!-- Leaflet GIS CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .leaflet-popup-content-wrapper { border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-850 min-h-screen flex flex-col antialiased">

    <!-- Top Government App Bar -->
    <header class="bg-slate-900 text-white shadow-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo & Title -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center shadow-inner font-bold text-white tracking-wider text-xl">
                            <i data-lucide="map-pin" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="font-extrabold text-base tracking-tight text-white flex items-center gap-2">
                                SIMUTASI-GIS
                                <span class="bg-blue-500/20 text-blue-300 text-[10px] uppercase font-semibold px-2 py-0.5 rounded border border-blue-400/30">BKPSDM</span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-medium hidden sm:block">Sistem Pemetaan Spasial & SPK Mutasi ASN</div>
                        </div>
                    </a>
                </div>

                <!-- Main Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="compass" class="w-4 h-4 inline mr-1.5"></i> Dashboard Peta
                    </a>
                    <a href="{{ route('asn.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('asn.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="users" class="w-4 h-4 inline mr-1.5"></i> Data ASN
                    </a>
                    <a href="{{ route('unit-kerja.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('unit-kerja.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="building-2" class="w-4 h-4 inline mr-1.5"></i> Formasi Unit (ABK)
                    </a>
                    <a href="{{ route('topsis.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('topsis.index', 'topsis.proses') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="calculator" class="w-4 h-4 inline mr-1.5"></i> SPK TOPSIS
                    </a>
                    <a href="{{ route('topsis.riwayat') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('topsis.riwayat') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="file-check" class="w-4 h-4 inline mr-1.5"></i> Usulan Baperjakat
                    </a>
                </nav>

                <!-- Status Badge & User Info -->
                <div class="flex items-center space-x-3">
                    <div class="hidden lg:flex items-center gap-2 bg-slate-800 border border-slate-700/80 px-3 py-1.5 rounded-full text-xs text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Server: MariaDB Connected</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation bar -->
        <div class="md:hidden border-t border-slate-800 px-3 py-2 flex items-center justify-between overflow-x-auto text-xs space-x-2">
            <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400' }}">Peta</a>
            <a href="{{ route('asn.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('asn.*') ? 'bg-slate-800 text-white' : 'text-slate-400' }}">ASN</a>
            <a href="{{ route('unit-kerja.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('unit-kerja.*') ? 'bg-slate-800 text-white' : 'text-slate-400' }}">Formasi</a>
            <a href="{{ route('topsis.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('topsis.index') ? 'bg-slate-800 text-white' : 'text-slate-400' }}">TOPSIS</a>
            <a href="{{ route('topsis.riwayat') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('topsis.riwayat') ? 'bg-slate-800 text-white' : 'text-slate-400' }}">Baperjakat</a>
        </div>
    </header>

    <!-- Alert Notifications -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-lg shadow-sm flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-lg shadow-sm flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
            </div>
        @endif
    </div>

    <!-- Main Page Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-5 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                <strong>SIMUTASI-GIS</strong> &copy; {{ date('Y') }} Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM).
            </div>
            <div class="text-slate-400 flex items-center gap-3">
                <span>Haversine Great-Circle</span> &bull; 
                <span>TOPSIS Multi-Criteria Engine</span> &bull; 
                <span>GovTech Standards</span>
            </div>
        </div>
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
