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
