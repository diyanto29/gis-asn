# METODOLOGI SKRIPSI: MODEL 2-TAHAP TOPSIS & OSRM
## Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN (SIMUTASI-GIS)

> **Dokumen Rujukan Akademik (Bab 3 Metodologi & Bab 4 Pembahasan)**  
> Memuat landasan matematis, perbandingan 2 sudut pandang ranking, justifikasi kriteria, pencegahan anomali kriteria konstan, dan panduan tanya-jawab ujian skripsi.

---

## 1. Arsitektur 2-Tahap / 2-Ranking (Dual-Perspective SPK)

Dalam penempatan dan mutasi ASN, permasalahan diselesaikan menggunakan pendekatan komprehensif dari hulu ke hilir:

```
                          ALUR KEPUTUSAN 2-TAHAP
┌────────────────────────────────────────────────────────────────────────┐
│ TAHAP 1: SELEKSI PRIORITAS PEGAWAI (Top-Down / Perspektif BKPSDM)       │
│ Unit Kerja Tujuan sbg Pusat -> Alternatif: Seluruh ASN Kandidat         │
│ "Siapa ASN yang paling berhak dan layak dipindahkan ke Unit Kerja X?"  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Menghasilkan: Daftar ASN Prioritas
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ TAHAP 2: REKOMENDASI PENEMPATAN OPTIMAL (Bottom-Up / Mandiri ASN)      │
│ Satu ASN Tertentu sbg Subjek -> Alternatif: Unit-Unit Kerja Berformasi │
│ "Dari sekian kantor berformasi, kantor mana yang paling ideal utk ASN?"│
└────────────────────────────────────────────────────────────────────────┘
```

### Mengapa Alur 2-Tahap Ini Krusial Secara Ilmiah?
1. **Mencegah Kebingungan Pimpinan (Tanpa Unit Tujuan):** Jika pimpinan hanya mengetahui daftar ranking ASN terjauh tanpa target unit, pimpinan tetap bingung harus memindahkan mereka ke mana.
2. **Mencegah Rebutan Formasi (Tanpa Filter Prioritas):** Jika seluruh ASN bebas memilih dinas terdekat tanpa seleksi hak, ASN yang jaraknya hanya 4 km akan berebut kantor yang berjarak 1 km dari rumahnya, menggeser ASN yang rumahnya 45 km.
3. **Kombinasi Kebijakan (Hulu ke Hilir):** Menentukan **siapa** yang paling berhak, sekaligus memberikan rekomendasi **ke mana** lokasi terbaiknya.

---

## 2. Perbandingan Matriks Keputusan: Kasus 1 vs Kasus 2

| Parameter | Tahap 1: Ranking PNS pada Unit Kerja | Tahap 2: Ranking Unit Kerja pada PNS |
| :--- | :--- | :--- |
| **Pusat Evaluasi** | 1 Unit Kerja Tujuan (Target Formasi) | 1 Orang Pegawai ASN (Subjek Mutasi) |
| **Alternatif Keputusan ($A_i$)** | Para Pegawai ASN ($A_1 = \text{Budi}, A_2 = \text{Siti}, \dots$) | Unit-Unit Kerja Berformasi ($A_1 = \text{Kec. A}, A_2 = \text{Pusk. B}, \dots$) |
| **Tujuan Praktis** | Hak akses Admin BKPSDM / Tim Penilai Baperjakat | Hak akses Simulasi Rekomendasi Mandiri ASN |
| **Sifat Kriteria** | Karakteristik yang berbeda antar-pegawai | Karakteristik yang berbeda antar-unit kerja |

---

## 3. Justifikasi Kriteria & Pencegahan Jebakan Akademik (Invarian)

### 3.1 Kriteria Tahap 1 (Ranking PNS pada Unit Kerja)
Seluruh kriteria menempel pada atribut masing-masing kandidat ASN:
* **$C_1$ Jarak Riil OSRM ($Cost$):** Jarak tempuh fisik jalan raya dari domisili ASN ke Unit Kerja Tujuan.
* **$C_2$ Masa Jabatan ($Benefit$):** Lama ASN menduduki jabatan saat ini (akumulasi pengalaman jabatan).
* **$C_3$ Masa Kerja Total ($Benefit$):** Total pengabdian sebagai ASN (senioritas aparatur).
* **$C_4$ Tingkat Beban Unit Asal ($Benefit$):** Rasio kelebihan beban di unit kerja asal ASN (unit yang kelebihan staf diprioritaskan dilepas).
* **$C_5$ Penghematan Jarak ($Benefit$):** Selisih $\max(0, \text{Jarak Lama} - \text{Jarak Baru})$.

