<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Rekom Mutasi PNS') — Sistem Pemetaan & Rekomendasi Mutasi PNS</title>
    
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

    <!-- Leaflet GIS CSS & JS + Heatmap Plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

    <!-- jQuery & DataTables 2 (Engine Core JS - Pure Light Theme Custom) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

    <!-- Select2 (Modern Searchable Dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); }

        /* =========================================================================
           SELECT2 MODERN LIGHT THEME CUSTOMIZATION (Enterprise GovTech Aesthetic)
        ========================================================================== */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: 42px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            padding: 6px 12px !important;
            background-color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important;
            outline: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #0f172a !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            padding-left: 0 !important;
            line-height: normal !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important;
            font-size: 0.8125rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-dropdown {
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15) !important;
            font-size: 0.875rem !important;
            background-color: #ffffff !important;
            z-index: 99999 !important;
            overflow: hidden;
        }
        .select2-container--default .select2-search--dropdown {
            padding: 8px 10px !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.375rem !important;
            padding: 6px 10px !important;
            font-size: 0.8125rem !important;
            background-color: #ffffff !important;
            outline: none !important;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 0.8125rem !important;
            color: #1e293b !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }

        /* =========================================================================
           INTERACTIVE MICRO-ANIMATIONS & TRANSITION SYSTEM (Adopsi dari crm_worrie)
        ========================================================================== */
        @keyframes cardSlideIn {
            0% {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes modalPopIn {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(20px);
            }
            60% {
                opacity: 1;
                transform: scale(1.015) translateY(-3px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(225, 29, 72, 0);
            }
        }

        @keyframes floatGentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .animate-card-slide {
            animation: cardSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .animate-modal-pop {
            animation: modalPopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .animate-pulse-glow {
            animation: pulseGlow 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }

        .animate-float {
            animation: floatGentle 3s ease-in-out infinite;
        }

        /* Interactive Card Hover & Lift Physics */
        .interactive-card {
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.22s ease,
                        background-color 0.22s ease;
        }

        .interactive-card:hover {
            transform: translateY(-3px) scale(1.008);
            box-shadow: 0 12px 28px -6px rgba(0, 74, 135, 0.12), 0 4px 12px -2px rgba(0, 0, 0, 0.04);
        }

        .interactive-card:active {
            transform: translateY(-1px) scale(0.995);
        }

        /* Standardized List / Table Row Nudge Animation */
        .table-row-standard {
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .table-row-standard:hover {
            background-color: rgba(239, 246, 255, 0.65) !important;
            transform: translateX(4px);
        }

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

        /* =========================================================================
           DATATABLES 2 MODERN ENTERPRISE THEME (100% LIGHT THEME ONLY - ZERO DARK STRIPES)
        ========================================================================== */
        .dt-container {
            font-size: 0.8125rem;
            color: #334155;
            width: 100%;
        }
        .dt-container table.dataTable {
            width: 100% !important;
            margin: 0 !important;
            border-collapse: collapse !important;
            background-color: #ffffff !important;
        }
        .dt-container table.dataTable tbody tr {
            background-color: #ffffff !important;
            color: #334155 !important;
            transition: background-color 0.15s ease;
        }
        .dt-container table.dataTable tbody tr:hover {
            background-color: #f8fafc !important;
        }
        .dt-container table.dataTable tbody td {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 0.875rem 1rem !important;
            vertical-align: middle !important;
        }
        .dt-container table.dataTable thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        .dt-container table.dataTable thead th.no-sort {
            cursor: default !important;
        }
        .dt-container table.dataTable thead th.dt-ordering-asc,
        .dt-container table.dataTable thead th.dt-ordering-desc {
            color: #1e40af !important;
            background-color: #f1f5f9 !important;
        }
        .dt-container .dt-layout-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 1.25rem;
            background-color: #ffffff;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .dt-container .dt-layout-row:first-child {
            border-bottom: 1px solid #f1f5f9;
        }
        .dt-container .dt-layout-row:last-child {
            border-top: 1px solid #f1f5f9;
        }
        .dt-container .dt-layout-table {
            display: block !important;
            width: 100% !important;
            overflow-x: auto !important;
            padding: 0 !important;
        }
        .dt-container .dt-search input {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.625rem !important;
            padding: 0.45rem 0.875rem 0.45rem 2.2rem !important;
            font-size: 0.8125rem !important;
            background-color: #f8fafc !important;
            color: #0f172a !important;
            outline: none !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
            min-width: 240px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: 0.75rem center !important;
            background-size: 1rem 1rem !important;
        }
        .dt-container .dt-search input:focus {
            background-color: #ffffff !important;
            border-color: #1e40af !important;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.12) !important;
        }
        .dt-container .dt-length select {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.625rem !important;
            padding: 0.4rem 2rem 0.4rem 0.75rem !important;
            font-size: 0.8125rem !important;
            background-color: #f8fafc !important;
            color: #334155 !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            outline: none !important;
        }
        .dt-container .dt-length select:focus {
            border-color: #1e40af !important;
            background-color: #ffffff !important;
        }
        .dt-container .dt-info {
            font-size: 0.75rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }
        .dt-container .dt-paging nav {
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }
        .dt-container .dt-paging button.dt-paging-button {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            padding: 0.35rem 0.7rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            background: #ffffff !important;
            color: #475569 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            min-width: 2rem !important;
            text-align: center !important;
        }
        .dt-container .dt-paging button.dt-paging-button:hover:not(.disabled) {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .dt-container .dt-paging button.dt-paging-button.current {
            background: #0f172a !important;
            color: #ffffff !important;
            border-color: #0f172a !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.2) !important;
        }
        .dt-container .dt-paging button.dt-paging-button.disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
            background: #f8fafc !important;
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
                        <span class="font-extrabold text-slate-900 text-sm tracking-tight">Rekom Mutasi PNS</span>
                        <span class="bg-blue-50 text-blue-700 text-[10px] uppercase font-bold px-1.5 py-0.2 rounded border border-blue-200">v1.0</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold block leading-tight">BKPSDM Kabupaten XYZ</span>
                </div>
            </a>
            <button onclick="toggleSidebar()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 lg:hidden">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links (Scrollable) -->
        <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto text-xs">
            
            @if(Auth::check() && Auth::user()->isAdmin())
            <!-- Menu Utama / Dasbor (Khusus Admin BKPSDM) -->
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400">
                    Dasbor Utama
                </div>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Dashboard Peta GIS</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Master Data Pengelolaan (Khusus Admin BKPSDM) -->
            @if(Auth::check() && Auth::user()->isAdmin())
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400 flex items-center justify-between">
                    <span>Master Data</span>
                    <span class="text-[9px] text-indigo-600 font-semibold bg-indigo-50 px-1.5 py-0.2 rounded">Admin</span>
                </div>
                <div class="space-y-1">
                    <a href="{{ route('asn.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('asn.index', 'asn.show', 'asn.create', 'asn.edit*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users" class="w-4 h-4 shrink-0 {{ request()->routeIs('asn.index', 'asn.show', 'asn.create', 'asn.edit*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Data PNS</span>
                    </a>

                    <a href="{{ route('unit-kerja.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('unit-kerja.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="building-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('unit-kerja.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Data Unit Kerja</span>
                    </a>

                    <a href="{{ route('master-jabatan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('master-jabatan.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="tag" class="w-4 h-4 shrink-0 {{ request()->routeIs('master-jabatan.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Data Referensi Jabatan</span>
                    </a>

                    <a href="{{ route('formasi-jabatan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('formasi-jabatan.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="briefcase" class="w-4 h-4 shrink-0 {{ request()->routeIs('formasi-jabatan.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Data Formasi Jabatan</span>
                    </a>

                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="user-cog" class="w-4 h-4 shrink-0 {{ request()->routeIs('users.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Manajemen User</span>
                    </a>

                    @php
                        $pendingLokasiCount = \Illuminate\Support\Facades\Schema::hasTable('pengajuan_ubah_lokasi')
                            ? \App\Models\PengajuanUbahLokasi::where('status', 'pending')->count()
                            : 0;
                    @endphp
                    <a href="{{ route('verifikasi-lokasi.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('verifikasi-lokasi.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="map-pin-check" class="w-4 h-4 shrink-0 {{ request()->routeIs('verifikasi-lokasi.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Verifikasi Lokasi</span>
                        @if($pendingLokasiCount > 0)
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-white">{{ $pendingLokasiCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
            @elseif(Auth::check() && Auth::user()->isAsn())
            <!-- Menu Profil Khusus Pegawai PNS -->
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400 flex items-center justify-between">
                    <span>Profil Pegawai</span>
                    <span class="text-[9px] text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.2 rounded">PNS</span>
                </div>
                <div class="space-y-1">
                    @if(Auth::user()->asn_id)
                        <a href="{{ route('asn.edit-lokasi', Auth::user()->asn_id) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('asn.edit-lokasi', 'asn.show') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="map-pin" class="w-4 h-4 shrink-0 {{ request()->routeIs('asn.edit-lokasi', 'asn.show') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                            <span>Profil &amp; Titik Lokasi</span>
                        </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SPK (Sistem Pendukung Keputusan) -->
            <div>
                <div class="px-3 mb-2 font-bold uppercase tracking-wider text-[10px] text-slate-400">
                    SPK
                </div>
                <div class="space-y-1">
                    @if(Auth::check() && Auth::user()->isAdmin())
                    <a href="{{ route('master-kriteria.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('master-kriteria.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="sliders" class="w-4 h-4 shrink-0 {{ request()->routeIs('master-kriteria.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Kriteria &amp; Bobot</span>
                    </a>

                    <a href="{{ route('topsis.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('topsis.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calculator" class="w-4 h-4 shrink-0 {{ request()->routeIs('topsis.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Perhitungan SPK TOPSIS</span>
                    </a>
                    @elseif(Auth::check() && Auth::user()->isAsn())
                    <a href="{{ route('asn.simulasi-mandiri') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('asn.simulasi-mandiri*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="compass" class="w-4 h-4 shrink-0 {{ request()->routeIs('asn.simulasi-mandiri*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Peta &amp; Rekomendasi Unit</span>
                    </a>
                    @endif

                    {{-- HIDE SEMENTARA SIMULASI, SMART SWAP & BAPERJAKAT SESUAI INSTRUKSI KLIEN --}}
                    @if(false)
                    <a href="{{ route('asn.simulasi-mandiri') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('asn.simulasi-mandiri*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="compass" class="w-4 h-4 shrink-0 {{ request()->routeIs('asn.simulasi-mandiri*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Simulasi Penempatan</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700">Tahap 2</span>
                    </a>

                    <a href="{{ route('swap-match.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('swap-match.*') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="repeat" class="w-4 h-4 shrink-0 {{ request()->routeIs('swap-match.*') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Smart Swap Match</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Zero-ABK</span>
                    </a>

                    @if(Auth::check() && Auth::user()->isAdmin())
                    <a href="{{ route('topsis.riwayat') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition {{ request()->routeIs('topsis.riwayat') ? 'bg-blue-50 text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="file-check-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('topsis.riwayat') ? 'text-blue-700' : 'text-slate-400' }}"></i>
                        <span>Usulan Baperjakat</span>
                    </a>
                    @endif
                    @endif
                </div>
            </div>

        </nav>

        <!-- Bottom Sidebar Widget (Authenticated User & Server Connection) -->
        <div class="p-3 border-t border-slate-200 bg-slate-50/50 shrink-0 space-y-2">
            @if(Auth::check())
            <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between text-xs shadow-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-[11px] truncate">{{ Auth::user()->name }}</div>
                        <div class="text-[9px] text-slate-400 font-mono truncate">
                            {{ Auth::user()->nip ?? Auth::user()->email }}
                        </div>
                    </div>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold shrink-0 {{ Auth::user()->isAdmin() ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                    {{ Auth::user()->isAdmin() ? 'Admin' : 'PNS' }}
                </span>
            </div>
            @endif

            <div class="flex items-center justify-between px-2 pt-1 text-[11px] text-slate-400">
                <span>BKPSDM Kabupaten XYZ</span>
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
                    <h2 class="font-bold text-slate-900 text-sm sm:text-base leading-tight">@yield('title', 'Sistem Rekomendasi Mutasi PNS')</h2>
                    <div class="text-[11px] text-slate-400 hidden sm:block">Pemerintah Kabupaten XYZ &bull; Badan Kepegawaian & Pengembangan SDM</div>
                </div>
            </div>

            <!-- Right: Quick Actions & User Account -->
            <div class="flex items-center gap-2 sm:gap-3">
                @if(Auth::check())
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border border-slate-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 text-slate-600 rounded-lg text-xs font-semibold transition" title="Keluar dari Sistem">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
                @endif
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

    <!-- Script Drawer Toggle & Lucide Init & DataTables 2 Init -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobileBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        document.addEventListener("DOMContentLoaded", function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Inisialisasi DataTables 2 Modern Light Theme untuk tabel berkelas .datatable-custom
            if (typeof $.fn.DataTable !== 'undefined') {
                $('.datatable-custom').each(function () {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        const dt = $(this).DataTable({
                            language: {
                                search: "",
                                searchPlaceholder: "Cari data cepat...",
                                lengthMenu: "Tampilkan _MENU_ baris",
                                info: "Menampilkan _START_ s.d _END_ dari _TOTAL_ data",
                                infoEmpty: "Menampilkan 0 data",
                                infoFiltered: "(disaring dari _MAX_ total data)",
                                zeroRecords: "Tidak ada data yang cocok dengan pencarian",
                                paginate: {
                                    first: "«",
                                    last: "»",
                                    next: "›",
                                    previous: "‹"
                                }
                            },
                            pageLength: 10,
                            responsive: true,
                            autoWidth: false,
                            ordering: true,
                            columnDefs: [
                                { targets: 'no-sort', orderable: false }
                            ]
                        });

                        // Re-render ikon Lucide saat ganti halaman atau sortir
                        dt.on('draw', function () {
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        });
                    }
                });
            }

            // Inisialisasi Select2 untuk dropdown pencarian (.searchable-select)
            if (typeof $.fn.select2 !== 'undefined') {
                $('.searchable-select').each(function () {
                    $(this).select2({
                        width: '100%',
                        placeholder: $(this).attr('placeholder') || '-- Pilih Opsi --',
                        allowClear: Boolean($(this).attr('placeholder'))
                    });
                });
            }
        });

        /**
         * Global Google Maps-style Teardrop Marker Pin Helper untuk seluruh peta Leaflet SIMUTASI-GIS.
         * @param {Object} opts { color: '#ea4335', type: 'dot'|'home'|'office'|'rank', label: '', size: 'sm'|'md'|'lg' }
         */
        window.createGmapPinIcon = function (opts = {}) {
            const color = opts.color || '#ea4335';
            const type = opts.type || 'dot';
            const label = opts.label || '';
            const size = opts.size || 'md';

            const dims = size === 'sm' ? { w: 28, h: 38 } : (size === 'lg' ? { w: 40, h: 52 } : { w: 34, h: 44 });
            let inner = `<circle cx="18" cy="17" r="6" fill="#ffffff"/>`;
            if (type === 'home') {
                inner = `<circle cx="18" cy="17" r="10.5" fill="#ffffff"/>
                         <path d="M12.5 17.5L18 12.6l5.5 4.9M14.2 16.4v5.1h7.6v-5.1" fill="none" stroke="${color}" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>`;
            } else if (type === 'office') {
                inner = `<circle cx="18" cy="17" r="10.5" fill="#ffffff"/>
                         <path d="M13.5 22v-9.5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1V22M11.5 22h13M16 14.5h1.5M18.5 14.5H20M16 17.5h1.5M18.5 17.5H20" fill="none" stroke="${color}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>`;
            } else if (type === 'rank') {
                inner = `<circle cx="18" cy="17" r="10.5" fill="#ffffff"/>
                         <text x="18" y="20.8" text-anchor="middle" font-size="10.5" font-weight="800" font-family="ui-sans-serif, system-ui, sans-serif" fill="${color}">${label}</text>`;
            }

            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="${dims.w}" height="${dims.h}" viewBox="0 0 36 48" style="filter: drop-shadow(0 3px 4px rgba(15,23,42,0.32)); overflow: visible;">
                    <path d="M18 1C8.61 1 1 8.61 1 18c0 11.8 15.1 26.8 16.1 27.8a1.25 1.25 0 0 0 1.8 0C19.9 44.8 35 29.8 35 18 35 8.61 27.39 1 18 1z" fill="${color}" stroke="#ffffff" stroke-width="2"/>
                    ${inner}
                </svg>
            `;

            return L.divIcon({
                className: 'gmap-teardrop-pin',
                html: svg,
                iconSize: [dims.w, dims.h],
                iconAnchor: [dims.w / 2, dims.h - 1],
                popupAnchor: [0, -(dims.h - 6)]
            });
        };

        /**
         * Global Google Maps Basemap & Layer Switcher untuk seluruh peta Leaflet SIMUTASI-GIS.
         * Menampilkan POI / lokasi Google Maps (kantor, sekolah, masjid, RS, nama jalan) secara default.
         */
        window.attachGmapBasemap = function (mapInstance) {
            const gmapRoadmap = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&hl=id&x={x}&y={y}&z={z}', {
                subdomains: ['0', '1', '2', '3'],
                maxZoom: 20,
                attribution: '&copy; Google Maps'
            });

            const gmapHybrid = L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&hl=id&x={x}&y={y}&z={z}', {
                subdomains: ['0', '1', '2', '3'],
                maxZoom: 20,
                attribution: '&copy; Google Maps Satellite'
            });

            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });

            gmapRoadmap.addTo(mapInstance);
            L.control.layers({
                'Google Maps (POI)': gmapRoadmap,
                'Satelit Hybrid': gmapHybrid,
                'OpenStreetMap': osmLayer
            }, null, { position: 'topright' }).addTo(mapInstance);

            return { gmapRoadmap, gmapHybrid, osmLayer };
        };

        /**
         * Global Cascading Dropdown Wilayah.id (Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa/Kelurahan).
         * Menggunakan plain JS Map (tanpa jQuery .data() cache bug), in-memory cache, request sequence guard,
         * disabled loading state, dan retry otomatis agar 1x klik langsung memuat pilihan dengan stabil.
         */
        window._wilayahMemCache = window._wilayahMemCache || {};
        window.initWilayahCascade = function (cfg) {
            const $prov = $(cfg.provSelector);
            const $kab  = $(cfg.kabSelector);
            const $kec  = $(cfg.kecSelector);
            const $desa = $(cfg.desaSelector);
            const baseUrl = (cfg.baseUrl || '{{ url('/api/wilayah') }}').replace(/\/$/, '');

            const provMap = new Map();
            const kabMap  = new Map();
            const kecMap  = new Map();
            const seq = { reg: 0, dist: 0, vil: 0 };

            function norm(s) {
                return (s || '').toLowerCase().replace(/^kab(\.|upaten)?\s+/i, '').replace(/^kota\s+/i, '').trim();
            }

            async function fetchCached(key, url) {
                if (window._wilayahMemCache[key] && window._wilayahMemCache[key].length > 0) {
                    return window._wilayahMemCache[key];
                }
                for (let attempt = 0; attempt < 2; attempt++) {
                    try {
                        const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!r.ok) continue;
                        const json = await r.json();
                        if (Array.isArray(json.data) && json.data.length > 0) {
                            window._wilayahMemCache[key] = json.data;
                            return json.data;
                        }
                    } catch (_) {}
                }
                return [];
            }

            function resetSelect($el, placeholder, disabled = false) {
                $el.empty().append(new Option(placeholder, '', false, false));
                $el.prop('disabled', disabled);
                $el.trigger('change.select2');
            }

            async function loadVillages(distCode, preDesa) {
                const mySeq = ++seq.vil;
                if (!distCode) {
                    resetSelect($desa, '-- Pilih Kecamatan Terlebih Dahulu --', false);
                    return;
                }
                resetSelect($desa, '⏳ Memuat Desa / Kelurahan...', true);
                const list = await fetchCached('vil:' + distCode, `${baseUrl}/villages/${encodeURIComponent(distCode)}`);
                if (mySeq !== seq.vil) return;

                $desa.empty().append(new Option('-- Pilih Desa / Kelurahan --', '', false, false));
                let matched = false;
                list.forEach(v => {
                    const sel = Boolean(preDesa && v.name.toLowerCase() === preDesa.toLowerCase());
                    if (sel) matched = true;
                    $desa.append(new Option(v.name, v.name, sel, sel));
                });
                if (preDesa && !matched) {
                    $desa.append(new Option(preDesa, preDesa, true, true));
                }
                $desa.prop('disabled', false).trigger('change.select2');
            }

            async function loadDistricts(regCode, preKec, preDesa) {
                const mySeq = ++seq.dist;
                kecMap.clear();
                resetSelect($desa, '-- Pilih Kecamatan Terlebih Dahulu --', false);
                if (!regCode) {
                    resetSelect($kec, '-- Pilih Kabupaten / Kota Terlebih Dahulu --', false);
                    return;
                }
                resetSelect($kec, '⏳ Memuat Kecamatan...', true);
                const list = await fetchCached('dist:' + regCode, `${baseUrl}/districts/${encodeURIComponent(regCode)}`);
                if (mySeq !== seq.dist) return;

                $kec.empty().append(new Option('-- Pilih Kecamatan --', '', false, false));
                let matchedCode = null;
                list.forEach(d => {
                    kecMap.set(d.name, d.code);
                    const sel = Boolean(preKec && d.name.toLowerCase() === preKec.toLowerCase());
                    if (sel) matchedCode = d.code;
                    const label = cfg.prefixKec === false ? d.name : `Kec. ${d.name}`;
                    $kec.append(new Option(label, d.name, sel, sel));
                });
                if (preKec && !matchedCode) {
                    const label = cfg.prefixKec === false ? preKec : `Kec. ${preKec}`;
                    $kec.append(new Option(label, preKec, true, true));
                }
                $kec.prop('disabled', false).trigger('change.select2');
                if (matchedCode) {
                    await loadVillages(matchedCode, preDesa);
                }
            }

            async function loadRegencies(provCode, preKab, preKec, preDesa) {
                const mySeq = ++seq.reg;
                kabMap.clear();
                kecMap.clear();
                resetSelect($kec, '-- Pilih Kabupaten / Kota Terlebih Dahulu --', false);
                resetSelect($desa, '-- Pilih Kecamatan Terlebih Dahulu --', false);
                if (!provCode) {
                    resetSelect($kab, '-- Pilih Provinsi Terlebih Dahulu --', false);
                    return;
                }
                resetSelect($kab, '⏳ Memuat Kabupaten / Kota...', true);
                const list = await fetchCached('reg:' + provCode, `${baseUrl}/regencies/${encodeURIComponent(provCode)}`);
                if (mySeq !== seq.reg) return;

                $kab.empty().append(new Option('-- Pilih Kabupaten / Kota --', '', false, false));
                let matchedCode = null;
                list.forEach(reg => {
                    kabMap.set(reg.name, reg.code);
                    const sel = Boolean(preKab && (
                        reg.name.toLowerCase() === preKab.toLowerCase() ||
                        norm(reg.name) === norm(preKab)
                    ));
                    if (sel) matchedCode = reg.code;
                    $kab.append(new Option(reg.name, reg.name, sel, sel));
                });
                if (preKab && !matchedCode) {
                    $kab.append(new Option(preKab, preKab, true, true));
                }
                $kab.prop('disabled', false).trigger('change.select2');
                if (matchedCode) {
                    await loadDistricts(matchedCode, preKec, preDesa);
                }
            }

            $prov.off('change.wilayah').on('change.wilayah', function () {
                const val = $(this).val() || '';
                const code = provMap.get(val) || null;
                loadRegencies(code, null, null, null);
                if (typeof cfg.onProvinceChange === 'function') cfg.onProvinceChange(val, code);
            });

            $kab.off('change.wilayah').on('change.wilayah', function () {
                const val = $(this).val() || '';
                const code = kabMap.get(val) || null;
                loadDistricts(code, null, null);
                if (typeof cfg.onRegencyChange === 'function') cfg.onRegencyChange(val, $prov.val() || '', code);
            });

            $kec.off('change.wilayah').on('change.wilayah', function () {
                const val = $(this).val() || '';
                const code = kecMap.get(val) || null;
                loadVillages(code, null);
                if (typeof cfg.onDistrictChange === 'function') cfg.onDistrictChange(val, $kab.val() || '', $prov.val() || '', code);
            });

            $desa.off('change.wilayah').on('change.wilayah', function () {
                const val = $(this).val() || '';
                if (typeof cfg.onVillageChange === 'function') cfg.onVillageChange(val, $kec.val() || '', $kab.val() || '', $prov.val() || '');
            });

            (async function initProvinces() {
                resetSelect($prov, '⏳ Memuat Provinsi...', true);
                const list = await fetchCached('provinces', `${baseUrl}/provinces`);
                $prov.empty().append(new Option('-- Pilih Provinsi --', '', false, false));
                let matchedCode = null;
                list.forEach(p => {
                    provMap.set(p.name, p.code);
                    const sel = Boolean(cfg.initProv && p.name.toLowerCase() === cfg.initProv.toLowerCase());
                    if (sel) matchedCode = p.code;
                    $prov.append(new Option(p.name, p.name, sel, sel));
                });
                if (cfg.initProv && !matchedCode) {
                    $prov.append(new Option(cfg.initProv, cfg.initProv, true, true));
                }
                $prov.prop('disabled', false).trigger('change.select2');
                if (matchedCode) {
                    await loadRegencies(matchedCode, cfg.initKab || null, cfg.initKec || null, cfg.initDesa || null);
                }
            })();
        };
    </script>
    @stack('scripts')
</body>
</html>
