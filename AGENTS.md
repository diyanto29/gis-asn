# SIMUTASI-GIS Engineering Rules & Guidelines
> Diadopsi dari arsitektur & anti-slop rules `crm_worrie` (clean architecture, anti-AI-slop layout, idempotent migrations, strict RBAC, high-fidelity UI).

Aturan permanen dan standar rekayasa perangkat lunak untuk sistem **SIMUTASI-GIS** (Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN BKPSDM):

---

## 1. Identitas Visual & Standar UI Anti-Slop (MANDATORI)
* **Tema Visual:** **LIGHT THEME ONLY** (Pemerintahan modern berstandar enterprise/GovTech, bersih, terstruktur, dan berwibawa).
* **Palet Warna Resmi BKPSDM / ASN:**
  - **Primary Navy Blue:** `#0f172a` / `#1e3a8a` (Navbar, heading utama, sidebar aktif, tombol primer).
  - **Accent Teal / Emerald:** `#0d9488` / `#059669` (Status zona dekat, formasi tersedia, indikator positif).
  - **Accent Warning Amber:** `#d97706` / `#f59e0b` (Status zona sedang, peringatan kapasitas).
  - **Accent Danger Rose:** `#e11d48` / `#dc2626` (Status zona jauh / beban tempuh tinggi).
  - **Background Canvas:** Putih bersih (`#ffffff`) dengan soft slate surface (`#f8fafc` / `#f1f5f9`) dan border halus (`#e2e8f0`).
* **Anti-AI-Slop Layout:**
  - Dilarang membuat layout "kartu melayang kecil di tengah layar kosong" (ciri khas AI slop).
  - Gunakan komposisi UI proporsional: *Full-width Split View* (Peta GIS di sisi kiri/kanan dan Data Filter/Ranking di sisi lain), *Stat Cards Grid* dengan metrik konkret, dan *Dense Data Table* dengan paginasi serta pencarian.
  - Peta Leaflet/OSM harus memiliki kontainer yang tegas, kontrol zoom terdefinisi, layer switcher, dan legenda zona jarak (Hijau, Kuning, Merah).

---

## 2. Aturan Database & Migrasi SQL (MANDATORI)
* **Setiap perubahan skema database WAJIB melalui migration Laravel resmi:**
  - Lokasi: `database/migrations/YYYY_MM_DD_HHMMSS_<nama_perubahan>.php`.
  - Migration harus reversibel (`up()` dan `down()`).
  - Berikan indeks pada kolom relasional & pencarian: `nip`, `id_unit_kerja`, `kode_desa`, `jarak_km`, `nilai_topsis`.
* **Dokumentasi Perubahan:** Catat setiap penambahan tabel di `MIGRATIONS.md`.

---

## 3. Keamanan Server & Role-Based Access Control (RBAC)
* **Pencegahan URL Injection & Akses Ilegal:**
  - Middleware otentikasi wajib melindungi seluruh rute admin dan ASN.
  - Role dipisahkan secara tegas: `admin` (BKPSDM / Baperjakat) vs `asn` (Pegawai).
  - Pegawai biasa dilarang mengakses modul pembobotan TOPSIS, master formasi, atau eksekusi mutasi. Akses tidak sah langsung di-*abort* (HTTP 403 / Redirect aman).
* **Keamanan Integrasi Single Sign-On (SSO):**
  - Pertukaran token SSO dari sistem eksisting (CI) wajib menggunakan tabel `sso_tokens`.
  - Token bersifat *One-Time Use* (langsung dihapus setelah divalidasi) dan kedaluwarsa dalam 2 menit (`expired_at < now()`).

---

## 4. Standar Logika Bisnis & Algoritma (Spasial & SPK)
* **Kalkulasi Spasial (Haversine Formula):**
  - Perhitungan jarak garis lurus unit kerja vs domisili dihitung secara matematis presisi ($R = 6.371\text{ km}$).
  - Klasifikasi zona wajib seragam:
    - **Dekat:** $< 5\text{ km}$
    - **Sedang:** $5 - 20\text{ km}$
    - **Jauh (Prioritas):** $> 20\text{ km}$
* **Validasi Formasi / ABK:**
  - Unit kerja dengan kuota $\le 0$ dilarang keras direkomendasikan untuk mutasi masuk guna mencegah *overstaffing*.
* **Mesin TOPSIS & Dynamic Weighting:**
  - Bobot kriteria ($C_1 \dots C_n$) disimpan di database dan dapat diatur oleh Admin. Total bobot ternormalisasi wajib bernilai $1.00$ ($100\%$).
  - Perhitungan matriks ternormalisasi, solusi ideal positif/negatif, dan nilai preferensi ($V_i$) harus transparan dan dapat diaudit.

---

## 5. Standar Kualitas Kode (Clean Code)
* Gunakan Controller yang ramping (*thin controller*) dengan memisahkan kalkulasi rumit ke dalam Service Layer (contoh: `HaversineService.php`, `TopsisService.php`).
* Form Request Validation wajib diterapkan untuk semua data masuk (terutama validasi latitude/longitude dan upload Excel/CSV).
* Selalu verifikasi sintaks dan pengujian unit sebelum menyelesaikan implementasi.
