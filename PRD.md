# Product Requirement Document (PRD)
## SIMUTASI-GIS: Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN

---

## 1. Identitas Dokumen
* **Nama Produk:** Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN (SIMUTASI-GIS)
* **Instansi / Studi Kasus:** Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM)
* **Status Dokumen:** Final Draft (MVP / Skripsi Scope)
* **Versi:** 1.0.0
* **Teknologi Utama:** Laravel 11/12, PHP 8.5+, SQLite/MySQL, Leaflet.js, OpenStreetMap, Algoritma Haversine & TOPSIS

---

## 2. Latar Belakang & Problem Statement
* **Latar Belakang:** Banyak ASN ditempatkan pada unit kerja yang berjarak puluhan kilometer dari domisili aslinya, yang berimplikasi pada penurunan produktivitas, tingginya tingkat keterlambatan absensi, dan beban psikologis/finansial perjalanan harian. Pemerintah daerah membutuhkan intervensi kebijakan mutasi berbasis pemerataan zonasi domisili.
* **Problem Statement:** 
  1. Data alamat domisili di database kepegawaian eksisting masih berupa teks naratif tanpa georeferensi titik koordinat.
  2. Belum ada visualisasi spasial pemetaan kesenjangan jarak antara unit kerja dan domisili ASN.
  3. Proses penentuan mutasi mendekati domisili rentan subjektif dan berisiko melanggar batas Analisis Beban Kerja (ABK) jika unit tujuan kelebihan pegawai (*overstaffed*).

---

## 3. Tujuan Produk & Target Keberhasilan (Goals & KPIs)
* **Tujuan Produk:** Menyediakan platform pemetaan geospasial domisili ASN terintegrasi dan menghasilkan rekomendasi mutasi objektif berbasis multi-kriteria (jarak & formasi jabatan kosong).
* **Indikator Keberhasilan (KPIs):**
  * 100% data ASN yang memperbarui profil memiliki titik lintang/bujur valid (presisi level atap rumah/kelurahan).
  * Kecepatan kalkulasi matriks TOPSIS dan Haversine di bawah 3 detik untuk skala $\ge 1.000$ ASN.
  * Transparansi rekomendasi mutasi terverifikasi oleh nilai preferensi ideal positif dan negatif TOPSIS.

---

## 4. Persona Pengguna
1. **Pegawai (ASN):** Melakukan login via SSO SIMPEG, memilih data alamat bertingkat, menentukan titik pinpoint rumah di peta interaktif, dan melihat jarak tempuh ke kantor.
2. **Administrator Kepegawaian (BKPSDM):** Mengelola master formasi/ABK, mengatur bobot kriteria dinamis, mengawasi dashboard peta persebaran spasial, serta mengeksekusi kalkulasi rekomendasi mutasi.
3. **Pimpinan / Tim Baperjakat:** Meninjau daftar peringkat rekomendasi pegawai untuk pengambilan keputusan rotasi/mutasi resmi.

---

## 5. Alur Pengguna (User Journey)

### A. Alur Pembaruan Titik Domisili (Sisi ASN)
1. ASN login di aplikasi SIMPEG eksisting (CodeIgniter) $\rightarrow$ klik menu **"Pemetaan Domisili"**.
2. Sistem meng-generate One-Time Token dan me-redirect ke aplikasi Laravel.
3. ASN memilih Provinsi $\rightarrow$ Kab/Kota $\rightarrow$ Kecamatan $\rightarrow$ Desa/Kelurahan.
4. Peta interaktif otomatis bergeser (*auto-center*) ke area desa terpilih.
5. ASN menggeser pin lokasi tepat di atas atap rumah (atau klik *"Gunakan Lokasi Saya"*).
6. Sistem menyimpan `latitude` dan `longitude` domisili, lalu menampilkan estimasi jarak ($km$) ke kantor kerja saat ini.

