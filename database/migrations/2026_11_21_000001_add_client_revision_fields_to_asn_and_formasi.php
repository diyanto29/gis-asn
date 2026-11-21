<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom jenis_jabatan pada formasi_jabatan
        if (!Schema::hasColumn('formasi_jabatan', 'jenis_jabatan')) {
            Schema::table('formasi_jabatan', function (Blueprint $table) {
                $table->enum('jenis_jabatan', ['struktural', 'fungsional', 'pelaksana'])->default('pelaksana')->after('nama_jabatan');
            });
        }

        // 2. Tambah field detail PNS pada asn_master sesuai catatan klien
        Schema::table('asn_master', function (Blueprint $table) {
            if (!Schema::hasColumn('asn_master', 'id_formasi_jabatan')) {
                $table->unsignedBigInteger('id_formasi_jabatan')->nullable()->after('jabatan');
                $table->foreign('id_formasi_jabatan')->references('id')->on('formasi_jabatan')->nullOnDelete();
            }
            if (!Schema::hasColumn('asn_master', 'jenis_jabatan')) {
                $table->enum('jenis_jabatan', ['struktural', 'fungsional', 'pelaksana'])->default('pelaksana')->after('id_formasi_jabatan');
            }
            if (!Schema::hasColumn('asn_master', 'unit_kerja_induk')) {
                $table->string('unit_kerja_induk', 255)->nullable()->after('id_unit_saat_ini');
            }
            if (!Schema::hasColumn('asn_master', 'tgl_jabatan')) {
                $table->date('tgl_jabatan')->nullable()->after('tmt_kerja_unit');
            }
            if (!Schema::hasColumn('asn_master', 'tgl_cpns')) {
                $table->date('tgl_cpns')->nullable()->after('tgl_jabatan');
            }
            if (!Schema::hasColumn('asn_master', 'tgl_pns')) {
                $table->date('tgl_pns')->nullable()->after('tgl_cpns');
            }
            if (!Schema::hasColumn('asn_master', 'tingkat_pendidikan')) {
                $table->string('tingkat_pendidikan', 50)->nullable()->after('tgl_pns');
            }
            if (!Schema::hasColumn('asn_master', 'pendidikan_terakhir')) {
                $table->string('pendidikan_terakhir', 150)->nullable()->after('tingkat_pendidikan');
            }
            if (!Schema::hasColumn('asn_master', 'tmt_golongan')) {
                $table->date('tmt_golongan')->nullable()->after('pangkat_golongan');
            }
        });

        // 3. Tambah kolom kategori_mode pada topsis_kriteria & compound unique index
        if (!Schema::hasColumn('topsis_kriteria', 'kategori_mode')) {
            Schema::table('topsis_kriteria', function (Blueprint $table) {
                $table->enum('kategori_mode', ['pns_to_dinas', 'dinas_to_pns'])->default('pns_to_dinas')->after('kode_kriteria');
            });
        }

        // Pastikan unique index lama di-drop jika ada
        try {
            DB::statement("ALTER TABLE topsis_kriteria DROP INDEX topsis_kriteria_kode_kriteria_unique");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE topsis_kriteria ADD UNIQUE topsis_kriteria_mode_kode_unique (kategori_mode, kode_kriteria)");
        } catch (\Throwable $e) {}

        // 4. Update data kriteria eksisting & inisialisasi kriteria untuk 2 mode
        DB::table('topsis_kriteria')->truncate();

        // Kriteria Mode 1: PNS to Dinas (PNS mencari Dinas Penempatan Terbaik)
        DB::table('topsis_kriteria')->insert([
            [
                'kategori_mode' => 'pns_to_dinas',
                'kode_kriteria' => 'C1',
                'parameter_key' => 'jarak_dinas',
                'nama_kriteria' => 'Jarak Domisili ke Dinas Tujuan (Km)',
                'bobot' => 0.30,
                'atribut' => 'cost',
                'is_active' => true,
                'keterangan' => 'Makin dekat jarak tempuh dari rumah ke dinas sasaran, semakin prioritas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'pns_to_dinas',
                'kode_kriteria' => 'C2',
                'parameter_key' => 'masa_jabatan',
                'nama_kriteria' => 'Masa Jabatan ASN Saat Ini (Tahun)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Dihitung dari Tanggal SK Jabatan. Semakin lama menjabat, semakin matang untuk rotasi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'pns_to_dinas',
                'kode_kriteria' => 'C3',
                'parameter_key' => 'masa_kerja',
                'nama_kriteria' => 'Masa Kerja Pengabdian CPNS (Tahun)',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Dihitung sejak TMT SK CPNS, mencerminkan jam terbang dan senioritas ASN.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'pns_to_dinas',
                'kode_kriteria' => 'C4',
                'parameter_key' => 'kebutuhan_dinas',
                'nama_kriteria' => 'Kebutuhan Staf Dinas Tujuan (ABK - Bezetting)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Makin besar kekurangan pegawai di dinas tujuan, makin diprioritaskan diisi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'pns_to_dinas',
                'kode_kriteria' => 'C5',
                'parameter_key' => 'formasi_jabatan',
                'nama_kriteria' => 'Ketersediaan Formasi Jabatan Linier',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Ketersediaan kuota lowong formasi jabatan yang sesuai (1 = Ada Kuota, 0 = Penuh).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Kriteria Mode 2: Dinas to PNS (Dinas mencari Pegawai Paling Layak Ditarik)
        DB::table('topsis_kriteria')->insert([
            [
                'kategori_mode' => 'dinas_to_pns',
                'kode_kriteria' => 'C1',
                'parameter_key' => 'jarak_pns',
                'nama_kriteria' => 'Jarak Domisili PNS ke Dinas (Km)',
                'bobot' => 0.35,
                'atribut' => 'cost',
                'is_active' => true,
                'keterangan' => 'Mencari pegawai yang domisilinya terdekat dengan dinas pemohon.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'dinas_to_pns',
                'kode_kriteria' => 'C2',
                'parameter_key' => 'masa_jabatan_pns',
                'nama_kriteria' => 'Masa Jabatan PNS di Unit Asal (Tahun)',
                'bobot' => 0.25,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Pegawai yang sudah lama di posisi lamanya lebih siap untuk penyegaran karir.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'dinas_to_pns',
                'kode_kriteria' => 'C3',
                'parameter_key' => 'masa_kerja_pns',
                'nama_kriteria' => 'Masa Kerja PNS sejak CPNS (Tahun)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Pengalaman kerja dan masa pengabdian aparatur.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kategori_mode' => 'dinas_to_pns',
                'kode_kriteria' => 'C4',
                'parameter_key' => 'kondisi_unit_asal',
                'nama_kriteria' => 'Kondisi Pegawai Unit Asal (Beban ABK)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'is_active' => true,
                'keterangan' => 'Memastikan unit asal dalam kondisi surplus/cukup pegawai (3 = Surplus, 2 = Cukup, 1 = Kurang).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asn_master', function (Blueprint $table) {
            $table->dropForeign(['id_formasi_jabatan']);
            $table->dropColumn([
                'id_formasi_jabatan',
                'jenis_jabatan',
                'unit_kerja_induk',
                'tgl_jabatan',
                'tgl_cpns',
                'tgl_pns',
                'tingkat_pendidikan',
                'pendidikan_terakhir',
                'tmt_golongan'
            ]);
        });

        Schema::table('formasi_jabatan', function (Blueprint $table) {
            $table->dropColumn('jenis_jabatan');
        });

        Schema::table('topsis_kriteria', function (Blueprint $table) {
            $table->dropColumn('kategori_mode');
        });
    }
};
