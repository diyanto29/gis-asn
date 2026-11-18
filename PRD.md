# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SISTEM PENDUKUNG KEPUTUSAN REKOMENDASI MUTASI PEGAWAI BERBASIS GEOSPASIAL (SIMUTASI-GIS)
> **Dokumen Spesifikasi Teknis & Acuan Naskah Skripsi**  
> **Studi Kasus:** Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Kabupaten Indramayu

---

## 1. IDENTITAS & INFORMASI DOKUMEN
* **Judul Penelitian / Skripsi:**  
  *“Sistem Pendukung Keputusan Rekomendasi Mutasi Pegawai Berbasis Geospasial Menggunakan Kombinasi Formula Haversine dan Metode TOPSIS dengan Fitur Smart Swap Match dan Analisis Rute OSRM”*
* **Nama Produk / Sistem:** SIMUTASI-GIS (*Sistem Informasi Geospasial Mutasi Pegawai*)
* **Versi Dokumen:** 2.0.0 (Comprehensive Academic & Production Release)
* **Tanggal Pembaruan:** November 2026
* **Basis Data:** MariaDB 13.0 (Home Server Environment)
* **Teknologi Utama:** Laravel 11/12, PHP 8.5+, Leaflet.js, OpenStreetMap, OSRM Routing Engine, Leaflet.heat, Formula Haversine, Algoritma TOPSIS, Bipartite Mutual Matching.

---

## 2. LATAR BELAKANG & PROBLEM STATEMENT

### 2.1 Latar Belakang Masalah
Dalam manajemen Aparatur Sipil Negara (ASN), penempatan pegawai sering kali mengabaikan aspek jarak geografis antara tempat tinggal (domisili) dan unit kerja (kantor dinas, puskesmas, kecamatan, atau sekolah). Fenomena ini menimbulkan sejumlah permasalahan:
1. **Beban Fisik & Finansial Pegawai:** Banyak ASN menempuh jarak perjalanan harian hingga puluhan kilometer (pulang-pergi $\ge 80\text{ km/hari}$), menghabiskan waktu berkendara hingga 2 jam per hari, dan menelan biaya belanja BBM yang signifikan.
2. **Penurunan Produktivitas & Disiplin:** Tingkat keterlambatan absensi meningkat akibat kendala lalu lintas di jalan raya antar-wilayah.
3. **Data Alamat Bersifat Naratif Teks:** Database kepegawaian eksisting (SIMPEG) hanya menyimpan alamat tempat tinggal berupa string teks naratif (jalan, RT/RW, kelurahan) tanpa adanya titik koordinat spasial (*latitude/longitude*).
4. **Subjektivitas Pengambilan Keputusan Mutasi:** Penentuan rotasi pegawai mendekati domisili rentan dilakukan secara subjektif dan berisiko melanggar batas Analisis Beban Kerja (ABK) jika unit kerja tujuan kelebihan kapasitas (*overstaffed*).

