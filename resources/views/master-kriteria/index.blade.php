@extends('layouts.app')

@section('title', 'Master Kriteria & Pembobotan TOPSIS')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Master Kriteria & Pembobotan TOPSIS</h1>
            <p class="text-sm text-slate-500 mt-1">
                Pengelolaan komponen kriteria keputusan, atribut (Cost/Benefit), dan rasio pembobotan dinamis (Dynamic Weighting).
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openAddCriteriaModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i> Tambah Kriteria Baru
            </button>
        </div>
    </div>

    <!-- Pengaturan Aturan Mutasi Dinamis (Syarat Mutlak & Keluar Daerah) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <form method="POST" action="{{ route('master-kriteria.update-aturan') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            @csrf
            <div class="flex-1">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-700"></i>
                    Aturan Mutasi Dinamis (Syarat Mutlak Sistem &amp; Keluar Daerah)
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Mutasi dalam instansi mewajibkan kesesuaian jabatan (Fungsional wajib sama nama &amp; jenjang; Pelaksana fleksibel sama/turun/naik dengan tingkat pendidikan sesuai), formasi tersedia (lowong &gt; 0), dan masa jabatan minimal. Aturan batas tahun dapat diubah dinamis.
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-3 shrink-0">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Min. Masa Jabatan (Thn)</label>
                    <input type="number" step="0.5" min="0" max="20" name="min_masa_jabatan_tahun"
                        value="{{ $mutasiRules['min_masa_jabatan_tahun'] ?? 2 }}"
                        class="w-28 px-2.5 py-1.5 text-xs font-bold text-slate-800 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Min. Masa Kerja Keluar (Thn)</label>
                    <input type="number" step="1" min="1" max="30" name="min_masa_kerja_keluar_tahun"
                        value="{{ $mutasiRules['min_masa_kerja_keluar_tahun'] ?? 5 }}"
                        class="w-32 px-2.5 py-1.5 text-xs font-bold text-slate-800 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                </div>
                <button type="submit" class="px-3.5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-bold transition shadow-xs h-[32px] inline-flex items-center gap-1.5">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i> Simpan Aturan
                </button>
            </div>
        </form>
    </div>

    <!-- Mode Switcher Tabs -->
    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 pb-3">
        <a href="{{ route('master-kriteria.index', ['mode' => 'pns_to_dinas']) }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $currentMode === 'pns_to_dinas' ? 'bg-blue-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
            <i data-lucide="user-check" class="w-4 h-4"></i>
            <span>Mode 1: PNS to Dinas (Pegawai Cari Kantor)</span>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono {{ $currentMode === 'pns_to_dinas' ? 'bg-blue-800 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $countPnsToDinas }} Kriteria</span>
        </a>
        <a href="{{ route('master-kriteria.index', ['mode' => 'dinas_to_pns']) }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $currentMode === 'dinas_to_pns' ? 'bg-blue-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
            <i data-lucide="building-2" class="w-4 h-4"></i>
            <span>Mode 2: Dinas to PNS (Dinas Cari Pegawai)</span>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono {{ $currentMode === 'dinas_to_pns' ? 'bg-blue-800 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $countDinasToPns }} Kriteria</span>
        </a>
    </div>

    {{-- HIDE SEMENTARA CARD PRESET SESUAI PERMINTAAN --}}
    <!-- Info & Presets Banner -->
    <div class="hidden bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="sliders" class="w-4 h-4 text-blue-600"></i> Preset Skenario Kebijakan Kepegawaian
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pilih skenario siap pakai atau atur bobot manual. Sesuai kaidah ilmiah MCDM TOPSIS, total bobot kriteria aktif <strong>wajib tepat 100% (1.00)</strong>.
                </p>
            </div>
            <!-- Preset Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="applyPreset('three')" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-lg transition border border-slate-200">
                    3 Kriteria (Jarak, Hemat, Masa)
                </button>
                <button type="button" onclick="applyPreset('four')" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 text-xs font-bold rounded-lg transition border border-blue-200">
                    4 Kriteria (Standar BKPSDM)
                </button>
                <button type="button" onclick="applyPreset('five')" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg transition border border-emerald-200">
                    5 Kriteria (+ SKP Kinerja)
                </button>
            </div>
        </div>
    </div>

    <!-- Main Weight Form -->
    <form id="weightForm" method="POST" action="{{ route('master-kriteria.update-bobot') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="kategori_mode" value="{{ $currentMode }}">

        {{-- HIDE SEMENTARA CARD TOTAL AKUMULASI BOBOT SESUAI PERMINTAAN --}}
        <!-- Total Meter Bar -->
        <div class="hidden bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex-1 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">Total Akumulasi Bobot Kriteria Aktif:</span>
                    <span id="totalBadge" class="font-extrabold text-sm font-mono {{ abs($totalBobot - 1.00) <= 0.01 ? 'text-emerald-600' : 'text-rose-600 animate-pulse' }}">
                        {{ round($totalBobot * 100) }}% ({{ number_format($totalBobot, 2) }})
                    </span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                    <div id="totalBar" class="h-full {{ abs($totalBobot - 1.00) <= 0.01 ? 'bg-emerald-500' : 'bg-rose-500' }} transition-all duration-300 rounded-full" style="width: {{ min(100, max(0, round($totalBobot * 100))) }}%;"></div>
                </div>
                <div id="statusMsg" class="text-[11px] flex items-center gap-1.5">
                    @if (abs($totalBobot - 1.00) <= 0.01)
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span class="text-emerald-700 font-semibold">Total bobot kriteria aktif tepat 100%. Sesuai kaidah ilmiah TOPSIS.</span>
                    @else
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                        <span class="text-rose-700 font-semibold">Total bobot wajib tepat 100% (1.00). Gunakan tombol Auto-Normalize.</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="autoNormalize()" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-xs" title="Normalisasi matematis otomatis agar total tepat 100%">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5 text-amber-600"></i> Auto-Normalize 100%
                </button>
                <button type="submit" id="submitWeightsBtn" {{ abs($totalBobot - 1.00) > 0.01 ? 'disabled' : '' }} class="px-5 py-2 bg-blue-700 hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i> Simpan Pembobotan
                </button>
            </div>
        </div>

        <!-- Kriteria List Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 w-16">Kode</th>
                            <th class="px-5 py-3.5">Nama Parameter Kriteria</th>
                            <th class="px-5 py-3.5 text-center">Atribut</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 w-64 text-center">Bobot Kepentingan</th>
                            <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($kriteria as $k)
                            <tr class="table-row-standard group {{ $k->is_active ? '' : 'bg-slate-50/50 opacity-60' }}">
                                <td class="px-5 py-3.5 font-bold font-mono text-slate-900 text-xs">
                                    <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 border border-slate-200">{{ $k->kode_kriteria }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900">{{ $k->nama_kriteria }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">Parameter Key: <code>{{ $k->parameter_key ?? '-' }}</code></div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $k->keterangan ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($k->atribut === 'cost')
                                        <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-xs font-bold uppercase tracking-wider">
                                            Cost (Min)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold uppercase tracking-wider">
                                            Benefit (Max)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($k->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($k->is_active)
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" onclick="adjustWeight('{{ $k->id }}', -0.05)" class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-700 transition">
                                                -
                                            </button>
                                            <div class="relative w-24">
                                                <input type="number" id="weight_{{ $k->id }}" name="bobot[{{ $k->id }}]" value="{{ $k->bobot }}" step="0.01" min="0" max="1" class="weight-input w-full text-center text-xs font-mono font-bold bg-white border border-slate-300 rounded-lg py-1 px-2 focus:ring-blue-500 focus:border-blue-500" oninput="updateTotal()">
                                                <span id="percent_{{ $k->id }}" class="absolute right-2 top-1.5 text-[10px] text-slate-400 font-bold pointer-events-none">
                                                    {{ round($k->bobot * 100) }}%
                                                </span>
                                            </div>
                                            <button type="button" onclick="adjustWeight('{{ $k->id }}', 0.05)" class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-center font-bold text-slate-700 transition">
                                                +
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">0% (Dinonaktifkan)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Kriteria Button -->
                                        <button type="button"
                                            onclick="openEditCriteriaModal({{ Js::from($k) }})"
                                            class="p-1.5 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs transition"
                                            title="Edit Kriteria &amp; Bobot">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <!-- Toggle Active Button -->
                                        <button type="button" onclick="document.getElementById('toggleForm_{{ $k->id }}').submit();" class="p-1.5 rounded-lg border text-xs transition {{ $k->is_active ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}" title="{{ $k->is_active ? 'Nonaktifkan Kriteria' : 'Aktifkan Kriteria' }}">
                                            <i data-lucide="{{ $k->is_active ? 'pause' : 'play' }}" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <!-- Delete Button (Only for C5+) -->
                                        @if (!in_array($k->kode_kriteria, ['C1', 'C2', 'C3', 'C4']))
                                            <button type="button" onclick="if(confirm('Hapus kriteria {{ $k->nama_kriteria }}?')) document.getElementById('deleteForm_{{ $k->id }}').submit();" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Kriteria">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Footer Bar Simpan Pembobotan -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-slate-600">
                    Total Bobot Aktif: <span id="footerTotalBadge" class="font-mono font-extrabold {{ abs($totalBobot - 1.00) <= 0.01 ? 'text-emerald-600' : 'text-rose-600' }}">{{ round($totalBobot * 100) }}%</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="autoNormalize()" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="wand-2" class="w-3.5 h-3.5 text-amber-600"></i> Auto-Normalize 100%
                    </button>
                    <button type="submit" id="submitWeightsFooterBtn" {{ abs($totalBobot - 1.00) > 0.01 ? 'disabled' : '' }} class="px-5 py-2 bg-blue-700 hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i> Simpan Pembobotan
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden Individual Forms for Toggle & Delete -->
    @foreach ($kriteria as $k)
        <form id="toggleForm_{{ $k->id }}" method="POST" action="{{ route('master-kriteria.toggle', $k->id) }}" class="hidden">
            @csrf
        </form>
        @if (!in_array($k->kode_kriteria, ['C1', 'C2', 'C3', 'C4']))
            <form id="deleteForm_{{ $k->id }}" method="POST" action="{{ route('master-kriteria.destroy', $k->id) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

</div>

<!-- Modal Tambah Kriteria Baru -->
<div id="addCriteriaModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden animate-modal-pop">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i> Tambah Kriteria Baru
            </h3>
            <button type="button" onclick="closeAddCriteriaModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('master-kriteria.store') }}" class="p-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Mode SPK <span class="text-rose-500">*</span></label>
                <select name="kategori_mode" required class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    <option value="pns_to_dinas" {{ $currentMode === 'pns_to_dinas' ? 'selected' : '' }}>Mode 1: PNS to Dinas (Pegawai Cari Kantor)</option>
                    <option value="dinas_to_pns" {{ $currentMode === 'dinas_to_pns' ? 'selected' : '' }}>Mode 2: Dinas to PNS (Dinas Cari Pegawai)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kriteria <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_kriteria" required placeholder="Contoh: Nilai SKP Kinerja Tahunan" class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Parameter Key <span class="text-rose-500">*</span></label>
                    <input type="text" name="parameter_key" required placeholder="contoh: nilai_skp" class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sifat Atribut <span class="text-rose-500">*</span></label>
                    <select name="atribut" required class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="benefit">Benefit (Makin Besar Makin Baik)</option>
                        <option value="cost">Cost (Makin Kecil Makin Baik)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Bobot Awal (0 - 1.0) <span class="text-rose-500">*</span></label>
                <input type="number" name="bobot" step="0.01" min="0" max="1" value="0.10" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                <p class="text-[10px] text-slate-400 mt-1">Setelah disimpan, gunakan tombol Auto-Normalize 100% untuk membagi rata bobot.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Landasan Kebijakan</label>
                <textarea name="keterangan" rows="2" placeholder="Contoh: Evaluasi prestasi kinerja pegawai dari BKN..." class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeAddCriteriaModal()" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Simpan Kriteria
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kriteria & Bobot -->
<div id="editCriteriaModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden animate-modal-pop">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <i data-lucide="edit-3" class="w-4 h-4 text-blue-600"></i> Edit Kriteria <span id="editKodeLabel" class="font-mono text-blue-700"></span>
            </h3>
            <button type="button" onclick="closeEditCriteriaModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="editCriteriaForm" method="POST" action="" class="p-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kriteria <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_kriteria" id="edit_nama_kriteria" required class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Parameter Key <span class="text-rose-500">*</span></label>
                    <input type="text" name="parameter_key" id="edit_parameter_key" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sifat Atribut <span class="text-rose-500">*</span></label>
                    <select name="atribut" id="edit_atribut" required class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="benefit">Benefit (Makin Besar Makin Baik)</option>
                        <option value="cost">Cost (Makin Kecil Makin Baik)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Bobot Kepentingan (0 - 1.00) <span class="text-rose-500">*</span></label>
                <input type="number" name="bobot" id="edit_bobot" step="0.01" min="0" max="1" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Landasan Kebijakan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="2" class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditCriteriaModal()" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = document.querySelectorAll('.weight-input');
        const badge = document.getElementById('totalBadge');
        const bar = document.getElementById('totalBar');
        const msg = document.getElementById('statusMsg');
        const submitBtn = document.getElementById('submitWeightsBtn');
        const footerBadge = document.getElementById('footerTotalBadge');
        const submitFooterBtn = document.getElementById('submitWeightsFooterBtn');

        window.openAddCriteriaModal = function() {
            document.getElementById('addCriteriaModal').classList.remove('hidden');
        };

        window.closeAddCriteriaModal = function() {
            document.getElementById('addCriteriaModal').classList.add('hidden');
        };

        window.openEditCriteriaModal = function(item) {
            const form = document.getElementById('editCriteriaForm');
            form.action = `{{ url('/master-kriteria') }}/${item.id}`;
            document.getElementById('editKodeLabel').textContent = `[${item.kode_kriteria}]`;
            document.getElementById('edit_nama_kriteria').value = item.nama_kriteria || '';
            document.getElementById('edit_parameter_key').value = item.parameter_key || '';
            document.getElementById('edit_atribut').value = item.atribut || 'benefit';
            document.getElementById('edit_bobot').value = parseFloat(item.bobot || 0).toFixed(2);
            document.getElementById('edit_keterangan').value = item.keterangan || '';
            document.getElementById('editCriteriaModal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        };

        window.closeEditCriteriaModal = function() {
            document.getElementById('editCriteriaModal').classList.add('hidden');
        };

        window.updateTotal = function() {
            let sum = 0;
            inputs.forEach(input => {
                const val = parseFloat(input.value) || 0;
                sum += val;
                
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
            if (footerBadge) footerBadge.innerText = percent + '%';

            if (sum >= 0.99 && sum <= 1.01) {
                badge.className = 'font-extrabold text-sm text-emerald-600 font-mono';
                bar.className = 'h-full bg-emerald-500 transition-all duration-300 rounded-full';
                msg.innerHTML = '<i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i> <span class="text-emerald-700 font-semibold">Total kriteria aktif tepat 100%. Sesuai kaidah ilmiah TOPSIS.</span>';
                submitBtn.disabled = false;
                if (footerBadge) footerBadge.className = 'font-mono font-extrabold text-emerald-600';
                if (submitFooterBtn) submitFooterBtn.disabled = false;
            } else {
                badge.className = 'font-extrabold text-sm text-rose-600 font-mono animate-pulse';
                bar.className = 'h-full bg-rose-500 transition-all duration-300 rounded-full';
                msg.innerHTML = '<i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i> <span class="text-rose-700 font-semibold">Total bobot saat ini ' + percent + '%. Wajib tepat 100% (1.00) agar formulir dapat disimpan.</span>';
                submitBtn.disabled = true;
                if (footerBadge) footerBadge.className = 'font-mono font-extrabold text-rose-600';
                if (submitFooterBtn) submitFooterBtn.disabled = true;
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        };

        window.adjustWeight = function(id, delta) {
            const input = document.getElementById('weight_' + id);
            if (!input) return;
            let current = parseFloat(input.value) || 0;
            let next = Math.max(0, Math.min(1, Math.round((current + delta) * 100) / 100));
            input.value = next.toFixed(2);
            updateTotal();
        };

        window.autoNormalize = function() {
            let sum = 0;
            inputs.forEach(input => {
                sum += parseFloat(input.value) || 0;
            });
            if (sum <= 0) return;

            let runningSum = 0;
            inputs.forEach((input, index) => {
                if (index === inputs.length - 1) {
                    let lastVal = Math.round((1.00 - runningSum) * 100) / 100;
                    input.value = Math.max(0, lastVal).toFixed(2);
                } else {
                    let val = parseFloat(input.value) || 0;
                    let norm = Math.round((val / sum) * 100) / 100;
                    input.value = norm.toFixed(2);
                    runningSum += norm;
                }
            });
            updateTotal();
        };

        window.applyPreset = function(preset) {
            const kriteriaList = @json($kriteria);
            
            if (preset === 'three') {
                inputs.forEach(input => {
                    const id = parseInt(input.id.replace('weight_', ''));
                    const item = kriteriaList.find(k => k.id === id);
                    if (item) {
                        if (item.kode_kriteria === 'C1') input.value = '0.40';
                        else if (item.kode_kriteria === 'C2') input.value = '0.30';
                        else if (item.kode_kriteria === 'C4') input.value = '0.30';
                        else input.value = '0.00';
                    }
                });
            } else if (preset === 'four') {
                inputs.forEach(input => {
                    const id = parseInt(input.id.replace('weight_', ''));
                    const item = kriteriaList.find(k => k.id === id);
                    if (item) {
                        if (item.kode_kriteria === 'C1') input.value = '0.35';
                        else if (item.kode_kriteria === 'C2') input.value = '0.25';
                        else if (item.kode_kriteria === 'C3') input.value = '0.15';
                        else if (item.kode_kriteria === 'C4') input.value = '0.25';
                        else input.value = '0.00';
                    }
                });
            } else if (preset === 'five') {
                inputs.forEach(input => {
                    const id = parseInt(input.id.replace('weight_', ''));
                    const item = kriteriaList.find(k => k.id === id);
                    if (item) {
                        if (item.kode_kriteria === 'C1') input.value = '0.30';
                        else if (item.kode_kriteria === 'C2') input.value = '0.20';
                        else if (item.kode_kriteria === 'C3') input.value = '0.15';
                        else if (item.kode_kriteria === 'C4') input.value = '0.20';
                        else input.value = '0.15';
                    }
                });
            }
            updateTotal();
        };

        updateTotal();
    });
</script>
@endpush

