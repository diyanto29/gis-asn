@extends('layouts.app')

@section('title', 'Edit Formasi Jabatan: ' . $formasi->nama_jabatan)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('formasi-jabatan.index', ['tahun' => $formasi->tahun_anggaran]) }}" class="p-2 border border-slate-300 hover:bg-white text-slate-600 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Formasi Jabatan</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $formasi->nama_jabatan }} &bull; Tahun Anggaran {{ $formasi->tahun_anggaran }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-3xl overflow-hidden">
        <form method="POST" action="{{ route('formasi-jabatan.update', $formasi->id) }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Kerja Sasaran <span class="text-rose-500">*</span></label>
                    <select name="id_unit_kerja" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" {{ old('id_unit_kerja', $formasi->id_unit_kerja) == $u->id ? 'selected' : '' }}>
                                {{ $u->nama_unit }} ({{ $u->kategori }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                        <input type="number" name="tahun_anggaran" value="{{ old('tahun_anggaran', $formasi->tahun_anggaran) }}" min="2020" max="2035" required class="w-full text-sm font-mono border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Periode Formasi <span class="text-rose-500">*</span></label>
                        <input type="text" name="periode" value="{{ old('periode', $formasi->periode) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jabatan <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan', $formasi->nama_jabatan) }}" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Jabatan <span class="text-rose-500">*</span></label>
                    <select name="jenis_jabatan" required class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="fungsional" {{ old('jenis_jabatan', $formasi->jenis_jabatan) == 'fungsional' ? 'selected' : '' }}>Jabatan Fungsional</option>
                        <option value="pelaksana" {{ old('jenis_jabatan', $formasi->jenis_jabatan) == 'pelaksana' ? 'selected' : '' }}>Jabatan Pelaksana</option>
                        <option value="struktural" {{ old('jenis_jabatan', $formasi->jenis_jabatan) == 'struktural' ? 'selected' : '' }}>Jabatan Struktural</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenjang Jabatan</label>
                    <select name="jenjang_jabatan" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Jenjang --</option>
                        <option value="Ahli Pertama (III/a - III/b)" {{ old('jenjang_jabatan', $formasi->jenjang_jabatan) == 'Ahli Pertama (III/a - III/b)' ? 'selected' : '' }}>Ahli Pertama (III/a - III/b)</option>
                        <option value="Ahli Muda (III/c - III/d)" {{ old('jenjang_jabatan', $formasi->jenjang_jabatan) == 'Ahli Muda (III/c - III/d)' ? 'selected' : '' }}>Ahli Muda (III/c - III/d)</option>
                        <option value="Ahli Madya (IV/a - IV/c)" {{ old('jenjang_jabatan', $formasi->jenjang_jabatan) == 'Ahli Madya (IV/a - IV/c)' ? 'selected' : '' }}>Ahli Madya (IV/a - IV/c)</option>
                        <option value="Pelaksana / Mahir (II/c - III/a)" {{ old('jenjang_jabatan', $formasi->jenjang_jabatan) == 'Pelaksana / Mahir (II/c - III/a)' ? 'selected' : '' }}>Pelaksana / Mahir (II/c - III/a)</option>
                        <option value="Pelaksana (II/a - II/c)" {{ old('jenjang_jabatan', $formasi->jenjang_jabatan) == 'Pelaksana (II/a - II/c)' ? 'selected' : '' }}>Pelaksana (II/a - II/c)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kualifikasi Pendidikan & Rumpun Keilmuan</label>
                <input type="text" name="kualifikasi_pendidikan" value="{{ old('kualifikasi_pendidikan', $formasi->kualifikasi_pendidikan) }}" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kebutuhan ABK <span class="text-rose-500">*</span></label>
                    <input type="number" name="kebutuhan_abk" id="kebutuhan_abk" value="{{ old('kebutuhan_abk', $formasi->kebutuhan_abk) }}" min="1" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2" oninput="calcLowong()">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Bezetting Riil <span class="text-rose-500">*</span></label>
                    <input type="number" name="bezetting_riil" id="bezetting_riil" value="{{ old('bezetting_riil', $formasi->bezetting_riil) }}" min="0" required class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2" oninput="calcLowong()">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-emerald-800 mb-1">Kuota Lowong <span class="text-rose-500">*</span></label>
                    <input type="number" name="kuota_lowong" id="kuota_lowong" value="{{ old('kuota_lowong', $formasi->kuota_lowong) }}" min="0" required class="w-full text-xs font-mono font-bold bg-emerald-50 text-emerald-900 border border-emerald-300 rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Status Formasi <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-xs border border-slate-300 rounded-lg p-2">
                        <option value="terbuka" {{ old('status', $formasi->status) == 'terbuka' ? 'selected' : '' }}>🟢 Terbuka</option>
                        <option value="terisi" {{ old('status', $formasi->status) == 'terisi' ? 'selected' : '' }}>🔵 Terisi</option>
                        <option value="ditutup" {{ old('status', $formasi->status) == 'ditutup' ? 'selected' : '' }}>⚪ Ditutup</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Landasan SK Formasi</label>
                <textarea name="keterangan" rows="2" class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 focus:ring-blue-500">{{ old('keterangan', $formasi->keterangan) }}</textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('formasi-jabatan.index', ['tahun' => $formasi->tahun_anggaran]) }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">Batal</a>
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function calcLowong() {
        const keb = parseInt(document.getElementById('kebutuhan_abk').value) || 0;
        const bez = parseInt(document.getElementById('bezetting_riil').value) || 0;
        const lowong = Math.max(0, keb - bez);
        document.getElementById('kuota_lowong').value = lowong;
    }
</script>
@endpush

