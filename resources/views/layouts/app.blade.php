<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
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
                        brand: {
                            DEFAULT: '#004a87',
                            dark: '#0f243c',
                            light: '#e1ecf5',
                        }
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
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); }

        /* Custom Marker Ripple Animation */
        @keyframes mapRipple {
            0% {
                transform: scale(0.85);
                opacity: 0.85;
            }
            100% {
                transform: scale(2.6);
                opacity: 0;
            }
        }
        .marker-ripple {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            animation: mapRipple 2.2s cubic-bezier(0, 0.2, 0.8, 1) infinite;
            pointer-events: none;
        }
        .custom-office-marker, .custom-home-marker {
            background: transparent !important;
            border: none !important;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 flex overflow-hidden">

    <!-- Mobile Drawer Backdrop -->
    <div id="mobileBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden hidden transition-opacity"></div>

    <!-- =========================================================================
         LEFT SIDEBAR NAVIGATION (GovTech / SaaS Style - Inspired by crm_worrie)
    ========================================================================== -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0 lg:static lg:z-auto">
        
        <!-- Brand Header -->
        <div class="h-16 px-5 border-b border-slate-200 flex items-center justify-between bg-white shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-700 flex items-center justify-center text-white shadow-sm font-bold">
                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-extrabold text-slate-900 text-sm tracking-tight">SIMUTASI-GIS</span>
                        <span class="bg-blue-50 text-blue-700 text-[10px] uppercase font-bold px-1.5 py-0.2 rounded border border-blue-200">v1.0</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold block leading-tight">BKPSDM Kabupaten</span>
                </div>
            </a>
            <button onclick="toggleSidebar()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 lg:hidden">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links (Scrollable) -->
        <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto text-xs">
            
            <!-- Menu Utama -->
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400">
                    Menu Utama
                </div>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Dashboard Peta GIS</span>
                    </a>

                    <a href="{{ route('asn.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('asn.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users" class="w-4 h-4 shrink-0 {{ request()->routeIs('asn.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Data Pegawai & Domisili</span>
                    </a>

                    <a href="{{ route('unit-kerja.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('unit-kerja.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="building-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('unit-kerja.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Formasi Unit (ABK)</span>
                    </a>
                </div>
            </div>

            <!-- Sistem Keputusan & Mutasi -->
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400">
                    Sistem Rekomendasi
                </div>
                <div class="space-y-1">
                    <a href="{{ route('topsis.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('topsis.index', 'topsis.proses') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calculator" class="w-4 h-4 shrink-0 {{ request()->routeIs('topsis.index', 'topsis.proses') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">SPK TOPSIS</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700">Dinamis</span>
                    </a>

                    <a href="{{ route('topsis.riwayat') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('topsis.riwayat') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="file-check-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('topsis.riwayat') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Usulan Baperjakat</span>
                    </a>
                </div>
            </div>

            <!-- Standar Metodologi (Info Box) -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5 text-[11px]">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-600"></i> Metodologi Riset
                </div>
                <p class="text-slate-500 leading-relaxed text-[10px]">
                    Kombinasi Formula Haversine Spasial & OSRM Road Routing dengan Algoritma TOPSIS Multi-Kriteria.
                </p>
            </div>

        </nav>

        <!-- Bottom Sidebar Widget (Server Connection & Profile) -->
        <div class="p-3 border-t border-slate-200 bg-slate-50/50 shrink-0 space-y-2">
            <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between text-xs shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <div>
                        <div class="font-bold text-slate-900 text-[11px]">MariaDB 13.0</div>
                        <div class="text-[9px] text-slate-400 font-mono">192.168.100.65</div>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Online</span>
            </div>

            <div class="flex items-center justify-between px-2 pt-1 text-[11px] text-slate-400">
                <span>BKPSDM Indramayu</span>
                <span>&copy; {{ date('Y') }}</span>
            </div>
        </div>

    </aside>

    <!-- =========================================================================
         MAIN APPLICATION AREA (Top Header + Scrollable Content)
    ========================================================================== -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-30 shadow-2xs">
            
            <!-- Left: Mobile Toggle & Breadcrumb Title -->
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="font-bold text-slate-900 text-sm sm:text-base leading-tight">@yield('title', 'Sistem Rekomendasi Mutasi ASN')</h2>
                    <div class="text-[11px] text-slate-400 hidden sm:block">Pemerintah Kabupaten Indramayu &bull; Badan Kepegawaian & Pengembangan SDM</div>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('topsis.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Jalankan SPK</span>
                </a>
                <button onclick="window.print()" class="p-2 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition" title="Cetak Halaman Ini">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                </button>
            </div>
        </header>

        <!-- Scrollable Content Viewport -->
        <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <!-- Notification Alerts -->
                @if (session('success'))
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                            <p class="text-xs sm:text-sm font-medium">{{ session('success') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-base">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                            <p class="text-xs sm:text-sm font-medium">{{ session('error') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-base">&times;</button>
                    </div>
                @endif

                <!-- Yielded Content -->
                @yield('content')

            </div>
        </main>

    </div>

    <!-- Script Drawer Toggle & Lucide Init -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobileBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        document.addEventListener("DOMContentLoaded", function () {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
