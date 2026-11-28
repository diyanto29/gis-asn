@if (!empty($topsisResult) && !empty($topsisResult['details']) && !empty($alternativesList))
@php
    $details = $topsisResult['details'];
    $divisors = $details['divisors'] ?? [];
    $matrixR = $details['matrix_r'] ?? [];
    $matrixY = $details['matrix_y'] ?? [];
    $idealPos = $details['ideal_positive'] ?? [];
    $idealNeg = $details['ideal_negative'] ?? [];

    $bobotSummary = $kriteriaModels->map(fn($k) => $k->nama_kriteria . ' ' . round($k->bobot * 100) . '%')->implode(' · ');
@endphp

<!-- Rincian Tahap TOPSIS (6 Tab Horizontal) -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-base font-extrabold text-slate-900">Rincian tahap TOPSIS</h3>
        <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
            {{ count($alternativesList) }} Alternatif &bull; {{ $kriteriaModels->count() }} Kriteria
        </span>
    </div>

    <!-- 6 Tab Navigasi Horizontal -->
    <div class="flex items-center gap-1 overflow-x-auto border-b border-slate-200 pb-0" role="tablist">
        @php
            $topsisTabs = [
                'matriks' => 'Matriks keputusan',
                'normalisasi' => 'Normalisasi',
                'terbobot' => 'Ternormalisasi terbobot',
                'ideal' => 'Solusi ideal',
                'jarak' => 'Jarak ideal',
                'preferensi' => 'Preferensi & ranking',
            ];
        @endphp
        @foreach ($topsisTabs as $tabKey => $tabLabel)
            <button type="button"
                id="tabBtn_{{ $tabKey }}"
                onclick="switchTopsisTab('{{ $tabKey }}')"
                class="topsis-tab-btn whitespace-nowrap px-4 py-2.5 text-xs font-bold rounded-t-lg border-b-2 transition {{ $loop->first ? 'bg-sky-100/80 text-blue-700 border-blue-600' : 'text-slate-500 border-transparent hover:text-slate-800 hover:bg-slate-50' }}">
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    <!-- Ringkasan Bobot Aktif -->
    <p class="text-xs text-slate-500 leading-relaxed">
        <span class="font-semibold text-slate-600">Bobot:</span> {{ $bobotSummary }}
    </p>

    <!-- TAB 1: Matriks keputusan (X) -->
    <div id="tabPane_matriks" class="topsis-tab-pane">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Alternatif</th>
                        @foreach ($kriteriaModels as $k)
                            <th class="px-4 py-3 text-right whitespace-nowrap" title="{{ $k->kode_kriteria }} ({{ strtoupper($k->atribut) }})">
                                {{ $k->nama_kriteria }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($alternativesList as $idx => $alt)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}
                            </td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-4 py-3 text-right font-mono">
                                    {{ number_format((float)($alt[$k->kode_kriteria] ?? 0), 4, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="bg-sky-50/70 font-bold text-blue-900 border-t border-sky-200">
                        <td class="px-4 py-2.5">Pembagi Euclidean (|X<sub>j</sub>|)</td>
                        @foreach ($kriteriaModels as $k)
                            <td class="px-4 py-2.5 text-right font-mono">
                                {{ number_format((float)($divisors[$k->kode_kriteria] ?? 1), 4, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: Normalisasi (R) -->
    <div id="tabPane_normalisasi" class="topsis-tab-pane hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Alternatif</th>
                        @foreach ($kriteriaModels as $k)
                            <th class="px-4 py-3 text-right whitespace-nowrap">{{ $k->nama_kriteria }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($alternativesList as $idx => $alt)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}
                            </td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-4 py-3 text-right font-mono">
                                    {{ number_format((float)($matrixR[$idx][$k->kode_kriteria] ?? 0), 4, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: Ternormalisasi terbobot (Y) -->
    <div id="tabPane_terbobot" class="topsis-tab-pane hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Alternatif</th>
                        @foreach ($kriteriaModels as $k)
                            <th class="px-4 py-3 text-right whitespace-nowrap">
                                {{ $k->nama_kriteria }} ({{ round($k->bobot * 100) }}%)
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($alternativesList as $idx => $alt)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $alt['label_alt'] ?? ($alt['nama_unit'] ?? ($alt['nama'] ?? '-')) }}
                            </td>
                            @foreach ($kriteriaModels as $k)
                                <td class="px-4 py-3 text-right font-mono">
                                    {{ number_format((float)($matrixY[$idx][$k->kode_kriteria] ?? 0), 4, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: Solusi ideal (A+ dan A-) -->
    <div id="tabPane_ideal" class="topsis-tab-pane hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Kriteria</th>
                        <th class="px-4 py-3 text-center">Atribut</th>
                        <th class="px-4 py-3 text-right">Bobot</th>
                        <th class="px-4 py-3 text-right text-emerald-800">Solusi Ideal Positif (A<sup>+</sup>)</th>
                        <th class="px-4 py-3 text-right text-rose-800">Solusi Ideal Negatif (A<sup>&minus;</sup>)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($kriteriaModels as $k)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                <span class="font-mono text-blue-700 mr-1">[{{ $k->kode_kriteria }}]</span>
                                {{ $k->nama_kriteria }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $k->atribut === 'cost' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                                    {{ $k->atribut }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ round($k->bobot * 100) }}%</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">
                                {{ number_format((float)($idealPos[$k->kode_kriteria] ?? 0), 4, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-rose-700">
                                {{ number_format((float)($idealNeg[$k->kode_kriteria] ?? 0), 4, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 5: Jarak ideal (D+ dan D-) -->
    <div id="tabPane_jarak" class="topsis-tab-pane hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Alternatif</th>
                        <th class="px-4 py-3 text-right text-rose-800">Jarak Ideal Positif (D<sup>+</sup>)</th>
                        <th class="px-4 py-3 text-right text-emerald-800">Jarak Ideal Negatif (D<sup>&minus;</sup>)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($ranked as $r)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $r['alternative']['label_alt'] ?? ($r['unit']->nama_unit ?? ($r['asn']->nama ?? '-')) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-rose-700">
                                {{ number_format((float)($r['d_positive'] ?? 0), 6, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-700">
                                {{ number_format((float)($r['d_negative'] ?? 0), 6, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 6: Preferensi & ranking (Vi) -->
    <div id="tabPane_preferensi" class="topsis-tab-pane hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-center w-16">Rank</th>
                        <th class="px-4 py-3">Alternatif</th>
                        <th class="px-4 py-3 text-right">D<sup>+</sup></th>
                        <th class="px-4 py-3 text-right">D<sup>&minus;</sup></th>
                        <th class="px-4 py-3 text-right text-blue-900">Nilai Preferensi (V<sub>i</sub>)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($ranked as $r)
                        <tr class="hover:bg-slate-50/70 {{ $r['rank'] === 1 ? 'bg-sky-50/50' : '' }}">
                            <td class="px-4 py-3 text-center font-extrabold {{ $r['rank'] === 1 ? 'text-blue-700' : 'text-slate-600' }}">
                                #{{ $r['rank'] }}
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $r['alternative']['label_alt'] ?? ($r['unit']->nama_unit ?? ($r['asn']->nama ?? '-')) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-rose-700">
                                {{ number_format((float)($r['d_positive'] ?? 0), 6, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-700">
                                {{ number_format((float)($r['d_negative'] ?? 0), 6, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-extrabold text-blue-900">
                                {{ number_format((float)($r['vi'] ?? $r['v_value'] ?? 0), 6, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.switchTopsisTab = function (key) {
        document.querySelectorAll('.topsis-tab-pane').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.topsis-tab-btn').forEach(btn => {
            btn.classList.remove('bg-sky-100/80', 'text-blue-700', 'border-blue-600');
            btn.classList.add('text-slate-500', 'border-transparent');
        });

        const activePane = document.getElementById('tabPane_' + key);
        const activeBtn = document.getElementById('tabBtn_' + key);
        if (activePane) activePane.classList.remove('hidden');
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-500', 'border-transparent');
            activeBtn.classList.add('bg-sky-100/80', 'text-blue-700', 'border-blue-600');
        }
    };
</script>
@endpush
@endif
