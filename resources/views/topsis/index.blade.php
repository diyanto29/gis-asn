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

        <!-- Column 2: Pengaturan Bobot Kriteria Dinamis (Dynamic Weighting) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-indigo-600"></i>
                        <h3 class="font-bold text-slate-900 text-sm">Pembobotan Kriteria Dinamis</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700">Dynamic Weight</span>
                </div>

                <p class="text-xs text-slate-500">
                    Pimpinan/Admin BKPSDM dapat menyesuaikan porsi bobot kriteria sesuai arah kebijakan rotasi daerah.
                </p>

                <form method="POST" action="{{ route('topsis.update-bobot') }}" class="space-y-3.5">
                    @csrf

                    @foreach ($kriteria as $k)
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800">
                                    [{{ $k->kode_kriteria }}] {{ $k->nama_kriteria }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $k->atribut === 'cost' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ strtoupper($k->atribut) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="number" step="0.01" min="0" max="1" name="bobot[{{ $k->id }}]" value="{{ $k->bobot }}" class="weight-input w-24 text-sm font-bold font-mono border border-slate-300 rounded p-1.5 text-center focus:ring-indigo-500">
                                <span class="text-xs text-slate-500">{{ $k->keterangan }}</span>
                            </div>
                        </div>
                    @endforeach

                    <!-- Live Total Weight Indicator -->
                    <div class="p-3 rounded-lg border flex items-center justify-between text-xs bg-slate-50 border-slate-200">
                        <span class="font-semibold text-slate-700">Total Bobot:</span>
                        <span id="totalWeightBadge" class="font-extrabold text-sm text-emerald-600">
                            {{ number_format($totalBobot * 100, 0) }}% ({{ number_format($totalBobot, 2) }})
                        </span>
                    </div>

                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg transition">
                        Simpan Perubahan Bobot
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputs = document.querySelectorAll('.weight-input');
        const badge = document.getElementById('totalWeightBadge');

        function updateTotal() {
            let sum = 0;
            inputs.forEach(i => sum += parseFloat(i.value) || 0);
            sum = Math.round(sum * 100) / 100;

            badge.innerText = Math.round(sum * 100) + '% (' + sum.toFixed(2) + ')';

            if (sum >= 0.99 && sum <= 1.01) {
                badge.className = 'font-extrabold text-sm text-emerald-600';
            } else {
                badge.className = 'font-extrabold text-sm text-rose-600 animate-pulse';
            }
        }

        inputs.forEach(i => i.addEventListener('input', updateTotal));
    });
</script>
@endpush
