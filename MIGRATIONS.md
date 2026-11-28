# SIMUTASI-GIS Database Migrations Log

Dokumentasi riwayat perubahan skema database untuk proyek **SIMUTASI-GIS** pada MariaDB Server (`192.168.100.65:3306`).

| No | File Migration | Deskripsi Perubahan | Status |
|:---|:---|:---|:---|
| 01 | `0001_01_01_000000_create_users_table.php` | Tabel autentikasi pengguna default Laravel (`users`, `password_reset_tokens`, `sessions`) | Selesai |
| 02 | `0001_01_01_000001_create_cache_table.php` | Tabel caching framework | Selesai |
| 03 | `0001_01_01_000002_create_jobs_table.php` | Tabel queue framework | Selesai |
| 04 | `2026_11_09_095947_create_sso_tokens_table.php` | Tabel tiket Single Sign-On (SSO) one-time token dari CodeIgniter | Selesai |
| 05 | `2026_11_09_095948_create_unit_kerja_table.php` | Master unit kerja, koordinat kantor (lat/lng), dan kuota formasi ABK | Selesai |
| 06 | `2026_11_09_095949_create_asn_master_table.php` | Data induk ASN, titik koordinat domisili spasial, zona jarak, dan profil kerja | Selesai |
| 07 | `2026_11_09_095950_create_topsis_kriteria_table.php` | Master kriteria TOPSIS dengan dynamic weighting (C1..Cn, bobot, cost/benefit) | Selesai |
| 08 | `2026_11_09_095951_create_rekomendasi_mutasi_table.php` | Hasil kalkulasi perangkingan preferensi mutasi ($V_i$), penghematan jarak, status Baperjakat | Selesai |
| 09 | `2026_11_09_110808_add_parameter_key_and_is_active_to_topsis_kriteria_and_asn_master.php` | Penambahan fleksibilitas kriteria dinamis (`parameter_key`, `is_active` toggle 3-5 kriteria) & data pendukung ASN (`nilai_skp`, `tanggungan_keluarga`) | Selesai |
| 10 | `2026_11_15_000001_add_auth_fields_to_users_table.php` | Menambahkan kolom `nip`, `role` (admin/asn), dan `asn_id` untuk sistem autentikasi mandiri independen (Native Login) | Selesai |
| 11 | `2026_11_15_000002_create_master_lokasi_table.php` | Tabel master data wilayah / kecamatan (`master_lokasi`) beserta titik koordinat geospasial pusat untuk sistem independen | Selesai |
| 12 | `2026_11_15_000003_create_formasi_jabatan_table.php` | Tabel master formasi jabatan (`formasi_jabatan`) untuk rekam jejak tracing multi-tahun (2025, 2026) dan pemenuhan ABK dinamis | Selesai |
| 13 | `2026_11_21_000001_add_client_revision_fields_to_asn_and_formasi.php` | Penambahan atribut revisi klien: `id_formasi_jabatan`, `jenis_jabatan` (struktural/fungsional/pelaksana), `unit_kerja_induk`, riwayat tanggal (`tgl_jabatan`, `tgl_cpns`, `tgl_pns`, `tmt_golongan`), jenjang & pendidikan terakhir pada `asn_master`; `jenis_jabatan` pada `formasi_jabatan`; serta kolom `kategori_mode` (`pns_to_dinas` & `dinas_to_pns`) dan indeks komposit unik pada `topsis_kriteria` | Selesai |
| 14 | `2026_11_21_000002_create_master_jabatan_table.php` | Pembuatan tabel master referensi jabatan standar ASN (`master_jabatan`), relasi foreign key `id_master_jabatan` pada `asn_master`, dan seeding otomatis 1.024 data nomenklatur baku jabatan (Pelaksana, Fungsional, Struktural) | Selesai |
| 15 | `2026_11_26_000001_enhance_master_jabatan_and_formasi_relations.php` | Penambahan atribut standar SIASN BKN (`siasn_id`, `kode_cepat`, `kelas_jabatan`, `bup_usia`) pada `master_jabatan` dan penambahan foreign key `id_master_jabatan` pada `formasi_jabatan` | Selesai |
| 16 | `2026_11_27_000001_update_master_jabatan_unique_nama_jenjang.php` | Mengubah unique constraint `master_jabatan` dari `nama_jabatan` menjadi komposit `('nama_jabatan', 'jenjang_jabatan')` sehingga nama jabatan boleh sama selama jenjang berbeda | Selesai |
| 17 | `2026_11_27_000002_sync_topsis_kriteria_five_criteria.php` | Menyelaraskan 5 kriteria standar TOPSIS (C1 Jarak, C2 Masa Jabatan, C3 Masa Kerja, C4 Formasi Jabatan, C5 Kebutuhan Pegawai Unit Asal) untuk Mode 1 & Mode 2 | Selesai |
| 18 | `2026_11_27_000003_align_mode1_five_and_mode2_four_topsis_criteria.php` | Menyelaraskan Mode 1 menjadi 5 kriteria (C1–C5 dengan subkriteria C2 & C3) dan Mode 2 menjadi 4 kriteria (C1 Jarak, C2 Masa Jabatan, C3 Masa Kerja, C4 Kebutuhan Pegawai Unit Asal) | Selesai |
| 19 | `2026_11_27_000004_create_pengajuan_ubah_lokasi_table.php` | Tabel pengajuan perubahan pin titik lokasi mandiri dengan upload KTP, approval/verifikasi Admin BKPSDM, dan riwayat pergantian pin | Selesai |
| 20 | `2026_11_28_000001_restore_real_wilayah_names_in_addresses.php` | Mengembalikan nama wilayah administratif riil (`Kabupaten Indramayu`) pada `asn_master.kabupaten_kota` dan `unit_kerja.alamat_kantor` agar cocok dengan API Wilayah.id | Selesai |
