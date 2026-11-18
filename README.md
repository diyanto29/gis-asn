# SIMUTASI-GIS
### Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN BKPSDM Berbasis Geospasial
> **Studi Kasus:** Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Kabupaten Indramayu  
> **Standar Rekayasa:** Clean Architecture, Anti-AI-Slop Light Theme, Pure OSRM Road Network, 2-Tahap TOPSIS SPK, dan AI Context-Aware Smart Reason Generator.

---

## 📌 Ringkasan Proyek

**SIMUTASI-GIS** adalah sistem informasi kepegawaian modern berstandar enterprise (*GovTech*) yang dirancang untuk memecahkan permasalahan penempatan dan rotasi Aparatur Sipil Negara (ASN). Sistem ini menggabungkan analisis spasial rute fisik jalan raya nyata (**Open Source Routing Machine - OSRM**) dengan metode pengambilan keputusan multi-kriteria (**TOPSIS**) serta kecerdasan buatan kontekstual (**AI Context-Aware Reason Generator**).

### Masalah yang Diselesaikan:
1. **Kelelahan Fisik & Beban Finansial ASN:** Mengatasi fenomena komuter ekstrem (jarak tempuh harian $\ge 80\text{ km/hari}$ PP) dengan rekomendasi unit kerja yang dekat dengan domisili.
2. **Keterbatasan Data Teks SIMPEG:** Mentransformasikan alamat teks naratif menjadi koordinat spasial presisi (*geocoded latitude/longitude*).
3. **Pencegahan Overstaffing (ABK Guard):** Mencegah mutasi ke kantor yang sudah penuh dengan validasi ketat status formasi lowong ($\text{kuota} > 0$).
4. **Solusi Unit Berkuota Nol (Smart Swap Match):** Memberikan rekomendasi tukar guling simetris (*Pareto optimal*) antar-dua pegawai lintas instansi dengan dampak formasi nol (*Zero-ABK Impact*).
5. **Kemudahan Pengajuan Mutasi (AI Reason Generator):** Membantu ASN menyusun surat permohonan mutasi berstandar birokrasi resmi secara otomatis berbasis data spasial dan formasi riil.

---

## 🌟 Fitur Utama Sistem

### 1. 🗺️ Peta Spasial Geospasial Interaktif (GIS Dashboard)
* **Pure OSRM Road Network Routing:** Mengukur rute berkendara nyata di atas jalan aspal pantura/provinsi via OSRM Driving Engine, menghasilkan jarak riil ($\text{km}$) dan estimasi durasi tempuh ($\text{menit}$).
* **Zonasi Jarak Standar BKPSDM:**
  * 🟢 **Zona Hijau (Dekat):** Jarak $< 5.0\text{ km}$ (Efisiensi optimal).
  * 🟡 **Zona Kuning (Sedang):** Jarak $5.0 - 20.0\text{ km}$ (Beban perjalanan wajar).
  * 🔴 **Zona Merah (Jauh / Prioritas Mutasi):** Jarak $> 20.0\text{ km}$ (Beban perjalanan tinggi).
* **Animasi Fisika Marker Ripple:** Indikator gelombang melingkar (*pulsating ripple*) pada setiap marker aktif.
* **Heatmap Kepadatan Domisili:** Visualisasi lapisan panas (*Leaflet.heat*) untuk menganalisis konsentrasi tempat tinggal ASN sekabupaten.
* **Integrasi Google Maps:** Tautan navigasi langsung ke Google Maps rute jalan raya.

### 2. 🧮 Sistem Pendukung Keputusan (SPK) TOPSIS 2-Tahap
* **Bukan AHP (Sesuai Batasan Riset Skripsi):** Menggunakan pembobotan dinamis (*Dynamic Weighting*) berbasis regulasi BKN & PermenPAN-RB dengan verifikasi total bobot tepat $1.00$ ($100\%$) dan fitur matematis *Auto-Normalize*.
* **Arsitektur 2-Tahap:**
  * **Tahap 1 (Top-Down Baperjakat/Admin):** Memilih unit kerja tujuan yang berformasi kosong, lalu meranking ASN mana yang paling prioritas dan berhak dipindahkan ke kantor tersebut.
  * **Tahap 2 (Bottom-Up Mandiri ASN):** Pegawai ASN melakukan simulasi mandiri untuk meranking kantor-kantor dinas mana yang paling optimal untuk dirinya berdasarkan jarak OSRM, sisa kuota formasi, dan penghematan kilometer harian.
* **Audit Trail Matematis:** Transparansi lengkap matriks normalisasi ($R$), matriks terbobot ($Y$), solusi ideal positif ($A^+$) dan negatif ($A^-$), jarak separasi ($D^+, D^-$), serta nilai preferensi akhir ($V_i$).