### B. Alur Eksekusi Rekomendasi Mutasi (Sisi Admin BKPSDM)
1. Admin mengunggah/memperbarui data kuota formasi unit kerja (ABK/Bezetting).
2. Admin membuka menu **Kriteria & Bobot** untuk mengatur persentase bobot TOPSIS (Total bobot = 100%).
3. Admin memilih unit kerja tujuan atau memilih opsi *"Rekomendasi Mutasi Massal Berbasis Domisili"*.
4. Sistem memfilter kandidat ASN yang domisilinya lebih dekat ke unit yang memiliki formasi kosong.
5. Sistem mengeksekusi algoritma TOPSIS $\rightarrow$ menampilkan tabel peringkat nilai preferensi ($V_i$).
6. Admin mengunduh laporan rekomendasi resmi format PDF/Excel untuk sidang Baperjakat.

---

## 6. Kebutuhan Fungsional (Functional Requirements)

### Modul 1: Autentikasi & Integrasi SSO
* **FR-01:** Sistem wajib mendukung integrasi login via Single Sign-On menggunakan One-Time Ephemeral Token dari aplikasi CodeIgniter.
* **FR-02:** Token memiliki masa kedaluwarsa maksimal 2 menit dan langsung hangus (*one-time use*) setelah diverifikasi.
* **FR-03:** Sesi pengguna di Laravel dipetakan berdasarkan NIP pegawai.

### Modul 2: Pembaruan Geolokasi Domisili (Self-Service)
* **FR-04:** Menyediakan cascading dropdown 4 tingkat (Provinsi, Kabupaten/Kota, Kecamatan, Kelurahan/Desa) berbasis master data Kemendagri.
* **FR-05:** Menyediakan peta interaktif Leaflet.js dengan marker yang dapat digeser (*draggable pin*).
* **FR-06:** Menyediakan tombol aktivasi sensor GPS perangkat menggunakan HTML5 Geolocation API.
* **FR-07:** Validasi sistem wajib memastikan nilai koordinat tidak kosong (`lat/lng != null`) dan berada dalam cakupan wilayah NKRI.

### Modul 3: Kalkulasi Jarak & Dashboard GIS
* **FR-08:** Sistem menghitung jarak Euclidean bola bumi menggunakan **Haversine Formula**:
  $$d = 2R \cdot \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)}\right)$$
  *(dimana $R = 6.371\text{ km}$)*.
* **FR-09:** Menampilkan visualisasi peta tematik (Peta GIS) dengan marker warna:
  * Hijau: Jarak dekat ($< 5\text{ km}$)
  * Kuning: Jarak sedang ($5 - 20\text{ km}$)
  * Merah: Jarak jauh ($> 20\text{ km}$)
* **FR-10:** Garis penghubung spasial (*polyline*) antara titik kantor dan titik domisili pegawai pada peta.

### Modul 4: Manajemen Formasi & Analisis Beban Kerja (ABK)
* **FR-11:** Fitur import file Excel/CSV data formasi berisi: Kode Unit, Nama Unit Kerja, Jabatan, Kebutuhan (ABK), Jumlah Riil Pegawai (Bezetting), dan Kuota Lowong.
* **FR-12:** Rule Engine: Unit kerja dengan kuota formasi $\le 0$ otomatis diblokir dari daftar usulan mutasi masuk (mencegah kelebihan beban).

### Modul 5: Sistem Pendukung Keputusan (Engine TOPSIS)
* **FR-13:** Modul konfigurasi pembobotan dinamis (*Dynamic Weighting*) dengan kriteria:
  * $C_1$: Jarak Domisili ke Unit Baru (Cost / makin kecil makin baik)
  * $C_2$: Masa Kerja di Unit Lama (Benefit / makin lama makin diprioritaskan)
  * $C_3$: Pangkat / Golongan Ruang (Benefit)
  * $C_4$: Tanggungan Keluarga / Riwayat Sakit (Benefit - jika data tersedia)