---

### 3.2 Kriteria Tahap 2 (Ranking Unit Kerja pada PNS)

> [!WARNING]
> **Jebakan Akademik Kriteria Konstan (Invarian):**  
> `Masa Kerja` dan `Masa Jabatan` ASN **TIDAK BOLEH** dimasukkan ke dalam matriks TOPSIS Tahap 2.  
> **Bukti Matematis:** Jika mengevaluasi 1 orang ASN (misal Budi dengan masa kerja 10 tahun) terhadap 5 Unit Kerja alternatif ($A_1 \dots A_5$), maka kolom nilai masa kerja bernilai:  
> $$X_{C2} = [10, 10, 10, 10, 10]^T$$  
> Saat dinormalisasi:  
> $$r_{i2} = \frac{10}{\sqrt{10^2 \times 5}} = \frac{1}{\sqrt{5}} \quad (\text{sama untuk semua } i)$$  
> Akibatnya, nilai solusi ideal positif dan negatif menjadi identik:  
> $$A_{C2}^+ = A_{C2}^- = w_2 \cdot \frac{1}{\sqrt{5}}$$  
> Jarak separasinya:  
> $$(y_{i2} - A_{C2}^+)^2 = 0$$  
> **Kriteria tersebut berkontribusi 0 (nol) terhadap pemeringkatan dan menjadi cacat metodologis.**

### Solusi Ilmiah yang Diterapkan:
1. **Masa Kerja & Masa Jabatan dijadikan "Eligibility Filter / Prasyarat Kelayakan":**
   - ASN hanya diizinkan mengajukan simulasi jika masa kerja memenuhi regulasi pemerintah (misal: Komitmen Pengabdian 10 Tahun BKN atau minimal 5 tahun).
2. **Kriteria TOPSIS Tahap 2 Murni Mengukur Atribut Unit Kerja:**
   * **$C_1$ Jarak Rute Riil OSRM ($Cost$):** Jarak jaringan jalan dari rumah ASN ke kantor unit kerja alternatif $A_i$.
   * **$C_2$ Kesesuaian Kualifikasi Formasi ($Benefit$):** Ketersediaan formasi lowong yang linear dengan jabatan/pendidikan ASN.
   * **$C_3$ Indeks Kebutuhan Pegawai / Sisa Kuota ($Benefit$):** Tingkat urgensi unit kerja yang kekurangan pegawai berdasarkan ABK.
   * **$C_4$ Efisiensi Penghematan Jarak ($Benefit$):** $\Delta \text{Jarak} = \text{Jarak Kantor Lama} - \text{Jarak Kantor Alternatif}$.

---

## 4. Rumus Matematis Formal TOPSIS (Bab 3 Skripsi)

### Langkah 1: Matriks Keputusan ($X$)
$$X = \begin{pmatrix} 
x_{11} & x_{12} & \dots & x_{1n} \\
x_{21} & x_{22} & \dots & x_{2n} \\
\vdots & \vdots & \ddots & \vdots \\
x_{m1} & x_{m2} & \dots & x_{mn} 
\end{pmatrix}$$

### Langkah 2: Normalisasi Euclidean ($R$)
$$r_{ij} = \frac{x_{ij}}{\sqrt{\sum_{i=1}^{m} x_{ij}^2}}, \quad i=1, \dots, m; \quad j=1, \dots, n$$

### Langkah 3: Matriks Ternormalisasi Terbobot ($Y$)
$$y_{ij} = w_j \cdot r_{ij}, \quad \text{dengan } \sum_{j=1}^{n} w_j = 1.00$$

### Langkah 4: Solusi Ideal Positif ($A^+$) dan Solusi Ideal Negatif ($A^-$)
$$A^+ = (y_1^+, y_2^+, \dots, y_n^+)$$
$$y_j^+ = \begin{cases} \max_{i}(y_{ij}), & \text{jika } j \text{ adalah kriteria } Benefit \\ \min_{i}(y_{ij}), & \text{jika } j \text{ adalah kriteria } Cost \end{cases}$$

