---
name: academic-code-doc
description: Standar dokumentasi kode untuk keperluan penulisan skripsi, laporan akademik, dan publikasi ilmiah pada proyek SIMUTASI-GIS. Memastikan setiap algoritma (TOPSIS, OSRM, Spasial) memiliki PHPDoc, rumus matematis, dan penjelasan langkah demi langkah yang siap dikutip ke Bab 3 dan Bab 4 skripsi.
---

# Academic Code Documentation Standards (Skripsi-Ready)

Skill ini mewajibkan setiap kode, fungsi, service, dan algoritma yang dibuat atau dimodifikasi dalam sistem SIMUTASI-GIS didokumentasikan dengan standar akademik yang jelas, terstruktur, dan mudah dikutip ke dalam naskah skripsi (Bab 3 Metodologi & Bab 4 Implementasi/Pengujian).

## 1. Aturan Penulisan PHPDoc & Anotasi Matematis
Setiap method dalam Service Layer (terutama `TopsisService`, `OsrmService`, `HaversineService`) WAJIB memiliki docblock lengkap yang mencakup:
1. **Deskripsi Teoretis:** Tujuan fungsi dan kaitannya dengan metodologi penelitian.
2. **Formula Matematis:** Dituliskan rumusnya secara eksplisit (KaTeX/teks representatif), contoh:
   - Normalisasi: $r_{ij} = \frac{x_{ij}}{\sqrt{\sum_{i=1}^{m} x_{ij}^2}}$
   - Solusi Ideal: $A^+ = \{\max(y_{ij}) \mid j \in Benefit\}, \{\min(y_{ij}) \mid j \in Cost\}$
   - Jarak Separasi: $D_i^+ = \sqrt{\sum (y_{ij} - y_j^+)^2}$
   - Nilai Preferensi: $V_i = \frac{D_i^-}{D_i^+ + D_i^-}$
3. **Param & Return Types:** Tipe data, contoh struktur array input, dan rentang nilai output (misal: $V_i \in [0, 1]$).

## 2. Inline Step-by-Step Comments
Di dalam tubuh fungsi/algoritma, WAJIB membagi kode ke dalam blok langkah bernomor yang sinkron dengan tahapan pada Bab 3 skripsi:
- `// Langkah 1: Membentuk Matriks Keputusan (X)`
- `// Langkah 2: Menghitung Pembagi Normalisasi Euclidean`
- `// Langkah 3: Matriks Ternormalisasi Terbobot (Y = R * W)`
- `// Langkah 4: Menentukan Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-)`
- `// Langkah 5: Menghitung Jarak Separasi Solusi Ideal (D+ dan D-)`
- `// Langkah 6: Menghitung Nilai Preferensi Relatif (Vi) dan Pemeringkatan`

## 3. Penamaan Variabel Sinkron Notasi Ilmiah
Variabel kode tidak boleh berupa singkatan acak; gunakan simbol yang sinkron dengan jurnal/skripsi:
- `$matrixR` untuk Matriks Ternormalisasi $R$.
- `$matrixY` untuk Matriks Ternormalisasi Terbobot $Y$.
- `$idealPositive` / `$idealNegative` untuk Solusi Ideal $A^+$ dan $A^-$.
- `$distPositive` / `$distNegative` untuk Separasi $D_i^+$ dan $D_i^-$.
- `$vi` / `$preferenceValue` untuk Nilai Preferensi $V_i$.

## 4. OSRM vs Haversine
- Menggunakan rute fisik riil jalan raya (OSRM API / service).
- Dokumentasikan parameter OSRM: koordinat titik asal [lng, lat], titik tujuan [lng, lat], metrik jarak (meter -> km), dan durasi tempuh (detik -> menit).