### 3. 🤖 AI Context-Aware Smart Reason Generator
* Asisten cerdas di dalam modal permohonan mutasi mandiri ASN.
* Menganalisis parameter riil: *Nama ASN, NIP, Jabatan, Kantor Saat Ini, Jarak Tempuh Lama, Kantor Tujuan, Jarak Tempuh Baru, Selisih Efisiensi Jarak (km), dan Sisa Kuota Formasi ABK*.
* Menyediakan 3 gaya narasi resmi birokrasi pemerintahan:
  1. **Efisiensi Jarak & Penghematan Mobilitas:** Menyoroti efisiensi waktu, fisik, dan energi untuk peningkatan produktivitas.
  2. **Kedekatan Domisili Keluarga & Stabilitas Kerja:** Menyoroti stabilitas sosial dan kedekatan domisili tanpa melanggar kedisiplinan kerja.
  3. **Optimalisasi Formasi ABK & Kinerja Organisasi:** Menyoroti pemenuhan kebutuhan Analisis Beban Kerja (ABK) instansi penerima.

### 4. 🔄 Smart Swap Match (Tukar Guling Simetris Zero-ABK)
* Solusi cerdas jika unit kerja yang diinginkan memiliki kuota penuh ($\text{kuota} = 0$).
* Algoritma *Bipartite Mutual Matching* mencari pasangan pegawai simetris lintas dinas yang mana keduanya sama-sama mengalami penghematan jarak ($\text{Save}_A > 0$ dan $\text{Save}_B > 0$).
* Pertukaran kursi bersifat *Pareto Optimal* tanpa mengganggu formasi ABK daerah ($\Delta\text{ABK} = 0$).

### 5. 🌱 Analitik Green GovTech (Ekonomi & Emisi Karbon)
* Menghitung dampak keberlanjutan mutasi:
  * Estimasi penghematan biaya belanja BBM pribadi ASN ($\text{Rp/tahun}$).
  * Estimasi reduksi emisi gas buang karbon ($CO_2\text{ kg/tahun}$).

### 6. 🔐 Autentikasi Mandiri & Role-Based Access Control (RBAC)
* Sistem beroperasi secara independen (*standalone*) tanpa ketergantungan wajib pada sistem eksternal.
* Login fleksibel: Mendukung input **18 Digit NIP** atau **Alamat Email**, dengan verifikasi kata sandi aman (`bcrypt`).
* Pemisahan hak akses tegas:
  * **Role `admin` (BKPSDM / Tim Baperjakat):** Akses penuh dasbor GIS makro, kelola data master unit kerja, master wilayah, data pegawai ASN, ubah bobot TOPSIS dinamis, eksekusi pemeringkatan Tahap 1, dan cetak naskah sidang.
  * **Role `asn` (Pegawai):** Dasbor mandiri, pembaruan titik domisili GPS, simulasi rekomendasi Tahap 2, dan pengajuan mutasi mandiri dengan AI Smart Reason.
* Tetap mempertahankan kompatibilitas Single Sign-On (SSO) one-time token melalui tabel `sso_tokens`.

### 7. 🗄️ Pengelolaan Master Data Mandiri (Independent System CRUD)
* **Master Pegawai ASN (CRUD Lengkap):** Pendaftaran pegawai baru dengan auto-provisioning akun login `users` mandiri, pembaruan profil kepegawaian (jabatan, pangkat/golongan, unit kerja, masa kerja, TMT), dan geocoding tempat tinggal.
* **Master Unit Kerja & Formasi ABK (CRUD Lengkap):** Pendaftaran kantor dinas baru, pemetaan titik kantor absensi di peta Leaflet, klasifikasi kategori (Dinas, Badan, Kecamatan, UPTD, Sekolah), kebutuhan ABK, bezetting riil, dan kuota formasi kosong.
* **Master Formasi Jabatan & Tracing ABK Multi-Tahun:** Modul khusus pengelolaan alokasi jabatan (kualifikasi rumpun ilmu, jenjang, kuota, status) dengan audit tracing tahunan (misal: Tahun 2025 historis terisi vs Tahun 2026 terbuka aktif) dan auto-sync ke matriks kuota TOPSIS.
* **Master Wilayah & Referensi Spasial (31 Kecamatan Indramayu):** Basis data geospasial 31 kecamatan di Kabupaten Indramayu mencakup kode Kemendagri, koordinat titik pusat spasial (lat/lng), jumlah desa, serta fitur interaktif *fly-to* otomatis saat memilih wilayah pada form domisili.

---

## 🛠️ Arsitektur & Teknologi

* **Backend Framework:** Laravel 12 (PHP 8.5+)
* **Basis Data:** MariaDB 13.0 (Home Server / Local Mac) / SQLite (Fallback)
* **Frontend:** Blade Templating, Tailwind CSS, DataTables 2 (Tailwind Custom GovTech UI), Lucide Icons, Vanilla JS (Anti-Slop Clean UI)
* **Peta Geospasial:** Leaflet.js v1.9.4, OpenStreetMap Tile Server, Leaflet.heat
* **Routing Engine:** Open Source Routing Machine (OSRM) HTTP API
* **Desain UI:** Light Theme Enterprise (Navy `#0f172a`, Emerald `#059669`, Amber `#d97706`, Rose `#dc2626`)

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### 1. Kebutuhan Lingkungan (Prerequisites)
* PHP $\ge 8.2$ (Disarankan PHP 8.3/8.5) dengan ekstensi `pdo_mysql`, `curl`, `mbstring`, `bcmath`.
* Composer $\ge 2.0$.
* MariaDB $\ge 10.6$ atau MySQL $\ge 8.0$.
* Node.js & NPM (opsional jika mengompilasi aset Vite).