### 2.2 Rumusan Masalah Riset
1. Bagaimana mentransformasikan data domisili pegawai dari teks naratif menjadi data spasial yang presisi?
2. Bagaimana memodelkan kalkulasi jarak tempuh menggunakan Formula Haversine (geometri bola bumi) yang diselaraskan dengan jarak riil jaringan jalan raya (*road network*) menggunakan OSRM?
3. Bagaimana merancang Sistem Pendukung Keputusan (SPK) multi-kriteria menggunakan arsitektur 2-Tahap TOPSIS (Tahap 1: Ranking PNS pada Unit Kerja, Tahap 2: Ranking Unit Kerja pada PNS) dengan penanganan kriteria invarian? *(Rincian lengkap: lihat [METODOLOGI_SKRIPSI_TOPSIS.md](file:///Users/diyanto/simutasi-asn/METODOLOGI_SKRIPSI_TOPSIS.md))*.
4. Bagaimana merumuskan model pencocokan simetris (*Smart Swap Match*) untuk mengatasi kendala unit kerja yang tidak memiliki kuota formasi kosong (Zero-ABK Impact)?

---

## 3. METODOLOGI & FORMULASI MATEMATIS

### 3.1 Formula Haversine (Kalkulasi Jarak Spasial Lurus)
Formula Haversine digunakan untuk menghitung jarak lingkaran besar (*great-circle distance*) antara dua titik koordinat di permukaan bola bumi:
$$\Delta\phi = \text{lat}_2 - \text{lat}_1, \quad \Delta\lambda = \text{lng}_2 - \text{lng}_1$$
$$a = \sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)$$
$$c = 2 \cdot \arctan\left(\frac{\sqrt{a}}{\sqrt{1-a}}\right)$$
$$d = R \cdot c$$
*(dimana $R = 6.371\text{ km}$ adalah radius rata-rata bumi, sudut dihitung dalam radian).*

**Klasifikasi Zonasi Jarak Standar BKPSDM:**
* **Zona Hijau (Dekat):** $d < 5.0\text{ km}$ (Efisiensi optimal).
* **Zona Kuning (Sedang):** $5.0\text{ km} \le d \le 20.0\text{ km}$ (Beban perjalanan wajar).
* **Zona Merah (Jauh / Prioritas Mutasi):** $d > 20.0\text{ km}$ (Beban perjalanan tinggi / prioritas rotasi).

---

### 3.2 OSRM Road Network Routing (Jarak Riil Aspal Jalan Raya)
Open Source Routing Machine (OSRM) diintegrasikan untuk mengambil geometri jaringan jalan raya nyata (*asphalt road network*) berbasis data OpenStreetMap (OSM) menggunakan algoritma rute tercepat (*Contraction Hierarchies*):
$$\text{URL: } \text{https://router.project-osrm.org/route/v1/driving/}\{\text{lng}_1\},\{\text{lat}_1\};\{\text{lng}_2\},\{\text{lat}_2\}\text{?overview=full\&geometries=geojson}$$
Sistem mengekstrak koordinat poligon jalan aspal, jarak rute berkendara nyata ($\text{km}$), dan estimasi waktu tempuh ($\text{menit}$).

---

### 3.3 Metode TOPSIS dengan Pembobotan Dinamis (Dynamic Weighting)
TOPSIS (*Technique for Order Preference by Similarity to Ideal Solution*) menentukan alternatif terbaik berdasarkan jarak terpendek dari solusi ideal positif dan jarak terjauh dari solusi ideal negatif.

#### Kriteria Penilaian:
* $C_1$: Jarak Domisili ke Unit Baru dalam Km (*Cost* — makin kecil makin diprioritaskan).
* $C_2$: Masa Kerja di Unit Asal dalam Tahun (*Benefit* — makin lama makin diprioritaskan).
* $C_3$: Jenjang Pangkat / Golongan Ruang (*Benefit* — bobot hierarki kepangkatan).
* $C_4$: Penghematan Beban Jarak Harian dalam Km (*Benefit* — makin besar penghematan kilometer, makin prioritas).

#### Tahapan Perhitungan:
1. **Matriks Keputusan Ternormalisasi ($R$):**
   $$r_{ij} = \frac{x_{ij}}{\sqrt{\sum_{k=1}^m x_{kj}^2}}$$
2. **Matriks Keputusan Ternormalisasi Terbobot ($Y$):**
   $$y_{ij} = w_j \cdot r_{ij}, \quad \text{dimana } \sum_{j=1}^n w_j = 1.00 \quad (100\%)$$
3. **Solusi Ideal Positif ($A^+$) dan Negatif ($A^-$):**
   $$A^+ = \{y_1^+, y_2^+, \dots, y_n^+\}, \quad A^- = \{y_1^-, y_2^-, \dots, y_n^-\}$$
   * Jika kriteria bersifat *Benefit*: $y_j^+ = \max_i(y_{ij})$ dan $y_j^- = \min_i(y_{ij})$.
   * Jika kriteria bersifat *Cost*: $y_j^+ = \min_i(y_{ij})$ dan $y_j^- = \max_i(y_{ij})$.
4. **Jarak Separasi Euclidean ($D_i^+$ dan $D_i^-$):**
   $$D_i^+ = \sqrt{\sum_{j=1}^n (y_{ij} - y_j^+)^2}, \quad D_i^- = \sqrt{\sum_{j=1}^n (y_{ij} - y_j^-)^2}$$
5. **Nilai Preferensi Akhir ($V_i$):**
   $$V_i = \frac{D_i^-}{D_i^+ + D_i^-}, \quad 0 \le V_i \le 1$$
   *Alternatif dengan nilai $V_i$ mendekati 1 menempati peringkat (*ranking*) prioritas tertinggi.*

---

### 3.3.1 Kaidah & Justifikasi Akademik Pembobotan Dinamis (*Dynamic Weighting*)

> [!IMPORTANT]
> **Pedoman Ujian Sidang Skripsi: Pertanyaan Kerap Muncul Seputar Pembobotan Kriteria**
> 
> * **Pertanyaan Penguji:** *"Mengapa total pembobotan kriteria wajib divalidasi tepat $100\%$ ($1.00$)? Apakah boleh kurang atau lebih?"*
> * **Jawaban Ilmiah:** **Wajib tepat $100\%$ ($1.00$).** Dalam teori *Multi-Criteria Decision Making* (MCDM), bobot $w_j$ merepresentasikan derajat kepentingan relatif (*relative importance ratio*) dari masing-masing dimensi kriteria dalam ruang vektor Euclidean $n$-dimensi. Jika total bobot tidak dibatasi pada $\sum w_j = 1.00$, maka:
>   1. Perhitungan jarak pemisah Euclidean ($D_i^+$ dan $D_i^-$) akan mengalami distorsi magnitudo skala.
>   2. Nilai kedekatan relatif ($V_i$) kehilangan komparabilitasnya saat dilakukan analisis sensitivitas (*sensitivity analysis*).
>   3. Dengan normalisasi $\sum w_j = 1.00$, setiap penambahan bobot pada suatu kriteria secara adil mengurangi porsi bobot kriteria lainnya (*zero-sum trade-off*), menjamin objektivitas matematis tanpa bias subjektif.
>
> * **Pertanyaan Penguji:** *"Apakah pembobotan boleh diubah-ubah oleh Admin BKPSDM?"*
> * **Jawaban Ilmiah:** **Boleh dan sangat dianjurkan (*Dynamic Weighting*).** Kebijakan kepegawaian daerah bersifat dinamis bergantung pada periode prioritas kerja Pemda. Sistem menyediakan 3 skenario kebijakan (*preset*) yang dapat disimulasikan secara instan:
>   1. **Skenario Standar Seimbang (*Balanced Policy*):** $C_1 = 35\%$, $C_4 = 25\%$, $C_2 = 25\%$, $C_3 = 15\%$. Menyeimbangkan aspek kedekatan jarak fisik dengan rekam jejak loyalitas pegawai.
>   2. **Skenario Prioritas Mobilitas Hijau (*Green GovTech Mobility*):** $C_1 = 45\%$, $C_4 = 35\%$, $C_2 = 10\%$, $C_3 = 10\%$. Dipilih saat Pemkab memfokuskan efisiensi anggaran transportasi dan pengurangan beban emisi BBM ASN.
>   3. **Skenario Meritokrasi & Senioritas (*Merit-Based Seniority*):** $C_2 = 40\%$, $C_3 = 30\%$, $C_1 = 15\%$, $C_4 = 15\%$. Dipilih saat formasi jabatan mensyaratkan kematangan masa kerja dan kesesuaian eselon golongan.
>   
> Sistem mengimplementasikan **Dual-Layer Validation** (validasi real-time di antarmuka pengguna via JavaScript dan verifikasi ketat di backend `TopsisController`) sehingga formulir ditolak jika $\sum w_j \neq 1.00$. Dilengkapi pula fitur **Auto-Normalize** matematis $w_j' = \frac{w_j}{\sum w_k}$.

---

### 3.4 Model Smart Swap Match (Tukar Guling Simetris Zero-ABK)
Untuk kantor tujuan yang kuota formasinya penuh ($\text{kuota} = 0$), sistem menggunakan pencocokan berpasangan simetris (*Bipartite Mutual Matching*):
Diberikan Pegawai $A$ di Unit $U_A$ dan Pegawai $B$ di Unit $U_B$:
$$\text{Save}_A = \text{Dist}(A, U_A) - \text{Dist}(A, U_B)$$
$$\text{Save}_B = \text{Dist}(B, U_B) - \text{Dist}(B, U_A)$$
**Syarat Pertukaran Pareto Optimal:**
$$\text{Save}_A > 0 \quad \text{dan} \quad \text{Save}_B > 0$$
$$\text{Total Efisiensi Jarak Bersama} = \text{Save}_A + \text{Save}_B$$
*Dampak Formasi:* Unit $U_A$ berkurang 1 dan bertambah 1 ($\Delta = 0$). Unit $U_B$ berkurang 1 dan bertambah 1 ($\Delta = 0$). Kursi formasi tetap seimbang tanpa melanggar ABK.

---

### 3.5 Model Analitik Green GovTech (BBM & Emisi Karbon)
Kalkulasi dampak ekonomi dan lingkungan per pegawai atau per pasangan tukar guling:
* **Penghematan Jarak Tahunan:** $\text{KM}_{\text{tahun}} = \Delta\text{Jarak (PP)} \times 250\text{ hari kerja}$.
* **Penghematan Belanja BBM Pribadi:**
  $$\text{Hemat BBM (Rp)} = \left(\frac{\text{KM}_{\text{tahun}}}{35\text{ km/liter}}\right) \times \text{Rp } 11.500$$
* **Reduksi Emisi Gas Buang Karbon ($CO_2$):**
  $$\text{Reduksi } CO_2\text{ (kg)} = \text{KM}_{\text{tahun}} \times 0.12\text{ kg } CO_2\text{/km}$$

---

### 3.6 Model Regulasi Kepegawaian & Kepatuhan Spasial Regional

Untuk menjamin kepatuhan sistem terhadap peraturan perundang-undangan ASN nasional dan batas kewenangan otonomi daerah, sistem menerapkan model logika kepatuhan terintegrasi:

#### 1. Aturan Ikatan Pengabdian 10 Tahun (PermenPAN-RB No. 27 Tahun 2021 Pasal 52)
Bagi ASN dengan tahun pengangkatan CPNS $\ge 2019$, berlaku komitmen tidak mengajukan pindah instansi keluar daerah selama 10 tahun sejak TMT CPNS.
$$\text{Tahun CPNS} = \text{Substring}(\text{NIP}, 9, 4)$$
$$\text{Masa Pengabdian} = \text{Tahun Berjalan} - \text{Tahun CPNS}$$
* **Logika Sistem:**
  * **Mutasi Internal (Antar-SKPD Indramayu):** **DIIZINKAN & DIREKOMENDASIKAN** sebagai instrumen mitigasi agar pegawai tidak mengalami kelelahan (*burnout*) atau mengundurkan diri akibat jarak tempuh jauh.
  * **Mutasi Eksternal (Keluar Daerah):** **TERKUNCI (*Locked Rule*)** jika $\text{Masa Pengabdian} < 10\text{ Tahun}$, disertai notifikasi sisa tahun komitmen.

#### 2. Model Optimalisasi Komuter Wilayah Perbatasan (Cirebon, Majalengka, Subang)
Kasus ASN berdomisili di luar perbatasan kabupaten (misalnya tinggal di Kabupaten/Kota Cirebon namun berstatus ASN Pemkab Indramayu):
* **Batas Kewenangan Instansi:** Sistem **TIDAK MEREKOMENDASIKAN** kantor di luar Pemkab Indramayu (karena perbedaan entitas APBD dan formasi BKN).
* **Solusi Geospasial Spasial:** Algoritma TOPSIS dan OSRM memindai seluruh unit kerja di Kabupaten Indramayu dan merekomendasikan **kantor Indramayu di zona koridor terdekat dari perbatasan Cirebon** (seperti Kecamatan Krangkeng, Kertasmaya, Sukagumiwang, Kedokan Bunder) untuk memangkas waktu tempuh komuter harian.

#### 3. Penanganan Kasus Ekstra-Teritorial (> 100 Km / Luar Pulau / Riau)
Jika koordinat domisili ASN berada di luar radius komuter harian ($d > 100\text{ km}$ atau luar provinsi):
* Sistem memicu **Notice Spasial Ekstra-Teritorial**.
* Pada kalkulasi TOPSIS, nilai kriteria Cost Jarak Baru ($C_1$) bernilai sangat tinggi sehingga secara matematis terdegradasi ke urutan bawah untuk rotasi internal harian.
* Sistem menandai status pegawai untuk diajukan ke sidang khusus Tim Baperjakat dengan opsi rekomendasi: *"Usulan Pindah Instansi Luar Daerah (Lolos Butuh BKN)"* jika masa kerja telah memenuhi syarat regulasi.

---

## 4. PERSONA & HAK AKSES PENGGUNA

| Persona | Deskripsi Peran | Hak Akses Utama |
| :--- | :--- | :--- |
| **Pegawai (ASN)** | Pengguna mandiri (*self-service*) | Login via SSO portal, pembaruan titik pinpoint domisili di peta, aktivasi GPS perangkat, monitoring jarak harian, pengajuan mutasi. |
| **Admin BKPSDM** | Pengelola data kepegawaian & mutasi | Manajemen master unit kerja & formasi ABK, penyesuaian bobot dinamis TOPSIS, eksekusi pemeringkatan mutasi, pencocokan Smart Swap Match. |
| **Tim Baperjakat / Pimpinan** | Pengambil keputusan mutasi daerah | Peninjauan dasbor makro peta GIS, heatmap kepadatan, review matriks nilai $V_i$, ekspor naskah berita acara rekomendasi sidang. |

---

## 5. KEBUTUHAN FUNGSIONAL LENGKAP (FUNCTIONAL REQUIREMENTS)

### Modul 1: Autentikasi Mandiri & Integrasi Sistem (Standalone Native Auth & SSO Bridge)
* **FR-01 (Native Authentication):** Sistem beroperasi secara independen (*standalone*) dengan form login mandiri yang mendukung input **18 Digit NIP** maupun **Alamat Email**, dengan verifikasi hash kata sandi aman (`bcrypt`).
* **FR-02 (Role-Based Access Control - RBAC):** Pemisahan otorisasi ketat melalui middleware `RoleMiddleware`:
  * Role `admin` memiliki wewenang penuh terhadap seluruh dasbor makro, manajemen kriteria TOPSIS dinamis, master formasi unit kerja, dan eksekusi sidang Baperjakat.
  * Role `asn` memiliki akses terproteksi ke dasbor mandiri, profil & pembaruan koordinat GPS domisili, simulasi penempatan Tahap 2, dan pengajuan mutasi mandiri.
* **FR-03 (SSO Compatibility Bridge):** Tetap menyediakan endpoint `/sso/verify` yang mengonsumsi token sekali pakai (*One-Time Ephemeral Token*) dari tabel `sso_tokens` dengan masa kedaluwarsa maksimal 2 menit untuk kompatibilitas integrasi portal eksternal.

### Modul 2: Pembaruan Mandiri Titik Domisili (Self-Service Georeferencing)
* **FR-04:** Menyediakan cascading dropdown wilayah (Provinsi, Kabupaten/Kota, Kecamatan, Kelurahan/Desa) berbasis master data Kemendagri.
* **FR-05:** Menyediakan peta pinpoint interaktif Leaflet dengan marker yang dapat digeser (*draggable pin*).
* **FR-06:** Menyediakan tombol sensor deteksi GPS instan menggunakan HTML5 Geolocation API.
* **FR-07:** *Live Preview Distance*: Menampilkan perubahan angka jarak secara real-time saat marker digeser di peta.

### Modul 3: Visualisasi Geospasial (GIS Dashboard)
* **FR-08:** Menampilkan marker kantor dinas (icon gedung navy) dan marker rumah ASN (icon rumah bergradasi hijau/kuning/merah).
* **FR-09:** Dilengkapi **efek animasi ripple gelombang melingkar** pada setiap marker untuk menunjukkan pulsasi titik spasial aktif.
* **FR-10 (Pure OSRM Road Curve):** Menampilkan garis rute mengemudi nyata yang meliuk-liuk di atas jalan aspal pantura/provinsi via OSRM Driving Engine, lengkap dengan informasi jarak rute berkendara nyata ($\text{km}$) dan estimasi durasi tempuh ($\text{menit}$).
* **FR-11 (Fokus Rute Jalan Raya Nyata):** Visualisasi rute pada antarmuka peta dasbor difokuskan secara murni pada rute fisik jalan raya (OSRM), mengeliminasi garis lurus imajiner (Haversine) dari antarmuka visual agar representasi spasial sesuai dengan medan tempuh nyata ASN. Formula Haversine tetap dipertahankan pada service layer sebagai dasar komparasi teoretis dan fallback matematis jika koneksi jaringan OSRM mengalami gangguan.
* **FR-12 (Heatmap Layer):** Menyediakan layer **Heatmap Kepadatan Domisili (Leaflet.heat)** untuk melihat hotspot sebaran tempat tinggal ASN sekabupaten.
* **FR-13 (Direct Google Maps):** Popup marker kantor, rumah, dan garis rute menyediakan tautan navigasi langsung ke Google Maps (`target="_blank"`).

### Modul 4: Manajemen Formasi & Analisis Beban Kerja (ABK Guard)
* **FR-14:** Pengelolaan data unit kerja mencakup titik kantor absensi, bezetting riil, kebutuhan ABK, dan kuota formasi lowong.
* **FR-15:** *ABK Guard Rule*: Unit kerja dengan kuota $\le 0$ otomatis diblokir dari daftar tujuan mutasi masuk biasa guna mencegah *overstaffing*.

### Modul 5: Sistem Pendukung Keputusan (Engine TOPSIS Fleksibel)
* **FR-16 (Dynamic Component Criteria):** Mendukung penambahan (*Add*), penghapusan (*Delete*), dan pengaktifan/penonaktifan (*Toggle Active/Inactive*) komponen kriteria secara modular (dapat berjalan fleksibel dengan 3 kriteria, 4 kriteria standar, maupun 5 kriteria komprehensif yang mencakup Nilai SKP Kinerja ASN, Usia, dsb.).
* **FR-17 (Strict 100% Validation & Auto-Normalize):** Pengaturan pembobotan dinamis (*Dynamic Weighting*) pada seluruh kriteria aktif dengan validasi ketat total bobot wajib tepat $1.00$ ($100\%$), dilengkapi tombol stepper ($\pm 5\%$) serta fitur matematis *Auto-Normalize* $w_j' = \frac{w_j}{\sum w_k}$.
* **FR-18:** Kalkulasi transparan mencakup matriks normalisasi $R$, matriks terbobot $Y$, solusi ideal $A^+$ dan $A^-$, jarak separasi $D^+$ dan $D^-$, serta nilai preferensi akhir $V_i$ pada ruang vektor Euclidean $n$-dimensi.
* **FR-19:** Pemeringkatan kandidat pegawai dari nilai $V_i$ tertinggi ke terendah dengan penandaan batas kuota lowong.
* **FR-20:** Matriks audit matematis dapat dibuka-tutup (*accordion*) untuk kebutuhan sidang skripsi/pertanggungjawaban ilmiah.

### Modul 6: Smart Swap Match (Tukar Guling Simetris Zero-ABK)
* **FR-21:** Algoritma otomatis memindai seluruh kombinasi pegawai lintas instansi dan mendeteksi pasangan simetris yang keduanya sama-sama hemat jarak ($\text{Save}_A > 0$ dan $\text{Save}_B > 0$).
* **FR-22:** Menampilkan visualisasi diagram pertukaran posisi kantor A $\leftrightarrow$ B, penghematan kilometer harian bersama, dan persentase skor kompatibilitas.
* **FR-23:** Menyediakan tombol komitmen *"Ajukan Paket Tukar Guling"* ke agenda sidang Baperjakat.

### Modul 7: Analitik Green GovTech & Pelaporan
* **FR-24:** Dashboard analitik menghitung estimasi penghematan pengeluaran BBM pegawai (Rupiah/tahun) dan tonase reduksi emisi $CO_2$.
* **FR-25:** Fitur cetak laporan resmi rekomendasi mutasi dan berita acara sidang Baperjakat (*print-optimized view*).

### Modul 8: Kepatuhan Regulasi Kepegawaian & Kategori Spasial Regional
* **FR-26 (BKN 10-Year Commitment Guard):** Ekstraksi otomatis tahun TMT CPNS dari 18 digit NIP. Bagi NIP $\ge 2019$ dengan masa kerja $< 10$ tahun, sistem menandai izin rotasi internal Indramayu sah, namun mengunci hak usulan mutasi keluar daerah sesuai PermenPAN-RB No. 27/2021.
* **FR-27 (Border Commuter Optimization):** Deteksi spasial ASN berdomisili perbatasan (Cirebon, Majalengka, Subang) dengan prioritas rekomendasi unit kerja Indramayu di zona koridor terdekat perbatasan.
* **FR-28 (Extra-Territorial Anomaly Notice):** Deteksi otomatis ASN dengan jarak domisili $> 100\text{ km}$ atau luar provinsi (seperti Riau, Medan) dengan penandaan notice khusus usulan pindah instansi luar daerah untuk sidang Baperjakat.

### Modul 9: Simulasi Rekomendasi Mandiri ASN & AI Context-Aware Smart Reason Generator
* **FR-29 (Simulasi Penempatan Mandiri - TOPSIS Tahap 2):** Pegawai ASN dapat menjalankan simulasi mandiri untuk memindai seluruh unit kerja se-kabupaten yang memiliki kuota formasi kosong ($\text{kuota} > 0$), mengevaluasi alternatif kantor menggunakan algoritma TOPSIS berbasis 3 kriteria ($C_1$: Jarak OSRM Baru [Cost], $C_2$: Sisa Kuota Formasi [Benefit], $C_3$: Efisiensi Penghematan Jarak [Benefit]), serta menampilkan visualisasi rute OSRM dari domisili ke unit rekomendasi teratas.
* **FR-30 (AI Context-Aware Smart Reason Generator):** Pada modal pengajuan permohonan mutasi, asisten AI secara otomatis menganalisis parameter riil pegawai (*Nama, NIP, Jabatan, Kantor Asal, Jarak Lama, Kantor Tujuan, Jarak Baru, Selisih Efisiensi Jarak km, dan Sisa Kuota Formasi*) untuk menghasilkan draf surat permohonan mutasi resmi.
* **FR-31 (Multi-Style Administrative Narratives):** Menyediakan 3 gaya pertimbangan birokrasi yang siap disisipkan ke berkas sidang Baperjakat:
  1. *Efisiensi Jarak & Penghematan Mobilitas*
  2. *Kedekatan Domisili Keluarga & Stabilitas Kerja*
  3. *Optimalisasi Formasi ABK & Kinerja Organisasi*
* **FR-32 (Pengajuan Usulan ke Baperjakat):** ASN dapat mengirimkan formulir permohonan mutasi beserta alasan yang telah di-generate ke database rekomendasi (`rekomendasi_mutasi`) dengan status `diajukan_baperjakat` untuk ditindaklanjuti pada sidang pertimbangan mutasi.

### Modul 10: Pengelolaan Master Data Independen (Master ASN, Unit Kerja, Formasi Jabatan, & Master Wilayah)
* **FR-33 (Master Data Pegawai ASN CRUD):** Admin BKPSDM dapat mendaftarkan ASN baru, mengedit biodata kepegawaian lengkap (jabatan, pangkat/golongan, unit kerja, masa kerja, TMT kerja), menentukan koordinat domisili via pinpoint Leaflet, dan menghapus data pegawai. Pendaftaran ASN baru secara otomatis menginisiasi akun login mandiri (`users`) dengan peran `asn`.
* **FR-34 (Master Data Unit Kerja CRUD):** Admin BKPSDM dapat menambah unit kerja baru, memetakan koordinat kantor absensi di peta, mengelola kategori instansi (Dinas, Badan, Kecamatan, UPTD, Sekolah), mengedit kebutuhan ABK, bezetting riil, serta kuota formasi kosong. Dilengkapi proteksi penghapusan jika unit masih menaungi pegawai aktif.
* **FR-35 (Master Data Wilayah & Geospasial CRUD):** Pengelolaan referensi 31 kecamatan di Kabupaten Indramayu mencakup kode Kemendagri, nama kecamatan, titik koordinat pusat spasial (lat/lng), jumlah desa, dan karakteristik wilayah. Dilengkapi peta interaktif untuk menentukan titik pusat serta fitur *fly-to* otomatis saat memilih kecamatan pada form domisili.
* **FR-36 (Master Formasi Jabatan Dinamis & Multi-Year Tracing):** Pengelolaan detail alokasi formasi kebutuhan jabatan fungsional/struktural (nama jabatan, jenjang, kualifikasi pendidikan linier, kebutuhan ABK, bezetting, kuota lowong, status terbuka/terisi/tutup) dengan filter audit riwayat multi-tahun (e.g. 2025 historis vs 2026 aktif berjalan). Menghubungkan otomatis ketersediaan formasi linier ke menu Simulasi Mandiri ASN serta auto-sync kuota instansi.
* **FR-37 (Eksekusi SK Mutasi & Closed-Loop Baperjakat):** Tim Penilai Kinerja / Baperjakat dapat menyetujui rekomendasi mutasi (menerbitkan status `disetujui`), yang secara otomatis mengeksekusi perpindahan unit kerja ASN di basis data (`asn_master.id_unit_saat_ini`), mengkalkulasi ulang jarak dinas baru, serta menyesuaikan bezetting dan kuota formasi unit asal maupun unit tujuan secara riil. Tersedia pula opsi penolakan dengan input catatan berita acara sidang.
* **FR-38 (Pengajuan Paket Tukar Guling ke Baperjakat):** Modul Smart Swap Match terhubung langsung ke basis data Baperjakat, memungkinkan pengguna mengajukan paket pasangan pertukaran simetris (*Zero-ABK*) untuk langsung masuk ke antrean agenda sidang Baperjakat.

---

## 6. KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)

* **Performance:** Perhitungan matriks TOPSIS dan pencarian pasangan Swap Match selesai dalam waktu $< 2$ detik untuk $1.000$ data pegawai.
* **Security & Integrity:** Perlindungan terhadap SQL Injection melalui PDO Prepared Statements, proteksi CSRF pada seluruh form POST/PUT, dan sanitasi koordinat lintang/bujur.
* **Design System Anti-Slop:**
  * **Tema Light Theme Wajib:** Palet warna resmi GovTech (Navy Blue `#0f172a`, Emerald Green `#059669`, Amber `#d97706`, Rose Red `#dc2626`).
  * **Navigasi Sidebar Kiri:** Model laci / sidebar modern yang memberikan ruang kanvas peta lebih luas dan proporsional.
  * **Animasi Fisika Pegas:** Mengadopsi `.interactive-card` (lift hover physics) dan `.table-row-standard` (nudge geser kanan pada hover).
* **Interoperabilitas:** Berjalan lancar di atas database MariaDB 13.0 pada server lokal/home-server.

---

## 7. STRUKTUR BASIS DATA RELASIONAL (MARIADB SCHEMA)

```
[sso_tokens]
├── token : varchar(64) [PK]
├── nip : varchar(30) [INDEX]
├── role : varchar(20) [default 'asn']
├── expired_at : datetime [INDEX]
└── timestamps

[unit_kerja]
├── id : bigint [PK, AI]
├── kode_unit : varchar(50) [UNIQUE]
├── nama_unit : varchar(150) [INDEX]
├── kategori : varchar(50) [Dinas, Badan, Kecamatan, UPTD, Sekolah]
├── kantor_lat : decimal(10,8)
├── kantor_lng : decimal(11,8)
├── alamat_kantor : text
├── kuota_formasi_kosong : int [INDEX]
├── bezetting_saat_ini : int
├── kebutuhan_abk : int
└── timestamps

[asn_master]
├── id : bigint [PK, AI]
├── nip : varchar(30) [UNIQUE, INDEX]
├── nama : varchar(150) [INDEX]
├── gelar_depan : varchar(20)
├── gelar_belakang : varchar(20)
├── jabatan : varchar(150)
├── pangkat_golongan : varchar(20) [INDEX]
├── masa_kerja_tahun : int
├── tmt_kerja_unit : date
├── id_unit_saat_ini : bigint [FK -> unit_kerja.id]
├── domisili_lat : decimal(10,8) [INDEX]
├── domisili_lng : decimal(11,8) [INDEX]
├── provinsi : varchar(100)
├── kabupaten_kota : varchar(100)
├── kecamatan : varchar(100)
├── kelurahan_desa : varchar(100)
├── kode_desa : varchar(20)
├── alamat_detail : text
├── jarak_ke_kantor_km : decimal(8,2) [INDEX]
├── zona_jarak : enum('dekat', 'sedang', 'jauh') [INDEX]
├── status_permohonan_mutasi : boolean
├── alasan_mutasi : text
└── timestamps

[topsis_kriteria]
├── id : bigint [PK, AI]
├── kode_kriteria : varchar(10) [UNIQUE, INDEX] -> C1, C2, C3, C4
├── nama_kriteria : varchar(100)
├── bobot : decimal(5,2)
├── atribut : enum('cost', 'benefit')
├── keterangan : text
└── timestamps

[rekomendasi_mutasi]
├── id : bigint [PK, AI]
├── id_asn : bigint [FK -> asn_master.id]
├── id_unit_asal : bigint [FK -> unit_kerja.id]
├── id_unit_tujuan : bigint [FK -> unit_kerja.id]
├── jarak_asal_km : decimal(8,2)
├── jarak_baru_km : decimal(8,2)
├── selisih_hemat_jarak_km : decimal(8,2) [INDEX]
├── nilai_preferensi_v : decimal(8,6) [INDEX]
├── ranking : int [INDEX]
├── status_usulan : enum('draft', 'diajukan_baperjakat', 'disetujui', 'ditolak')
├── catatan_baperjakat : text
├── tanggal_kalkulasi : timestamp
└── timestamps

[master_lokasi]
├── id : bigint [PK, AI]
├── kode_kecamatan : varchar(20) [UNIQUE, INDEX] -> Kode Kemendagri
├── nama_kecamatan : varchar(100) [INDEX] -> 31 Kecamatan Indramayu
├── pusat_lat : decimal(10,8)
├── pusat_lng : decimal(11,8)
├── jumlah_desa : int
├── keterangan : text
└── timestamps

[formasi_jabatan]
├── id : bigint [PK, AI]
├── id_unit_kerja : bigint [FK -> unit_kerja.id, INDEX]
├── tahun_anggaran : int [INDEX] -> 2024, 2025, 2026, 2027
├── periode : varchar(50) -> Reguler, Semester 1
├── nama_jabatan : varchar(150) [INDEX] -> Analis SDM, Pranata Komputer, dll
├── jenjang_jabatan : varchar(50) -> Ahli Pertama, Ahli Muda, Pelaksana
├── kualifikasi_pendidikan : varchar(150)
├── kebutuhan_abk : int
├── bezetting_riil : int
├── kuota_lowong : int
├── status : enum('terbuka', 'terisi', 'ditutup') [INDEX]
├── keterangan : text
└── timestamps
```

---

## 8. STRUKTUR KODE & SERVICE LAYER (CLEAN ARCHITECTURE)

* `app/Services/HaversineService.php` : Perhitungan trigonometri spherical great-circle distance dan penentuan zona warna (dekat, sedang, jauh).
* `app/Services/TopsisService.php` : Mesin normalisasi matriks, pembobotan dinamis, penentuan solusi ideal, jarak separasi, dan pemeringkatan preferensi $V_i$.
* `app/Services/SwapMatchService.php` : Algoritma pencocokan simetris bipartit dua arah untuk menemukan pasangan tukar guling yang saling menguntungkan (Zero-ABK).
* `app/Http/Controllers/AuthController.php` : Modul autentikasi mandiri berbasis NIP 18 digit atau email, verifikasi bcrypt, dan tombol demo instan.
* `app/Http/Controllers/SimulasiMandiriController.php` : Modul simulasi penempatan mandiri ASN (TOPSIS Tahap 2), visualisasi rute jalan raya OSRM, modal pengajuan mutasi dengan AI Smart Reason Generator.
* `app/Http/Controllers/DashboardController.php` : Penyaji data spasial makro GIS, agregasi statistik zona, dan titik koordinat untuk Leaflet.
* `app/Http/Controllers/AsnController.php` : Modul kepegawaian (CRUD lengkap, pembuatan akun login otomatis) dan pembaruan mandiri titik koordinat domisili.
* `app/Http/Controllers/UnitKerjaController.php` : Modul master unit kerja (CRUD lengkap, kategori instansi, peta kantor, kebutuhan ABK, dan kuota formasi lowong).
* `app/Http/Controllers/FormasiJabatanController.php` : Modul master formasi jabatan & rekam jejak tracing ABK multi-tahun (2025, 2026, status formasi terbuka/terisi/tutup, dan auto-sync kuota unit).
* `app/Http/Controllers/MasterLokasiController.php` : Modul master data wilayah / kecamatan (CRUD lengkap 31 kecamatan Indramayu, titik pusat spasial, dan integrasi peta).
* `app/Http/Controllers/KriteriaController.php` : Modul master kriteria & pembobotan dinamis TOPSIS terpisah (CRUD kriteria, toggle aktif/nonaktif, penyesuaian bobot 100%, preset kebijakan, dan auto-normalize).
* `app/Http/Controllers/TopsisController.php` : Eksekutor SPK Tahap 1 (Top-Down Admin), penyimpan draf usulan sidang, dan pengelola bobot kriteria dinamis.
* `app/Http/Controllers/SwapMatchController.php` : Pengendali antarmuka pencocokan tukar guling ASN (Zero-ABK).
* `app/Http/Controllers/SsoController.php` : Gerbang Single Sign-On (SSO) terintegrasi portal eksisting via one-time token.
* `app/Http/Middleware/RoleMiddleware.php` : Penjaga otorisasi berbasis peran (`admin` vs `asn`).

---

## 9. ROADMAP & RENCANA PENGEMBANGAN PASCA-SKRIPSI

1. **Fase 1 (Selesai — MVP Skripsi & Uji Coba BKPSDM):**
   * Autentikasi Mandiri NIP/Email & RBAC terproteksi.
   * Modul pembaruan domisili spasial & deteksi GPS.
   * Peta GIS dengan Pure OSRM Road Curve & Animasi Ripple.
   * Layer Heatmap Kepadatan Domisili Pegawai.
   * Mesin TOPSIS Dynamic Weighting + Audit Akademis (Tahap 1 & Tahap 2).
   * Modul Simulasi Rekomendasi Mandiri ASN dengan AI Context-Aware Smart Reason Generator.
   * Mesin Smart Swap Match (Tukar Guling Zero-ABK).
   * Kalkulator Analitik Hemat BBM & Reduksi Emisi Karbon.
   * Integrasi MariaDB 13.0 lokal / home-server & SQLite fallback.
2. **Fase 2 (Rencana Lanjutan Pasca-Sidang):**
   * WhatsApp Gateway Notifikasi Otomatis (Fonnte / Baileys).
   * Generator Dokumen PDF Surat Keputusan (SK) Mutasi Bupati ber-QR Code Tanda Tangan Elektronik (TTE BSrE BSSN).
   * Integrasi API Webhook dua arah dengan SIASN BKN Pusat.

---

## 10. REFERENSI DOKUMEN & STANDAR KEPEGAWAIAN

* Dokumen Glosarium & Skenario Kasus ABK: [DOKUMENTASI_ABK_FORMASI_DAN_SINKRONISASI.md](file:///Users/diyanto/simutasi-asn/DOKUMENTASI_ABK_FORMASI_DAN_SINKRONISASI.md)
* Dokumen Metodologi & Notasi Ilmiah TOPSIS: [METODOLOGI_SKRIPSI_TOPSIS.md](file:///Users/diyanto/simutasi-asn/METODOLOGI_SKRIPSI_TOPSIS.md)
* Dokumen Desain Perangkat Lunak UML & ERD: [DESAIN_SISTEM_UML_FLOWCHART_ERD.md](file:///Users/diyanto/simutasi-asn/DESAIN_SISTEM_UML_FLOWCHART_ERD.md)
* Dokumen Riwayat Migrasi Database: [MIGRATIONS.md](file:///Users/diyanto/simutasi-asn/MIGRATIONS.md)