$$A^- = (y_1^-, y_2^-, \dots, y_n^-)$$
$$y_j^- = \begin{cases} \min_{i}(y_{ij}), & \text{jika } j \text{ adalah kriteria } Benefit \\ \max_{i}(y_{ij}), & \text{jika } j \text{ adalah kriteria } Cost \end{cases}$$

### Langkah 5: Jarak Solusi Ideal Separasi ($D_i^+$ dan $D_i^-$)
$$D_i^+ = \sqrt{\sum_{j=1}^{n} (y_{ij} - y_j^+)^2}$$
$$D_i^- = \sqrt{\sum_{j=1}^{n} (y_{ij} - y_j^-)^2}$$

### Langkah 6: Nilai Preferensi Relatif ($V_i$) & Perangkingan
$$V_i = \frac{D_i^-}{D_i^+ + D_i^-}, \quad V_i \in [0, 1]$$
*Alternatif dengan nilai $V_i$ tertinggi menempati peringkat ke-1 (Rekomendasi Utama).*

---

## 5. Peran Geospasial: OSRM Routing Engine vs Haversine

* **OSRM (Open Source Routing Machine) - Metode Utama:**
  - Menghitung rute fisik berbasis jaringan jalan raya OpenStreetMap (*road network*).
  - Memperhitungkan kelokan jalan, akses jembatan, jalan satu arah, dan batas kecepatan.
  - Menghasilkan dua parameter riil: **Jarak Tempuh ($km$)** dan **Estimasi Waktu Tempuh ($menit$)**.
* **Haversine Formula - Metode Cadangan (*Fallback / Benchmark*):**
  - Digunakan sebagai *baseline* komparasi teoritis dan failover apabila koneksi server OSRM mengalami gangguan.

---

## 6. Panduan Tanya-Jawab Ujian Sidang Skripsi (Defense Cheat Sheet)

**Q1: Mengapa sistem ini menggunakan 2 model pemeringkatan (2 Tahap)?**
> *Jawaban:*  
> Mutasi ASN melibatkan dua kepentingan yang saling terkait. Tahap 1 memenuhi kebutuhan manajerial BKPSDM untuk menyeleksi pegawai mana yang paling berhak dipindahkan ke formasi kosong. Tahap 2 memenuhi kebutuhan personal pegawai untuk menyimulasikan kantor mana yang paling efisien dari domisilinya tanpa melanggar kuota formasi. Kombinasi ini menjamin transparansi dari sisi pemerintah daerah sekaligus keadilan bagi pegawai.

**Q2: Mengapa Masa Kerja tidak dimasukkan ke dalam matriks TOPSIS saat merangking unit kerja untuk 1 orang ASN?**
> *Jawaban:*  
> Karena yang dinilai adalah satu individu ASN terhadap beberapa unit kerja pilihan. Nilai masa kerja ASN tersebut adalah konstan di semua alternatif kantor. Secara matematis dalam TOPSIS, kriteria yang bernilai identik akan menghasilkan nilai solusi ideal positif dan negatif yang sama ($A^+ = A^-$), sehingga jarak separasinya nol dan tidak memberikan diferensiasi bobot. Oleh karena itu, masa kerja ditempatkan sebagai *Eligibility Filter* (syarat minimal pengajuan mutasi sesuai regulasi BKN).

**Q3: Mengapa tidak menggunakan metode AHP untuk penentuan bobot kriteria?**
> *Jawaban:*  
> Berdasarkan batasan penelitian dan efisiensi birokrasi, penentuan bobot kriteria pada sistem ini merujuk langsung pada regulasi/kebijakan resmi kepegawaian (BKN & PermenPAN-RB) dengan fitur *Dynamic Weighting*. Sistem menyediakan validasi akumulasi bobot tepat 100% dan verifikasi konsistensi tanpa harus membebani tim verifikator dengan kuesioner perbandingan berpasangan (*pairwise comparison matrix*) AHP yang kompleks.

---

## 7. Tracing Perhitungan Kode Langkah Demi Langkah (Bab 4 Pembahasan)

Bagian ini mendokumentasikan bagaimana kode pada `TopsisService.php` memproses data input dari matriks mentah hingga menghasilkan ranking akhir secara deterministik.

