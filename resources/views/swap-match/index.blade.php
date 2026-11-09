@extends('layouts.app')

@section('title', 'Smart Swap Match — Tukar Guling Simetris (Zero-ABK)')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Smart Swap Match (Tukar Guling ASN)</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Zero-ABK Impact
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Mesin deteksi pasangan pegawai lintas instansi yang saling menguntungkan (Pareto Optimal) jika bertukar posisi tanpa mengubah jumlah formasi kantor asal & tujuan.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 bg-white rounded-lg text-xs font-semibold hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Usulan Swap
            </button>
        </div>
    </div>

    <!-- Impact Analytics Cards (BBM, Emisi Karbon, Jarak Bersama) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="interactive-card animate-card-slide bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between" style="animation-delay: 0.05s;">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pasangan Cocok (Match)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-blue-700">{{ $totalPasangan }} Pasang</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="repeat" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-slate-400">Kedua pihak sama-sama untung</div>
        </div>

        <div class="interactive-card animate-card-slide bg-white p-4 rounded-xl border border-indigo-200 shadow-xs flex flex-col justify-between" style="animation-delay: 0.1s;">
            <div class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">Total Efisiensi Jarak/Tahun</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-indigo-600">{{ number_format($totalHematTahunKm, 0) }} km</span>
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><i data-lucide="gauge" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-indigo-500 font-medium">Pengurangan mobilitas harian</div>
        </div>

        <div class="interactive-card animate-card-slide bg-white p-4 rounded-xl border border-emerald-200 shadow-xs flex flex-col justify-between" style="animation-delay: 0.15s;">
            <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Estimasi Hemat BBM Pegawai</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-emerald-600">Rp {{ number_format($totalHematBbmRp, 0, ',', '.') }}</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><i data-lucide="wallet" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-emerald-600 font-medium">Uang belanja BBM dihemat / thn</div>
        </div>

        <div class="interactive-card animate-card-slide bg-white p-4 rounded-xl border border-teal-200 shadow-xs flex flex-col justify-between" style="animation-delay: 0.2s;">
            <div class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Reduksi Emisi Karbon (CO₂)</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-teal-700">{{ number_format($totalReduksiCo2 / 1000, 2) }} Ton</span>
                <span class="p-2 bg-teal-50 text-teal-600 rounded-lg"><i data-lucide="leaf" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-1 text-[11px] text-teal-600 font-medium">Kontribusi Green GovTech</div>
        </div>
    </div>

    <!-- Swap Pairs List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="git-compare" class="w-4 h-4 text-blue-600"></i>
                Daftar Rekomendasi Pertukaran Simetris (Zero-ABK)
            </h2>
            <span class="text-xs text-slate-500">Urut dari efisiensi kumulatif tertinggi</span>
        </div>

        @forelse ($pairs as $index => $pair)
            <div class="interactive-card bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                
                <!-- Pair Header Badge & Total Savings -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-blue-700 text-white font-extrabold text-xs flex items-center justify-center">
                            #{{ $index + 1 }}
                        </span>
                        <div>
                            <span class="font-bold text-slate-900 text-sm">Pasangan Rekomendasi Tukar Guling</span>
                            <span class="text-xs text-slate-400 font-mono ml-2">Score Kompatibilitas: {{ $pair['compatibility_score'] }}%</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-extrabold text-xs">
                            Total Hemat: +{{ $pair['combined_save_daily_km'] }} km/hari (PP)
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-xs">
                            Hemat BBM: Rp {{ number_format($pair['est_hemat_bbm_rp'], 0, ',', '.') }}/thn
                        </span>
                    </div>
                </div>

                <!-- Side-by-side Visual Exchange -->
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-center">
                    
                    <!-- Pegawai A (3 cols) -->
                    <div class="md:col-span-3 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded">Pegawai A</span>
                            <span class="text-xs font-bold text-emerald-600 font-mono">Hemat {{ $pair['saveA'] }} km</span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 text-sm leading-tight">{{ $pair['asnA']->nama_lengkap }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $pair['asnA']->nip }} &bull; {{ $pair['asnA']->jabatan }}</div>
                        </div>
                        <div class="text-xs space-y-1 pt-1 border-t border-slate-200/60">
                            <div class="text-slate-600">Kantor Saat Ini: <strong>{{ $pair['unitA']->nama_unit }}</strong> (Jarak: {{ $pair['distA_current'] }} km)</div>
                            <div class="text-emerald-700 font-semibold">Tujuan Tukar: <strong>{{ $pair['unitB']->nama_unit }}</strong> (Jarak Baru: {{ $pair['distA_swap'] }} km)</div>
                            <div class="text-slate-400 text-[11px]">Domisili: {{ $pair['asnA']->kelurahan_desa }}, Kec. {{ $pair['asnA']->kecamatan }}</div>
                        </div>
                    </div>

                    <!-- Exchange Arrow Center (1 col) -->
                    <div class="md:col-span-1 flex flex-col items-center justify-center text-center py-2">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shadow-xs animate-float">
                            <i data-lucide="repeat" class="w-5 h-5"></i>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">TUKAR</span>
                        <span class="text-[9px] text-emerald-600 font-bold">ABK Imbang</span>
                    </div>

                    <!-- Pegawai B (3 cols) -->
                    <div class="md:col-span-3 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">Pegawai B</span>
                            <span class="text-xs font-bold text-emerald-600 font-mono">Hemat {{ $pair['saveB'] }} km</span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 text-sm leading-tight">{{ $pair['asnB']->nama_lengkap }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $pair['asnB']->nip }} &bull; {{ $pair['asnB']->jabatan }}</div>
                        </div>
                        <div class="text-xs space-y-1 pt-1 border-t border-slate-200/60">
                            <div class="text-slate-600">Kantor Saat Ini: <strong>{{ $pair['unitB']->nama_unit }}</strong> (Jarak: {{ $pair['distB_current'] }} km)</div>
                            <div class="text-emerald-700 font-semibold">Tujuan Tukar: <strong>{{ $pair['unitA']->nama_unit }}</strong> (Jarak Baru: {{ $pair['distB_swap'] }} km)</div>
                            <div class="text-slate-400 text-[11px]">Domisili: {{ $pair['asnB']->kelurahan_desa }}, Kec. {{ $pair['asnB']->kecamatan }}</div>
                        </div>
                    </div>

                </div>

                <!-- Footer Action Buttons -->
                <div class="pt-2 flex flex-wrap items-center justify-between gap-3 text-xs border-t border-slate-100">
                    <div class="text-slate-500 text-[11px]">
                        Reduksi Emisi Karbon: <strong>{{ $pair['est_reduksi_co2_kg'] }} kg CO₂ / tahun</strong>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="https://www.google.com/maps/dir/?api=1&origin={{ $pair['asnA']->domisili_lat }},{{ $pair['asnA']->domisili_lng }}&destination={{ $pair['unitB']->kantor_lat }},{{ $pair['unitB']->kantor_lng }}&travelmode=driving" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition">
                            🚗 Rute Baru Pegawai A ↗
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&origin={{ $pair['asnB']->domisili_lat }},{{ $pair['asnB']->domisili_lng }}&destination={{ $pair['unitA']->kantor_lat }},{{ $pair['unitA']->kantor_lng }}&travelmode=driving" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition">
                            🚗 Rute Baru Pegawai B ↗
                        </a>
                        <button onclick="alert('Usulan paket tukar guling telah ditandai untuk dimasukkan ke draf agenda sidang Baperjakat berikutnya!')" class="px-3.5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-lg shadow-xs transition">
                            Ajukan Tukar Guling
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="p-8 text-center text-slate-400 bg-white rounded-xl border border-dashed border-slate-200">
                Belum ditemukan pasangan tukar guling yang saling menguntungkan dari data saat ini.
            </div>
        @endforelse
    </div>

</div>
@endsection