* **FR-14:** Perhitungan matematis TOPSIS mencakup:
  1. Pembentukan Matriks Keputusan Ternormalisasi ($R$).
  2. Matriks Keputusan Ternormalisasi Terbobot ($Y$).
  3. Penentuan Solusi Ideal Positif ($A^+$) dan Solusi Ideal Negatif ($A^-$).
  4. Perhitungan Jarak Separasi Euclidean ($D_i^+$ dan $D_i^-$).
  5. Perhitungan Nilai Preferensi Akhir:
     $$V_i = \frac{D_i^-}{D_i^+ + D_i^-}$$
* **FR-15:** Menampilkan hasil pemeringkatan ASN dari nilai $V_i$ tertinggi (1.000) hingga terendah.

### Modul 6: Pelaporan & Ekspor Data
* **FR-16:** Export data hasil pemeringkatan ke dokumen cetak PDF yang dilengkapi rekap jarak asal, jarak baru, selisih penghematan jarak ($\Delta\text{ Jarak}$), dan nama unit tujuan.
* **FR-17:** Export data rekapitulasi ke format Spreadsheet (.xlsx).

---

## 7. Kebutuhan Non-Fungsional (Non-Functional Requirements)
* **Kinerja (Performance):** Query spasial dan perhitungan TOPSIS untuk 1.000 data pegawai selesai dieksekusi dalam waktu $< 3$ detik.
* **Keamanan (Security):** Token SSO dienkripsi, koordinat divalidasi tipe float/double untuk menghindari SQL Injection, dan seluruh rute data ASN dilindungi middleware otorisasi.
* **Kemudahan Penggunaan (Usability):** Antarmuka peta responsif diakses melalui peramban mobile (smartphone ASN) dan desktop (Admin).
* **Kompatibilitas:** Peta berjalan lancar tanpa API Key berbayar dengan memanfaatkan tile layer OpenStreetMap gratis.

---

## 8. Skema Basis Data Inti (Data Model Reference)

```
[sso_tokens]
- token (PK, varchar 64)
- nip (varchar 30)
- role (varchar 20)
- expired_at (datetime)

[asn_master]
- nip (PK, varchar 30)
- nama (varchar 150)
- jabatan (varchar 100)
- golongan (varchar 10)
- tmt_kerja_unit (date)
- id_unit_saat_ini (FK)
- domisili_lat (decimal 10,8)
- domisili_lng (decimal 11,8)
- kode_desa (varchar 15)
- alamat_detail (text)

[unit_kerja]
- id (PK, int)
- nama_unit (varchar 150)
- kategori (Dinas, Kecamatan, Puskesmas, Sekolah)
- kantor_lat (decimal 10,8)
- kantor_lng (decimal 11,8)
- kuota_formasi_kosong (int)

[topsis_kriteria]
- id (PK, int)
- kode_kriteria (C1, C2, C3, ...)
- nama_kriteria (varchar 100)
- bobot (decimal 5,2)
- atribut (cost / benefit)
```

---

## 9. Rencana Rilis (Roadmap)
* **Fase 1 (MVP / Target Skripsi):**
  * SSO CI-Laravel & Setup Database.
  * Form Cascading Wilayah + Map Picker Leaflet.
  * Formula Haversine & Visualisasi Peta Sebaran.
  * CRUD Kuota Formasi Unit Kerja via Excel.
  * Engine TOPSIS Berbasis Bobot Dinamis & Ekspor PDF.
* **Fase 2 (Pengembangan Lanjutan Pasca-Skripsi):**
  * Algoritma *Swap Match* (Tukar Guling ASN antar-instansi tanpa merubah jumlah formasi).
  * Integrasi rute jalan nyata via Open Source Routing Machine (OSRM).
  * Notifikasi WhatsApp Gateway ke ASN saat usulan mutasi diproses.

