@extends('layouts.app')

@section('title', 'Sistem Pendukung Keputusan (SPK) TOPSIS Mutasi ASN')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sistem Pendukung Keputusan (SPK) Mutasi ASN</h1>
            <p class="text-sm text-slate-500 mt-1">
                Kombinasi Formula Haversine Spasial & Algoritma TOPSIS dengan Pembobotan Kriteria Dinamis (Dynamic Weighting).
            </p>
        </div>
        <a href="{{ route('topsis.riwayat') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition shadow-sm">
            <i data-lucide="archive" class="w-4 h-4"></i> Riwayat Usulan Baperjakat
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1: Pemilihan Unit Kerja Tujuan Mutasi -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                        <i data-lucide="target" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-base">Pilih Unit Kerja Tujuan Mutasi</h2>
                        <p class="text-xs text-slate-500">Sistem hanya menampilkan unit kerja yang memiliki kuota formasi lowong (ABK &gt; 0).</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('topsis.proses') }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Pilih Instansi / Unit Kerja Sasaran</label>
                            <div class="space-y-2.5">
                                @forelse ($targetUnits as $unit)
                                    <label class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="unit_tujuan_id" value="{{ $unit->id }}" required class="text-blue-600 focus:ring-blue-500 w-4 h-4" {{ $loop->first ? 'checked' : '' }}>
                                            <div>
                                                <div class="font-bold text-slate-900 text-sm">{{ $unit->nama_unit }}</div>
                                                <div class="text-xs text-slate-500">{{ $unit->kategori }} &bull; {{ $unit->alamat_kantor }}</div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full">
                                                {{ $unit->kuota_formasi_kosong }} Formasi Kosong
                                            </span>
                                            <div class="text-[10px] text-slate-400 mt-1 font-mono">
                                                {{ number_format($unit->kantor_lat, 4) }}, {{ number_format($unit->kantor_lng, 4) }}
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                        Semua formasi unit kerja saat ini terisi penuh. Silakan update kuota di menu Formasi Unit.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3">
                            <button type="submit" class="w-full py-3 bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <i data-lucide="play" class="w-4 h-4"></i> Eksekusi Algoritma TOPSIS
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Column 2: Pengaturan Bobot Kriteria Dinamis (Dynamic Weighting & Flexible 3-5 Criteria) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-indigo-600"></i>
                        <h3 class="font-bold text-slate-900 text-sm">Komponen Kriteria Dinamis</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('master-kriteria.index') }}" class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1 border border-slate-300 shadow-xs" title="Buka Halaman Manajemen Master Kriteria">
                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-blue-600"></i> Kelola di Master
                        </a>
                        <button type="button" onclick="openAddCriteriaModal()" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-xs">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Tambah Kriteria
                        </button>
                    </div>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Komponen kriteria dapat <strong>ditambah, dikurangi, atau dinonaktifkan (3, 4, 5, atau lebih kriteria)</strong> sesuai instrumen kebijakan BKPSDM. Total bobot kriteria aktif <strong>wajib tepat 100% (1.00)</strong>.
                </p>

                <!-- Quick Policy Presets (3 Kriteria, 4 Kriteria, 5 Kriteria) -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Preset Simulasi Komponen:</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" onclick="applyPreset('three')" class="px-2 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold rounded-lg text-center transition border border-slate-200">
                            3 Kriteria <span class="block text-[9px] font-normal text-slate-500">(Jarak, Hemat, Masa)</span>
                        </button>
                        <button type="button" onclick="applyPreset('four')" class="px-2 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 text-[11px] font-bold rounded-lg text-center transition border border-blue-200">
                            4 Kriteria <span class="block text-[9px] font-normal text-blue-600">(Standar BKPSDM)</span>
                        </button>
                        <button type="button" onclick="applyPreset('five')" class="px-2 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-lg text-center transition border border-emerald-200">
                            5 Kriteria <span class="block text-[9px] font-normal text-emerald-600">(+ SKP Kinerja)</span>
                        </button>
                    </div>
                </div>

                <form id="weightForm" method="POST" action="{{ route('topsis.update-bobot') }}" class="space-y-3.5">
                    @csrf

                    <div class="space-y-2.5">
                        @foreach ($kriteria as $k)
                            <div class="p-3 rounded-xl border transition {{ $k->is_active ? 'bg-slate-50 border-slate-200' : 'bg-slate-100/60 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-extrabold text-slate-900">
                                            [{{ $k->kode_kriteria }}] {{ $k->nama_kriteria }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $k->atribut === 'cost' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ strtoupper($k->atribut) }}
                                        </span>
                                        @if ($k->is_active)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-600">Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-[11px] text-slate-500 mb-2">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="database" class="w-3 h-3 text-slate-400"></i>
                                        Param: <code class="font-mono text-slate-700 bg-white px-1 py-0.5 rounded border border-slate-200">{{ $k->parameter_key }}</code>
                                    </span>
                                    
                                    <!-- Toggle & Delete Actions -->
                                    <div class="flex items-center gap-2">
                                        <button 
                                            type="button" 
                                            onclick="event.preventDefault(); document.getElementById('toggle_form_{{ $k->id }}').submit();" 
                                            class="text-[10px] font-bold {{ $k->is_active ? 'text-amber-700 hover:text-amber-900' : 'text-blue-700 hover:text-blue-900' }} hover:underline"
                                        >
                                            {{ $k->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                        @if ($k->kode_kriteria !== 'C1')
                                            <span class="text-slate-300">&bull;</span>
                                            <button 
                                                type="button" 
                                                onclick="if(confirm('Hapus kriteria [{{ $k->kode_kriteria }}] {{ $k->nama_kriteria }}?')) { document.getElementById('delete_form_{{ $k->id }}').submit(); }" 
                                                class="text-[10px] font-semibold text-rose-600 hover:text-rose-800 hover:underline"
                                            >
                                                Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if ($k->is_active)
                                    <div class="flex items-center gap-2 pt-1">
                                        <!-- Stepper Minus -->
                                        <button type="button" onclick="stepWeight('weight_{{ $k->id }}', -0.05)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold flex items-center justify-center transition shadow-xs">
                                            -
                                        </button>

                                        <!-- Number Input -->
                                        <div class="relative flex-1">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0" 
                                                max="1" 
                                                id="weight_{{ $k->id }}"
                                                data-kode="{{ $k->kode_kriteria }}"
                                                name="bobot[{{ $k->id }}]" 
                                                value="{{ $k->bobot }}" 
                                                class="weight-input w-full text-sm font-extrabold font-mono border border-slate-300 rounded-lg p-2 text-center focus:ring-2 focus:ring-indigo-500 bg-white"
                                            >
                                        </div>

                                        <!-- Stepper Plus -->
                                        <button type="button" onclick="stepWeight('weight_{{ $k->id }}', 0.05)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold flex items-center justify-center transition shadow-xs">
                                            +
                                        </button>

                                        <!-- Percent Tag -->
                                        <span id="percent_{{ $k->id }}" class="w-14 text-right font-mono font-bold text-xs text-slate-700">
                                            {{ round($k->bobot * 100) }}%
                                        </span>
                                    </div>
                                @else
                                    <div class="p-2 bg-slate-200/50 rounded-lg text-center text-[11px] text-slate-500 italic">
                                        Kriteria ini dinonaktifkan (diabaikan dalam perhitungan matriks TOPSIS).
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Live Total Weight Meter (Active Criteria Only) -->
                    <div class="p-3.5 rounded-xl border bg-slate-50 border-slate-200 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                                <i data-lucide="calculator" class="w-4 h-4 text-slate-400"></i>
                                Akumulasi Bobot Kriteria Aktif:
                            </span>
                            <span id="totalWeightBadge" class="font-extrabold text-sm text-emerald-600">
                                {{ number_format($totalBobot * 100, 0) }}% ({{ number_format($totalBobot, 2) }})
                            </span>
                        </div>

                        <!-- Progress Bar Visualizer -->
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div id="totalWeightBar" class="h-full bg-emerald-500 transition-all duration-300 rounded-full" style="width: {{ min(100, $totalBobot * 100) }}%;"></div>
                        </div>

                        <div id="validationMessage" class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Total tepat 100%. Kaidah matematis TOPSIS terpenuhi.
                        </div>
                    </div>

                    <!-- Action Buttons: Auto-Normalize & Submit -->
                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" onclick="autoNormalize()" class="px-3 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shrink-0" title="Ratakan proporsional agar total tepat 100%">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Auto-Normalize 100%
                        </button>
                        
                        <button type="submit" id="submitWeightBtn" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i> Simpan Bobot Aktif
                        </button>
                    </div>
                </form>
            </div>

            <!-- Thesis Defense Academic Guide Card -->
            <div class="bg-gradient-to-br from-indigo-50/60 to-blue-50/60 rounded-xl border border-indigo-100 p-4 text-xs space-y-2.5">
                <div class="flex items-center gap-2 text-indigo-900 font-bold text-xs">
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-indigo-600"></i>
                    <span>Kaidah Akademik Skripsi: Fleksibilitas 3-5 Kriteria</span>
                </div>
                <p class="text-slate-600 leading-relaxed text-[11px]">
                    <strong>Fleksibilitas Jumlah Kriteria ($n$ Dimensi):</strong><br>
                    Algoritma TOPSIS bekerja pada ruang vektor berdimensi-$n$ ($m$ alternatif $\times$ $n$ kriteria). Berapapun jumlah kriteria yang diaktifkan (3, 4, atau 5 kriteria), perhitungan normalisasi matriks $R$, solusi ideal $A^+/A^-$, dan separasi Euclidean $D^+/D^-$ tetap valid dan konsisten, asalkan total bobot $\sum_{j=1}^n w_j = 1.00$.
                </p>
            </div>
        </div>

    </div>

</div>

<!-- Hidden Standalone Forms for Toggle and Delete Criteria -->
@foreach ($kriteria as $k)
    <form id="toggle_form_{{ $k->id }}" action="{{ route('topsis.kriteria.toggle', $k->id) }}" method="POST" class="hidden">
        @csrf
    </form>
    @if ($k->kode_kriteria !== 'C1')
        <form id="delete_form_{{ $k->id }}" action="{{ route('topsis.kriteria.destroy', $k->id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endforeach

<!-- Modal Tambah Kriteria Baru -->
<div id="addCriteriaModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-card-slide space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Kriteria TOPSIS Baru</h3>
            </div>
            <button type="button" onclick="closeAddCriteriaModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('topsis.kriteria.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Kriteria</label>
                <input type="text" name="nama_kriteria" required placeholder="Contoh: Nilai Capaian Kinerja (SKP ASN)" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Sumber Data Parameter</label>
                <select name="parameter_key" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500">
                    <option value="nilai_skp">📋 Nilai Capaian Kinerja Pegawai (SKP ASN)</option>
                    <option value="usia_pegawai">🎂 Usia Pegawai (Tahun - Dihitung dari NIP)</option>
                    <option value="tanggungan_keluarga">👥 Jumlah Tanggungan Keluarga</option>
                    <option value="masa_kerja">⏳ Masa Kerja Pegawai (Tahun)</option>
                    <option value="pangkat_golongan">🎖️ Pangkat / Golongan Ruang</option>
                    <option value="jarak_baru">📍 Jarak Domisili ke Unit Baru (Km)</option>
                    <option value="hemat_jarak">📐 Selisih Penghematan Jarak (Km)</option>
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Data numerik alternatif akan ditarik otomatis dari master pegawai.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Sifat Atribut</label>
                    <select name="atribut" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500">
                        <option value="benefit">BENEFIT (Makin tinggi makin baik)</option>
                        <option value="cost">COST (Makin rendah makin baik)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Bobot Awal</label>
                    <input type="number" step="0.01" min="0" max="1" name="bobot" value="0.15" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 text-center font-bold focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Keterangan Singkat</label>
                <textarea name="keterangan" rows="2" placeholder="Deskripsi pertimbangan kriteria..." class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeAddCriteriaModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Tambahkan Kriteria
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputs = document.querySelectorAll('.weight-input');
        const badge = document.getElementById('totalWeightBadge');
        const bar = document.getElementById('totalWeightBar');
        const msg = document.getElementById('validationMessage');
        const submitBtn = document.getElementById('submitWeightBtn');

        window.openAddCriteriaModal = function() {
            document.getElementById('addCriteriaModal').classList.remove('hidden');
        };

        window.closeAddCriteriaModal = function() {
            document.getElementById('addCriteriaModal').classList.add('hidden');
        };

        function updateTotal() {
            let sum = 0;
            inputs.forEach(input => {
                const val = parseFloat(input.value) || 0;
                sum += val;
                
                // Update percent badge next to input
                const id = input.id.replace('weight_', '');
                const percentLabel = document.getElementById('percent_' + id);
                if (percentLabel) {
                    percentLabel.innerText = Math.round(val * 100) + '%';
                }
            });

            sum = Math.round(sum * 100) / 100;
            const percent = Math.round(sum * 100);

            badge.innerText = percent + '% (' + sum.toFixed(2) + ')';
            bar.style.width = Math.min(100, Math.max(0, percent)) + '%';

            if (sum >= 0.99 && sum <= 1.01) {
                badge.className = 'font-extrabold text-sm text-emerald-600';
                bar.className = 'h-full bg-emerald-500 transition-all duration-300 rounded-full';
                msg.innerHTML = '<i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i> <span class="text-emerald-700 font-semibold">Total kriteria aktif tepat 100%. Sesuai kaidah ilmiah TOPSIS.</span>';
                submitBtn.disabled = false;
            } else {
                badge.className = 'font-extrabold text-sm text-rose-600 animate-pulse';
                bar.className = 'h-full bg-rose-500 transition-all duration-300 rounded-full';
                const selisih = Math.round((1 - sum) * 100);
                const ket = selisih > 0 ? `Kurang ${selisih}%` : `Kelebihan ${Math.abs(selisih)}%`;
                msg.innerHTML = `<i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i> <span class="text-rose-600 font-bold">${ket}! Total kriteria aktif wajib 100%. Klik 'Auto-Normalize'.</span>`;
                submitBtn.disabled = true;
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        window.stepWeight = function (inputId, delta) {
            const input = document.getElementById(inputId);
            if (!input) return;
            let current = parseFloat(input.value) || 0;
            current = Math.max(0, Math.min(1, Math.round((current + delta) * 100) / 100));
            input.value = current.toFixed(2);
            updateTotal();
        };

        window.applyPreset = function (type) {
            const presets = {
                three: { C1: 0.50, C4: 0.30, C2: 0.20, C3: 0.00, C5: 0.00 },
                four:  { C1: 0.35, C4: 0.25, C2: 0.25, C3: 0.15, C5: 0.00 },
                five:  { C1: 0.30, C4: 0.20, C2: 0.20, C3: 0.15, C5: 0.15 }
            };

            const selected = presets[type];
            if (!selected) return;

            inputs.forEach(input => {
                const kode = input.getAttribute('data-kode');
                if (selected[kode] !== undefined) {
                    input.value = selected[kode].toFixed(2);
                }
            });

            updateTotal();
        };

        window.autoNormalize = function () {
            let sum = 0;
            inputs.forEach(i => sum += (parseFloat(i.value) || 0));

            if (sum <= 0) {
                const even = (1.0 / inputs.length);
                inputs.forEach(i => i.value = even.toFixed(2));
            } else {
                let accumulated = 0;
                const count = inputs.length;
                inputs.forEach((input, index) => {
                    const current = parseFloat(input.value) || 0;
                    if (index === count - 1) {
                        const remaining = Math.round((1.0 - accumulated) * 100) / 100;
                        input.value = Math.max(0, remaining).toFixed(2);
                    } else {
                        const normalized = Math.round((current / sum) * 100) / 100;
                        accumulated += normalized;
                        input.value = normalized.toFixed(2);
                    }
                });
            }

            updateTotal();
        };

        inputs.forEach(i => i.addEventListener('input', updateTotal));
        updateTotal(); // Initial run
    });
</script>
@endpush

