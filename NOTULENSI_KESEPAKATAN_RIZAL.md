# NOTULENSI KESEPAKATAN & ANALISIS SISTEM SKRIPSI
## Hasil Penyelarasan Metodologi & Fitur SIMUTASI-GIS (Diyanto & Ka Rizal)

> **Dokumen Catatan Diskusi Akademik**  
> **Topik:** Penegasan Batasan Masalah (Non-AHP), Pemilihan Mesin Jarak OSRM, Arsitektur 2-Tahap TOPSIS, dan Pembagian Hak Akses Pengguna.

---

## 1. Ringkasan Poin-Poin Kesepakatan Utama

| No | Poin Diskusi | Keputusan / Kesepakatan | Justifikasi Akademik & Teknis | Status di Sistem |
|:---:|:---|:---|:---|:---:|
| **1** | **Penggunaan Metode AHP** | ❌ **TIDAK MENGGUNAKAN AHP** | Masuk dalam batasan masalah skripsi. Sistem menggunakan **Kebijakan Kepegawaian Resmi BKN** dengan fitur *Dynamic Weighting* yang transparan dan tervalidasi 100% tanpa membebani dengan kuesioner matriks berpasangan AHP. | ✅ **Selesai (Sesuai)** |
| **2** | **Kalkulasi Jarak Tempuh** | 🚗 **WAJIB OSRM (Rute Riil Jalan)** | Menghitung jarak tempuh aktual jaringan jalan raya (*road network*) dan estimasi waktu tempuh (menit), bukan garis lurus (*as the crow flies*) Haversine. | ✅ **Selesai (Sesuai)** |
| **3** | **Model Perangkingan SPK** | 🔄 **2-TAHAP / 2-RANKING** | **Tahap 1:** Rangking PNS pada Dinas Tujuan (Top-Down BKPSDM).<br/>**Tahap 2:** Rangking Dinas pada PNS (Bottom-Up Simulasi ASN). | ⚠️ **Tahap 1 Siap, Tahap 2 Perlu Halaman UI** |
| **4** | **Hak Akses Pegawai ASN** | 👤 **HANYA BISA SIMULASI MANDIRI** | Pegawai ASN dilarang mengubah bobot kriteria, formasi unit, atau eksekusi SK. ASN hanya memiliki akses melihat peta, profil, dan **Simulasi Penempatan Terdekat**. | ⚠️ **RBAC Siap, Modul UI Simulasi Perlu Dibuat** |
| **5** | **Kriteria Seleksi Pegawai** | 📊 **Kriteria Multi-Aspek** | Mempertimbangkan Jarak, Masa Kerja, Pangkat/Golongan, serta Beban Kerja Unit Asal. | ✅ **Selesai (Sesuai)** |

---

## 2. Penjelasan Detail Poin Kesepakatan

### 2.1 Mengapa Kita TIDAK Menggunakan AHP (Analytic Hierarchy Process)?
Dalam percakapan:
> **Ka Rizal:** *"Menurut dika priwe? dan perlu AHP belih ya bang?"*  
> **Diyanto:** *"Oraa pelru zal, kare batasane skripsi mah"*  
> **Ka Rizal:** *"Okeh bang, anggap lah kebijakan kepegawaian"*

**Landasan Teoretis & Pembelaan Sidang Skripsi:**
1. **Batasan Masalah Penelitian:** Fokus utama riset adalah integrasi spasial OSRM dan pemeringkatan TOPSIS multi-kriteria pada pemetaan penempatan pegawai.
2. **Efisiensi Regulasi Pemerintah:** Dalam instansi pemerintah (BKPSDM), bobot prioritas mutasi diturunkan langsung dari regulasi resmi (Peraturan BKN & KemenPAN-RB), bukan dari persepsi subjektif individu pengisi kuesioner AHP.
3. **Fitur Dynamic Weighting:** Sistem menyediakan antarmuka bagi pimpinan BKPSDM untuk menyesuaikan bobot kriteria secara fleksibel dengan validasi otomatis akumulasi tepat 100% ($\sum w_j = 1.00$).

---

