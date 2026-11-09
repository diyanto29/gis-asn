@extends('layouts.app')

@section('title', 'Hasil Perangkingan TOPSIS: ' . $unitTujuan->nama_unit)

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('topsis.index') }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Hasil Rekomendasi Mutasi ASN</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Unit Tujuan: <strong class="text-slate-800">{{ $unitTujuan->nama_unit }}</strong> &bull; Kuota Lowong: <strong class="text-emerald-700">{{ $unitTujuan->kuota_formasi_kosong }} Formasi</strong>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 bg-white rounded-lg text-xs font-semibold hover:bg-slate-50 transition shadow-sm flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Ranked Result Table Form -->
    <form method="POST" action="{{ route('topsis.simpan') }}">
        @csrf
        <input type="hidden" name="unit_tujuan_id" value="{{ $unitTujuan->id }}">

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm">Peringkat Kelayakan Rekomendasi (Nilai Preferensi $V_i$)</h2>
                    <p class="text-xs text-slate-500">Urutan ASN yang paling ideal dipindahkan mendekati domisili berdasarkan matriks multi-kriteria.</p>
                </div>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Simpan ke Usulan Baperjakat
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3.5 text-center">Rank</th>
                            <th class="px-4 py-3.5">Pegawai / NIP</th>
                            <th class="px-4 py-3.5">Unit Kerja Asal</th>
                            <th class="px-4 py-3.5 text-right">Jarak Asal</th>
                            @foreach ($kriteria as $code => $crit)
                                <th class="px-4 py-3.5 text-right">
                                    <span class="text-slate-900 font-bold">[{{ $code }}]</span> {{ $crit['nama'] }}
                                    <span class="block text-[10px] font-normal uppercase text-slate-400">
                                        {{ $crit['atribut'] }} &bull; {{ round($crit['bobot'] * 100) }}%
                                    </span>
                                </th>
                            @endforeach
                            <th class="px-4 py-3.5 text-right">Nilai Preferensi (Vi)</th>
                            <th class="px-4 py-3.5 text-center">Status Kuota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ranked as $item)
                            @php
                                $alt = $item['alternative'];
                                $isWithinQuota = ($item['rank'] <= $unitTujuan->kuota_formasi_kosong);
                            @endphp
                            <tr class="table-row-standard group {{ $isWithinQuota ? 'bg-emerald-50/30' : '' }}">
                                <td class="px-4 py-3.5 text-center font-extrabold text-base">
                                    <span class="w-7 h-7 rounded-full inline-flex items-center justify-center {{ $isWithinQuota ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' }} group-hover:scale-110 transition-transform">
                                        {{ $item['rank'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][id_asn]" value="{{ $alt['id'] }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][id_unit_asal]" value="{{ $alt['asn_model']->id_unit_saat_ini }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][jarak_asal_km]" value="{{ $alt['jarak_lama'] }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][jarak_baru_km]" value="{{ $alt['jarak_baru'] }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][selisih_hemat_jarak_km]" value="{{ $alt['hemat_jarak'] }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][nilai_preferensi_v]" value="{{ $item['v_value'] }}">
                                    <input type="hidden" name="ranks[{{ $loop->index }}][ranking]" value="{{ $item['rank'] }}">

                                    <div class="font-bold text-slate-900">{{ $alt['nama'] }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $alt['nip'] }}</div>
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-700">
                                    {{ $alt['unit_asal'] }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-medium text-slate-500">
                                    {{ number_format($alt['jarak_lama'], 2) }} km
                                </td>
                                @foreach ($kriteria as $code => $crit)
                                    <td class="px-4 py-3.5 text-right font-semibold text-slate-800">
                                        @php
                                            $val = $alt[$code] ?? null;
                                        @endphp
                                        @if (is_numeric($val))
                                            {{ number_format($val, 2) }}
                                        @else
                                            {{ $val ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3.5 text-right font-mono font-extrabold text-base text-blue-700">
                                    {{ number_format($item['v_value'], 6) }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if ($isWithinQuota)
                                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full">
                                            Prioritas Kuota (Lolos)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">
                                            Kandidat Cadangan
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + count($kriteria) }}" class="px-5 py-8 text-center text-slate-400">Tidak ada alternatif kandidat yang memenuhi syarat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- Transparansi Akademis: Matriks Audit TOPSIS (Accordion) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ openAudit: false }">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between cursor-pointer" onclick="document.getElementById('auditContent').classList.toggle('hidden')">
            <div class="flex items-center gap-2">
                <i data-lucide="file-code" class="w-5 h-5 text-indigo-600"></i>
                <h3 class="font-bold text-slate-900 text-sm">Transparansi Perhitungan Matematis TOPSIS (Audit Akademis / Skripsi)</h3>
            </div>
            <span class="text-xs text-blue-600 font-semibold hover:underline">Klik untuk Buka/Tutup Matriks</span>
        </div>

        <div id="auditContent" class="p-5 space-y-6 hidden">
            <!-- 1. Solusi Ideal Positif & Negatif -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">1. Solusi Ideal Positif ($A^+$) dan Negatif ($A^-$)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($kriteria as $code => $crit)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1">
                            <div class="font-bold text-slate-800">[{{ $code }}] {{ $crit['nama'] }}</div>
                            <div class="text-slate-500 text-[11px]">Sifat: <strong>{{ strtoupper($crit['atribut']) }}</strong></div>
                            <div class="text-emerald-700 font-mono">A+ : {{ number_format($details['ideal_positive'][$code] ?? 0, 6) }}</div>
                            <div class="text-rose-700 font-mono">A- : {{ number_format($details['ideal_negative'][$code] ?? 0, 6) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. Matriks Terbobot Y & Jarak Separasi D+, D- -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">2. Jarak Separasi Euclidean ($D^+$ & $D^-$)</h4>
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="px-3 py-2">Pegawai</th>
                                <th class="px-3 py-2 text-right">D+ (Jarak ke Ideal Positif)</th>
                                <th class="px-3 py-2 text-right">D- (Jarak ke Ideal Negatif)</th>
                                <th class="px-3 py-2 text-right">Nilai Vi = D- / (D+ + D-)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($ranked as $item)
                                <tr>
                                    <td class="px-3 py-2 font-sans font-medium">{{ $item['alternative']['nama'] }}</td>
                                    <td class="px-3 py-2 text-right text-rose-600">{{ number_format($item['d_positive'], 6) }}</td>
                                    <td class="px-3 py-2 text-right text-emerald-600">{{ number_format($item['d_negative'], 6) }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-blue-700">{{ number_format($item['v_value'], 6) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