### 2. Kloning & Pengaturan Dependensi
```bash
# Kloning repositori
git clone https://github.com/username/simutasi-asn.git
cd simutasi-asn

# Instal dependensi PHP
composer install

# Salin konfigurasi environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Konfigurasi Basis Data (`.env`)
Sesuaikan konfigurasi database MariaDB pada berkas `.env`:
```ini
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simutasi_asn
DB_USERNAME=root
DB_PASSWORD=passwordkamu
```

### 4. Migrasi & Seeding Data Awal
Jalankan migrasi skema database dan seeder data dummy:
```bash
# Eksekusi migrasi tabel
php artisan migrate:fresh

# Isi data awal pegawai, unit kerja, kriteria TOPSIS, dan user login
php artisan db:seed
```

### 5. Menjalankan Server Aplikasi
```bash
php artisan serve --port=8000
```
Buka peramban (*browser*) Anda pada: **`http://127.0.0.1:8000`**

---

## 🔑 Akun Uji Coba Demo (Default Credentials)

Sistem menyediakan tombol **"Demo Login Instan"** di halaman login untuk mempermudah evaluasi:

| Peran (*Role*) | Identitas (NIP / Email) | Password | Deskripsi Akses |
| :--- | :--- | :--- | :--- |
| **Admin BKPSDM** | `admin@bkpsdm.go.id` *(atau NIP: `198501012010011001`)* | `admin123` | Akses penuh manajemen kriteria, master formasi, eksekusi mutasi Baperjakat |
| **Pegawai ASN** | `198507122010011005` *(Ahmad Fauzi, S.IP, M.Si)* | `password123` | Akses mandiri pemetaan domisili, simulasi rekomendasi unit, pengajuan mutasi AI |

---

## 📂 Struktur Dokumen Akademik & Referensi Skripsi

Proyek ini dilengkapi dengan dokumentasi komprehensif yang siap dikutip untuk penulisan naskah Skripsi:

1. 📄 **[TESTING_DAN_TEST_CASES_NEGATIF.md](file:///Users/diyanto/simutasi-asn/TESTING_DAN_TEST_CASES_NEGATIF.md)**:
   * Dokumentasi Bab 4 Skripsi: Matriks pengujian Black-Box (Happy Path & 8 Kasus Negatif Ketat), pembuktian RBAC, Zero-ABK Guard, dan 75 assertions lolos 100%.
2. 📄 **[DOKUMENTASI_ABK_FORMASI_DAN_SINKRONISASI.md](file:///Users/diyanto/simutasi-asn/DOKUMENTASI_ABK_FORMASI_DAN_SINKRONISASI.md)**:
   * Glosarium resmi istilah BKN/MenPAN-RB (Bezetting Riil, Kebutuhan ABK, Kuota Lowong), arsitektur sinkronisasi makro-mikro, dan 6 studi kasus (*case scenarios*) pencegahan overstaffing.
3. 📄 **[METODOLOGI_SKRIPSI_TOPSIS.md](file:///Users/diyanto/simutasi-asn/METODOLOGI_SKRIPSI_TOPSIS.md)**:
   * Dokumentasi lengkap Bab 3 & Bab 4: Notasi matematis KaTeX, perbandingan arsitektur 2-Tahap, bukti penanganan kriteria invarian, dan lembar contekan ujian sidang (*Defense Cheat Sheet*).
4. 📄 **[NOTULENSI_KESEPAKATAN_RIZAL.md](file:///Users/diyanto/simutasi-asn/NOTULENSI_KESEPAKATAN_RIZAL.md)**:
   * Catatan resmi kesepakatan pembimbingan teknis: Non-AHP, validasi total bobot 100%, pure OSRM, dan sistem mandiri.
5. 📄 **[PRD.md](file:///Users/diyanto/simutasi-asn/PRD.md)**:
   * *Product Requirement Document* komprehensif memuat 10 modul fungsional, analisis hukum BKN 10 tahun, dan skema MariaDB.
6. 📄 **[DESAIN_SISTEM_UML_FLOWCHART_ERD.md](file:///Users/diyanto/simutasi-asn/DESAIN_SISTEM_UML_FLOWCHART_ERD.md)**:
   * Diagram UML lengkap (Use Case, Activity, Sequence), Flowchart alur komputasi, dan Entity-Relationship Diagram (ERD).
7. 📄 **[MIGRATIONS.md](file:///Users/diyanto/simutasi-asn/MIGRATIONS.md)**:
   * Catatan audit seluruh berkas migrasi database Laravel.

---

## 🏛️ Hak Cipta & Lisensi
Dikembangkan sebagai karya akademik dan inovasi GovTech untuk **Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Kabupaten Indramayu**.  
Lisensi perangkat lunak di bawah naungan akademik institusi.
