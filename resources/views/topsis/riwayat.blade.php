@extends('layouts.app')

@section('title', 'Daftar Usulan Rekomendasi Mutasi Baperjakat')

@section('content')
<div class="space-y-6">

    <!-- Kop Surat Resmi (Hanya Tampil Saat Print) -->
    <div class="hidden print:block text-center border-b-4 border-double border-slate-900 pb-3 mb-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800">Pemerintah Kabupaten XYZ</h3>
        <h2 class="text-lg font-black uppercase tracking-tight text-slate-900">Badan Kepegawaian dan Pengembangan Sumber Daya Manusia</h2>
        <h4 class="text-xs font-semibold text-slate-700">Tim Penilai Kinerja / Badan Pertimbangan Jabatan dan Kepangkatan (Baperjakat)</h4>
        <p class="text-[10px] text-slate-500 mt-0.5">Jl. Mayjen Sutoyo No. 1, Lemahmekar, Kec. Indramayu, Kabupaten XYZ, Jawa Barat 45214</p>
        <div class="mt-3 text-xs font-bold uppercase tracking-wide text-slate-900 bg-slate-100 py-1 border border-slate-300">
            Daftar Usulan dan Rekomendasi Mutasi Rotasi Pegawai PNS Berbasis Spasial OSRM & TOPSIS
        </div>
    </div>

    <!-- Header Screen (Disembunyikan saat print) -->
    <div class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    Sidang Baperjakat BKPSDM
                </span>
                <span class="text-xs text-slate-400">&bull; Closed-Loop Execution</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-1">Daftar Usulan & Eksekusi Mutasi Baperjakat</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Pusat pengesahan usulan rotasi PNS: setujui untuk menerbitkan SK dan memindahkan pegawai secara riil, atau tolak dengan berita acara.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 bg-white rounded-lg text-xs font-semibold hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4 text-slate-500"></i> Cetak Berita Acara Sidang
            </button>
            <a href="{{ route('topsis.index') }}" class="px-3.5 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> SPK TOPSIS Baru
            </a>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <div class="font-medium">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
            <div class="font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Table DataTables 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table id="tableRiwayat" class="w-full text-left text-sm text-slate-600 datatable-custom">
                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12 no-sort">No</th>
                        <th class="px-4 py-3.5">Pegawai PNS</th>
                        <th class="px-4 py-3.5">Unit Asal ➔ Tujuan</th>
                        <th class="px-4 py-3.5 text-right">Jarak Lama</th>
                        <th class="px-4 py-3.5 text-right">Jarak Baru</th>
                        <th class="px-4 py-3.5 text-right">Hemat</th>
                        <th class="px-4 py-3.5 text-center">Nilai Vi</th>
                        <th class="px-4 py-3.5 text-center">Status Usulan</th>
                        <th class="px-4 py-3.5 text-center print:hidden no-sort">Aksi Keputusan Sidang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $index => $rek)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 text-center font-bold text-slate-700">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900">{{ $rek->asn->nama_lengkap }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $rek->asn->nip }} &bull; {{ $rek->asn->jabatan }}</div>
                                @if ($rek->catatan_baperjakat)
                                    <div class="text-[11px] text-slate-500 mt-1 italic bg-slate-50 p-1.5 rounded border border-slate-100">
                                        &ldquo;{{ $rek->catatan_baperjakat }}&rdquo;
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-xs text-slate-500">Asal: <span class="font-medium text-slate-700">{{ $rek->unitAsal->nama_unit ?? '-' }}</span></div>
                                <div class="text-xs text-blue-900 font-bold mt-0.5">Tujuan: <span>{{ $rek->unitTujuan->nama_unit ?? '-' }}</span></div>
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-500 text-xs">
                                {{ number_format($rek->jarak_asal_km, 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600 text-xs">
                                {{ number_format($rek->jarak_baru_km, 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-indigo-600 text-xs">
                                +{{ number_format($rek->selisih_hemat_jarak_km, 2) }} km
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-800 text-xs">
                                {{ number_format($rek->nilai_preferensi_v, 4) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($rek->status_usulan === 'disetujui')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs rounded-full font-bold uppercase inline-flex items-center gap-1">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Disetujui (SK)
                                    </span>
                                @elseif ($rek->status_usulan === 'ditolak')
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-xs rounded-full font-bold uppercase inline-flex items-center gap-1">
                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Ditolak
                                    </span>
                                @elseif ($rek->status_usulan === 'diajukan_baperjakat')
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs rounded-full font-bold uppercase inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Menunggu Sidang
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs rounded-full font-semibold uppercase">
                                        {{ $rek->status_usulan }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center print:hidden">
                                @if (in_array($rek->status_usulan, ['draft', 'diajukan_baperjakat']))
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Setujui & Eksekusi Mutasi -->
                                        <form method="POST" action="{{ route('topsis.approve', $rek->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI dan MENERBITKAN SK MUTASI untuk {{ $rek->asn->nama_lengkap }} ke {{ $rek->unitTujuan->nama_unit }}? Unit kerja PNS akan langsung berpindah di sistem.');" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1" title="Setujui Mutasi dan Terbitkan SK">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Setujui
                                            </button>
                                        </form>

                                        <!-- Tombol Tolak Usulan -->
                                        <form method="POST" action="{{ route('topsis.reject', $rek->id) }}" onsubmit="return confirm('Tolak usulan mutasi untuk {{ $rek->asn->nama_lengkap }}?');" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition flex items-center gap-1" title="Tolak Usulan Mutasi">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Tolak
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Selesai disidangkan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-400">Belum ada usulan rekomendasi mutasi yang disimpan ke database Baperjakat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tanda Tangan Berita Acara Sidang Baperjakat (Hanya Tampil Saat Print) -->
    <div class="hidden print:grid grid-cols-2 gap-8 pt-8 text-center text-xs text-slate-800">
        <div>
            <p>Mengetahui,</p>
            <p class="font-bold">Sekretaris Tim Baperjakat</p>
            <p class="text-[10px] text-slate-500">Kepala BKPSDM Kabupaten XYZ</p>
            <div class="h-20"></div>
            <p class="font-bold underline">Drs. H. DADANG RAHMAT, M.Si</p>
            <p class="font-mono text-[10px]">NIP. 196805141994031004</p>
        </div>
        <div>
            <p>Kabupaten XYZ, {{ date('d F Y') }}</p>
            <p class="font-bold">Ketua Tim Baperjakat</p>
            <p class="text-[10px] text-slate-500">Sekretaris Daerah Kabupaten XYZ</p>
            <div class="h-20"></div>
            <p class="font-bold underline">Ir. H. AEP SURAHMAN</p>
            <p class="font-mono text-[10px]">NIP. 196708211993031002</p>
        </div>
    </div>

</div>
@endsection