### Contoh Studi Kasus Numerik (3 Alternatif, 3 Kriteria)

#### A. Data Alternatif Keputusan ($X$)
Misalkan terdapat 3 kandidat ASN untuk mengisi formasi kosong di **Dinas X**:
* $A_1$: Budi (Jarak: $4.0\text{ km}$, Masa Kerja: $8\text{ thn}$, Golongan: $3$)
* $A_2$: Siti (Jarak: $12.0\text{ km}$, Masa Kerja: $12\text{ thn}$, Golongan: $4$)
* $A_3$: Joko (Jarak: $2.0\text{ km}$, Masa Kerja: $4\text{ thn}$, Golongan: $2$)

Bobot dan atribut kriteria:
* $C_1$: Jarak ($Cost$, bobot $w_1 = 0.40$)
* $C_2$: Masa Kerja ($Benefit$, bobot $w_2 = 0.35$)
* $C_3$: Pangkat Golongan ($Benefit$, bobot $w_3 = 0.25$)

Matriks Keputusan:
$$X = \begin{pmatrix}
4.0 & 8 & 3 \\
12.0 & 12 & 4 \\
2.0 & 4 & 2
\end{pmatrix}$$

---

### Langkah 1 di Kode: Menghitung Pembagi Normalisasi Euclidean
Kode PHP:
```php
$sumSquares = 0.0;
foreach ($alternatives as $alt) {
    $val = (float)($alt[$code] ?? 0.0);
    $sumSquares += ($val * $val);
}
$divisors[$code] = ($sumSquares > 0) ? sqrt($sumSquares) : 1.0;
```
Perhitungan Manual:
* $|X_1| = \sqrt{4.0^2 + 12.0^2 + 2.0^2} = \sqrt{16 + 144 + 4} = \sqrt{164} \approx 12.806248$
* $|X_2| = \sqrt{8^2 + 12^2 + 4^2} = \sqrt{64 + 144 + 16} = \sqrt{224} \approx 14.966630$
* $|X_3| = \sqrt{3^2 + 4^2 + 2^2} = \sqrt{9 + 16 + 4} = \sqrt{29} \approx 5.385165$

---

### Langkah 2 di Kode: Normalisasi ($R$) dan Terbobot ($Y$)
Kode PHP:
```php
$rVal = $rawVal / $divisors[$code];
$yVal = $rVal * (float)$crit['bobot'];
```
Perhitungan Manual:
* **Matriks Ternormalisasi $R$:**
  $$R = \begin{pmatrix}
  4.0 / 12.8062 = 0.3123 & 8 / 14.9666 = 0.5345 & 3 / 5.3852 = 0.5571 \\
  12.0 / 12.8062 = 0.9370 & 12 / 14.9666 = 0.8018 & 4 / 5.3852 = 0.7428 \\
  2.0 / 12.8062 = 0.1562 & 4 / 14.9666 = 0.2673 & 2 / 5.3852 = 0.3714
  \end{pmatrix}$$
* **Matriks Terbobot $Y$ ($y_{ij} = r_{ij} \times w_j$):**
  $$Y = \begin{pmatrix}
  0.3123 \times 0.40 = 0.1249 & 0.5345 \times 0.35 = 0.1871 & 0.5571 \times 0.25 = 0.1393 \\
  0.9370 \times 0.40 = 0.3748 & 0.8018 \times 0.35 = 0.2806 & 0.7428 \times 0.25 = 0.1857 \\
  0.1562 \times 0.40 = 0.0625 & 0.2673 \times 0.35 = 0.0935 & 0.3714 \times 0.25 = 0.0928
  \end{pmatrix}$$

---

### Langkah 3 di Kode: Menentukan Solusi Ideal $A^+$ dan $A^-$
Kode PHP:
```php
if ($isCost) {
    $idealPositive[$code] = min($columnY);
    $idealNegative[$code] = max($columnY);
} else {
    $idealPositive[$code] = max($columnY);
    $idealNegative[$code] = min($columnY);
}
```
Perhitungan Manual:
* $C_1$ ($Cost$): $y_1^+ = \min(0.1249, 0.3748, 0.0625) = \mathbf{0.0625}$, $y_1^- = \max(...) = \mathbf{0.3748}$
* $C_2$ ($Benefit$): $y_2^+ = \max(0.1871, 0.2806, 0.0935) = \mathbf{0.2806}$, $y_2^- = \min(...) = \mathbf{0.0935}$
* $C_3$ ($Benefit$): $y_3^+ = \max(0.1393, 0.1857, 0.0928) = \mathbf{0.1857}$, $y_3^- = \min(...) = \mathbf{0.0928}$
* Maka:
  $$A^+ = [0.0625, 0.2806, 0.1857]$$
  $$A^- = [0.3748, 0.0935, 0.0928]$$