### 2.2 Mengapa Menggunakan OSRM, Bukan Garis Lurus (Haversine)?
* **Kelemahan Haversine:** Menghitung jarak garis lurus bola bumi. Pada kenyataannya, ASN tidak bisa terbang melintasi sungai, sawah, atau laut tanpa melewati jalan raya dan jembatan resmi.
* **Keunggulan OSRM (Open Source Routing Machine):**
  - Mengambil data rute riil dari OpenStreetMap (OSM).
  - Menghitung jarak rute jalan raya yang sebenarnya dilalui pegawai (km).
  - Memberikan estimasi durasi waktu tempuh berkendara (menit).
  - Haversine tetap dipertahankan sebagai metode *fallback/backup* jika jaringan OSRM offline.

---

### 2.3 Arsitektur 2-Tahap: Penanganan Kriteria Invarian (Jebakan Akademik)
Kesepakatan penting terkait usulan kriteria Ka Rizal:
* **Tahap 1 (Rangking PNS pada Dinas A):**
  - Alternatif: Para kandidat ASN.
  - Kriteria: Jarak OSRM ($Cost$), Masa Jabatan ($Benefit$), Masa Kerja ($Benefit$), Beban Unit Asal ($Benefit$).
  - 👉 **Valid 100%**, karena setiap PNS memiliki nilai yang berbeda.
* **Tahap 2 (Rangking Dinas pada 1 Orang PNS):**
  - Alternatif: Unit-unit kerja yang membuka formasi.
  - Kriteria TOPSIS: Jarak OSRM ($Cost$), Kesesuaian Formasi Jabatan ($Benefit$), Sisa Kuota Unit ($Benefit$), Efisiensi Penghematan Jarak ($Benefit$).
  - ⚠️ **Catatan Penting:** `Masa Kerja` dan `Masa Jabatan` pegawai **TIDAK dimasukkan ke dalam matriks TOPSIS Tahap 2**, melainkan ditempatkan sebagai **Eligibility Filter (Syarat Awal BKN)**. Sebab, nilai masa kerja 1 orang PNS bernilai sama di semua pilihan kantor (invarian), yang jika dipaksakan masuk TOPSIS akan bernilai nol ($A^+ = A^-$).

---

### 2.4 Pembagian Wewenang Hak Akses (Role-Based Access Control)
Sesuai kalimat kesepakatan: *"cuman di user PNS cuman bisa simulasi aja"*:

```
┌────────────────────────────────────────────────────────┐
│ ROLE: ADMINISTRATOR BKPSDM                             │
│ 1. Kelola Master Unit Kerja & Kuota Formasi ABK        │
│ 2. Kelola Master Bobot & Kriteria TOPSIS               │
│ 3. Jalankan Eksekusi SPK TOPSIS Tahap 1 (Pilih ASN)    │
│ 4. Cetak Berkas Usulan Tim Pertimbangan (Baperjakat)   │
│ 5. Akses Peta Sebaran & Smart Swap Match               │
└────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────┐
│ ROLE: PEGAWAI ASN                                      │
│ 1. Melihat Profil Data Kepegawaian Pribadi             │
│ 2. Memperbarui Titik Koordinat Domisili Rumah          │
│ 3. Menjalankan Modul Simulasi Rekomendasi Mandiri      │
│    (Melihat 3-5 Dinas Terdekat Berformasi & Rute OSRM) │
│ 4. Mengajukan Permohonan Mutasi Mandiri                │
└────────────────────────────────────────────────────────┘
```

---

## 3. Rencana Aksi Pengembangan (Action Plan)

1. ✅ **Database Lokal MariaDB Mac:** Selesai dikonfigurasi di `127.0.0.1:3306` (Bisa dikelola dengan Sequel Ace/TablePlus).
2. ✅ **Autentikasi Mandiri (Native Login):** Selesai diimplementasikan di `/login` dengan dukungan login NIP/Email dan tombol demo akun.
3. ✅ **Dokumentasi UML, Flowchart, dan ERD:** Selesai dibuat di `DESAIN_SISTEM_UML_FLOWCHART_ERD.md`.
4. ✅ **Dokumentasi Metodologi & Tracing Komputasi TOPSIS:** Selesai dibuat di `METODOLOGI_SKRIPSI_TOPSIS.md`.
5. ⏳ **Pekerjaan Berikutnya:** Membuat antarmuka **Modul Simulasi Rekomendasi Mandiri ASN (Tahap 2)** agar akun role `asn` bisa langsung menyimulasikan penempatan kantor terdekat dari rumahnya.

