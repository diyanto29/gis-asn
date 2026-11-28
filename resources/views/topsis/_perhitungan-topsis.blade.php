@if (!empty($topsisResult) && !empty($topsisResult['details']) && !empty($alternativesList))
@php
    $details = $topsisResult['details'];
    $divisors = $details['divisors'] ?? [];
    $matrixR = $details['matrix_r'] ?? [];
    $matrixY = $details['matrix_y'] ?? [];
    $idealPos = $details['ideal_positive'] ?? [];
    $idealNeg = $details['ideal_negative'] ?? [];
@endphp

<!-- Tombol & Panel Riwayat Rincian Perhitungan Matematis TOPSIS -->
<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="calculator" class="w-4 h-4 text-blue-700"></i>
                Rincian Perhitungan Matematis TOPSIS (Audit Trail)
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Transparansi tahapan normalisasi Euclidean (Matriks X &rarr; R &rarr; Y &rarr; A<sup>+</sup>/A<sup>&minus;</sup> &rarr; D<sup>+</sup>/D<sup>&minus;</sup> &rarr; V<sub>i</sub>) terhadap <strong>{{ count($alternativesList) }}</strong> alternatif.
            </p>
        </div>
        <button type="button" id="btnToggleTopsisCalc" onclick="toggleTopsisCalculation()"
            class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs inline-flex items-center gap-2">
            <i data-lucide="chevron-down" id="iconToggleTopsisCalc" class="w-4 h-4 transition-transform"></i>
            <span id="labelToggleTopsisCalc">Tampilkan Perhitungan TOPSIS</span>
        </button>
    </div>

    <div id="topsisCalculationPanel" class="hidden p-5 space-y-6 bg-slate-50/40">

        <!-- LANGKAH 1: Matriks Keputusan (X) & Pembagi Euclidean -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-800">Langkah 1 &bull; Matriks Keputusan Awal (X) &amp; Pembagi Normalisasi Euclidean (|X<sub>j</sub>|)</span>
                <span class="text-[11px] font-mono text-slate-500">|X<sub>j</sub>| = &radic;(&Sigma; x<sub>ij</sub>&sup2;)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200 text-[11px]">
                        <tr>
                            <th class="px-3 py-2.5 w-10 text-center">No</th>
                            <th class="px-3 py-2.5">Alternatif</th>
                            @foreach ($kriteriaModels as $k)
                                <th class="px-3 py-2.5 text-right font-mono">
                                    {{ $k->kode_kriteria }}
                                    <span class="block text-[9px] font-normal {{ $k->atribut === 'cost' ? 'text-amber-700' : 'text-emerald-700' }}">
                                        {{ strtoupper($k->atribut) }} ({{ round($k->bobot * 100) }}%)
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($alternativesList as $idx => $alt)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2 text-center font-mono text-slate-400">A{{ $idx + 1 }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}</td>
                                @foreach ($kriteriaModels as $k)
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format((float)($alt[$k->kode_kriteria] ?? 0), 2) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="bg-blue-50/70 font-bold text-blue-900 border-t-2 border-blue-200">
                            <td class="px-3 py-2.5 text-center font-mono" colspan="2">Pembagi Euclidean |X<sub>j</sub>|</td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float)($divisors[$k->kode_kriteria] ?? 1), 4) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LANGKAH 2: Matriks Ternormalisasi (R) -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-800">Langkah 2 &bull; Matriks Ternormalisasi (R)</span>
                <span class="text-[11px] font-mono text-slate-500">r<sub>ij</sub> = x<sub>ij</sub> / |X<sub>j</sub>|</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200 text-[11px]">
                        <tr>
                            <th class="px-3 py-2.5 w-10 text-center">No</th>
                            <th class="px-3 py-2.5">Alternatif</th>
                            @foreach ($kriteriaModels as $k)
                                <th class="px-3 py-2.5 text-right font-mono">{{ $k->kode_kriteria }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($alternativesList as $idx => $alt)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2 text-center font-mono text-slate-400">A{{ $idx + 1 }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}</td>
                                @foreach ($kriteriaModels as $k)
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format((float)($matrixR[$idx][$k->kode_kriteria] ?? 0), 4) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LANGKAH 3: Matriks Terbobot (Y) + Solusi Ideal Positif (A+) & Negatif (A-) -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-800">Langkah 3 &amp; 4 &bull; Matriks Terbobot (Y) &amp; Solusi Ideal Positif (A<sup>+</sup>) / Negatif (A<sup>&minus;</sup>)</span>
                <span class="text-[11px] font-mono text-slate-500">y<sub>ij</sub> = w<sub>j</sub> &times; r<sub>ij</sub></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200 text-[11px]">
                        <tr>
                            <th class="px-3 py-2.5 w-10 text-center">No</th>
                            <th class="px-3 py-2.5">Alternatif</th>
                            @foreach ($kriteriaModels as $k)
                                <th class="px-3 py-2.5 text-right font-mono">{{ $k->kode_kriteria }} (w={{ number_format($k->bobot, 2) }})</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($alternativesList as $idx => $alt)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2 text-center font-mono text-slate-400">A{{ $idx + 1 }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}</td>
                                @foreach ($kriteriaModels as $k)
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format((float)($matrixY[$idx][$k->kode_kriteria] ?? 0), 4) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="bg-emerald-50/80 font-bold text-emerald-900 border-t-2 border-emerald-200">
                            <td class="px-3 py-2.5 text-center font-mono" colspan="2">Solusi Ideal Positif (A<sup>+</sup>)</td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float)($idealPos[$k->kode_kriteria] ?? 0), 4) }}</td>
                            @endforeach
                        </tr>
                        <tr class="bg-rose-50/80 font-bold text-rose-900">
                            <td class="px-3 py-2.5 text-center font-mono" colspan="2">Solusi Ideal Negatif (A<sup>&minus;</sup>)</td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float)($idealNeg[$k->kode_kriteria] ?? 0), 4) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LANGKAH 5 & 6: Jarak Separasi (D+, D-) & Nilai Preferensi (Vi) -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-800">Langkah 5 &amp; 6 &bull; Jarak Separasi (D<sup>+</sup>, D<sup>&minus;</sup>), Nilai Preferensi (V<sub>i</sub>), &amp; Ranking Akhir</span>
                <span class="text-[11px] font-mono text-slate-500">V<sub>i</sub> = D<sub>i</sub><sup>&minus;</sup> / (D<sub>i</sub><sup>+</sup> + D<sub>i</sub><sup>&minus;</sup>)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200 text-[11px]">
                        <tr>
                            <th class="px-3 py-2.5 text-center w-14">Rank</th>
                            <th class="px-3 py-2.5">Alternatif</th>
                            <th class="px-3 py-2.5 text-right font-mono">Jarak Ideal Positif (D<sup>+</sup>)</th>
                            <th class="px-3 py-2.5 text-right font-mono">Jarak Ideal Negatif (D<sup>&minus;</sup>)</th>
                            <th class="px-3 py-2.5 text-right font-mono text-blue-900">Nilai Preferensi (V<sub>i</sub>)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($ranked as $r)
                            <tr class="hover:bg-slate-50/70 {{ $r['rank'] === 1 ? 'bg-amber-50/40' : '' }}">
                                <td class="px-3 py-2 text-center font-extrabold {{ $r['rank'] === 1 ? 'text-amber-600' : 'text-slate-600' }}">#{{ $r['rank'] }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-800">{{ $r['alternative']['label_alt'] ?? ($r['unit']->nama_unit ?? ($r['asn']->nama ?? '-')) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-rose-700">{{ number_format((float)($r['d_positive'] ?? 0), 6) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-emerald-700">{{ number_format((float)($r['d_negative'] ?? 0), 6) }}</td>
                                <td class="px-3 py-2 text-right font-mono font-extrabold text-blue-900">{{ number_format((float)($r['vi'] ?? $r['v_value'] ?? 0), 6) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    window.toggleTopsisCalculation = function () {
        const panel = document.getElementById('topsisCalculationPanel');
        const label = document.getElementById('labelToggleTopsisCalc');
        const icon = document.getElementById('iconToggleTopsisCalc');
        if (!panel) return;
        const isHidden = panel.classList.toggle('hidden');
        if (label) label.textContent = isHidden ? 'Tampilkan Perhitungan TOPSIS' : 'Sembunyikan Perhitungan TOPSIS';
        if (icon) icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
        if (!isHidden) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
</script>
@endpush
@endif