---

### Langkah 4 di Kode: Jarak Separasi ($D^+$ dan $D^-$)
Kode PHP:
```php
$distPositiveSq += pow($yVal - $idealPositive[$code], 2);
$distNegativeSq += pow($yVal - $idealNegative[$code], 2);
$dPositive = sqrt($distPositiveSq);
$dNegative = sqrt($distNegativeSq);
```
Perhitungan Manual:
* **Untuk Budi ($A_1$):**
  $$D_1^+ = \sqrt{(0.1249 - 0.0625)^2 + (0.1871 - 0.2806)^2 + (0.1393 - 0.1857)^2} \approx 0.1221$$
  $$D_1^- = \sqrt{(0.1249 - 0.3748)^2 + (0.1871 - 0.0935)^2 + (0.1393 - 0.0928)^2} \approx 0.2709$$
* **Untuk Siti ($A_2$):**
  $$D_2^+ = \sqrt{(0.3748 - 0.0625)^2 + (0.2806 - 0.2806)^2 + (0.1857 - 0.1857)^2} \approx 0.3123$$
  $$D_2^- = \sqrt{(0.3748 - 0.3748)^2 + (0.2806 - 0.0935)^2 + (0.1857 - 0.0928)^2} \approx 0.2091$$
* **Untuk Joko ($A_3$):**
  $$D_3^+ = \sqrt{(0.0625 - 0.0625)^2 + (0.0935 - 0.2806)^2 + (0.0928 - 0.1857)^2} \approx 0.2091$$
  $$D_3^- = \sqrt{(0.0625 - 0.3748)^2 + (0.0935 - 0.0935)^2 + (0.0928 - 0.0928)^2} \approx 0.3123$$

---

### Langkah 5 & 6 di Kode: Nilai Preferensi ($V_i$) & Perangkingan
Kode PHP:
```php
$vi = ($denominator > 0) ? ($dNegative / $denominator) : 0.0;
usort($results, fn($a, $b) => $b['v_value'] <=> $a['v_value']);
```
Perhitungan Manual:
* $V_1 \text{ (Budi)} = \frac{0.2709}{0.1221 + 0.2709} = \frac{0.2709}{0.3930} \approx \mathbf{0.6893}$
* $V_2 \text{ (Siti)} = \frac{0.2091}{0.3123 + 0.2091} = \frac{0.2091}{0.5214} \approx \mathbf{0.4010}$
* $V_3 \text{ (Joko)} = \frac{0.3123}{0.2091 + 0.3123} = \frac{0.3123}{0.5214} \approx \mathbf{0.5990}$

#### Tabel Hasil Akhir Komputasi (Tampilan Sistem & Bab 4):
| Peringkat | Kandidat ASN | $D^+$ | $D^-$ | Nilai Preferensi ($V_i$) | Rekomendasi Sistem |
| :---: | :---: | :---: | :---: | :---: | :--- |
| **Rank 1** | **Budi ($A_1$)** | $0.1221$ | $0.2709$ | **$0.6893$** | **Prioritas Utama Mutasi** |
| **Rank 2** | Joko ($A_3$) | $0.2091$ | $0.3123$ | $0.5990$ | Cadangan Prioritas 1 |
| **Rank 3** | Siti ($A_2$) | $0.3123$ | $0.2091$ | $0.4010$ | Cadangan Prioritas 2 |

*Kesimpulan Analisis:*  
Meskipun Joko memiliki jarak yang paling dekat ($2\text{ km}$), Budi menjadi **Rank 1** karena memiliki kombinasi seimbang antara jarak yang masih tergolong dekat ($4\text{ km}$) serta masa kerja dan golongan yang jauh lebih tinggi dibanding Joko. Sistem TOPSIS membuktikan kemampuannya menyeimbangkan kriteria biaya (*cost*) dan keuntungan (*benefit*) secara optimal.


