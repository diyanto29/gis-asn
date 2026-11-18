# DOKUMENTASI PENGUJIAN SISTEM & TEST CASE NEGATIF (BAB 4 SKRIPSI)
> Sistem Pendukung Keputusan Pemetaan Spasial & Rekomendasi Mutasi ASN (SIMUTASI-GIS)  
> Metode Pengujian: **Automated Feature Testing & Black-Box Testing (Boundary & Negative Cases)**

Dokumen ini disusun untuk memenuhi kebutuhan **Bab 4 (Hasil dan Pembahasan / Pengujian Sistem)** pada naskah skripsi, membuktikan keandalan sistem dalam menangani masukan tidak valid, pelanggaran aturan kepegawaian, dan upaya eskalasi hak akses (*privilege escalation*).

---

## 1. Ringkasan Eksekusi Pengujian Otomatis (PHPUnit Test Suite)

Pengujian dijalankan pada lingkungan server lokal MariaDB dengan hasil:
* **Total Test Cases:** 12 Pengujian (4 Positif / Happy Path + 8 Kasus Negatif Ketat)
* **Total Assertions:** 75 Pengecekan Asersi
* **Tingkat Keberhasilan:** **100% Berhasil (*Passed / Zero Errors*)**
* **Waktu Eksekusi:** $\pm 748\text{ ms}$

```
Tests:    12 passed (75 assertions)
Duration: 0.75s
Status:   SUCCESS (Production-Ready & Thesis-Ready)
```

---

## 2. Matriks Pengujian Kasus Negatif (*Negative Test Cases Matrix*)

Berikut adalah 8 skenario pengujian negatif yang telah diotomatisasi dalam berkas `tests/Feature/NegativeValidationTest.php`:

| ID Uji | Modul / Fitur | Skenario Kasus Negatif | Input Data Uji | Respon yang Diharapkan (*Expected*) | Hasil Pengujian (*Actual*) | Kesimpulan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-NEG-01** | **Autentikasi Mandiri** | Login dengan NIP yang tidak terdaftar di sistem. | `login: "198507122010019999"`, `pass: "wrongpass"` | Sistem menolak login, memunculkan pesan error *"Kredensial yang diberikan tidak cocok"*, sesi tetap *guest*. | Error sesi terdeteksi, diarahkan kembali ke login. | **VALID (Lolos)** |
| **TC-NEG-02** | **Proteksi Tamu (Guest)** | Pengguna yang belum login mencoba mengakses langsung endpoint internal. | `GET /dashboard`, `GET /simulasi-mandiri`, `GET /asn` | Sistem mencegat permintaan (*middleware auth*) dan me-redirect paksa ke `/login`. | HTTP 302 Redirect ke `/login`. | **VALID (Lolos)** |
| **TC-NEG-03** | **RBAC Guard (Hak Akses)** | Pengguna ber-peran `asn` mencoba mengakses menu konfigurasi Baperjakat/Admin. | `GET /topsis`, `GET /master-kriteria`, `GET /formasi-jabatan` | Sistem memblokir akses secara mutlak dengan kode status keamanan **HTTP 403 Forbidden**. | HTTP 403 Forbidden pada seluruh rute admin. | **VALID (Lolos)** |
| **TC-NEG-04** | **Integritas Domisili Spasial** | Pegawai ASN mencoba mengubah titik koordinat tempat tinggal pegawai lain (*ID Hijacking*). | User ASN A mengakses `GET /asn/{id_B}/edit-lokasi` | Sistem mendeteksi ketidaksesuaian ID/NIP dengan akun login dan melempar respon **HTTP 403 Forbidden**. | Akses ditolak HTTP 403 dengan notifikasi otorisasi. | **VALID (Lolos)** |
| **TC-NEG-05** | **Regulasi Zero-ABK Mutasi** | Pegawai mengajukan mutasi ke unit kerja yang formasi kosongnya sudah habis ($\text{kuota} \le 0$). | `POST /simulasi-mandiri/ajukan` dengan `unit_tujuan: [Unit Berkuota 0]` | Sistem menolak permohonan (*Flash Error*: Unit tidak memiliki kuota lowong / Zero-ABK) untuk mencegah penumpukan pegawai. | Permohonan digagalkan, record tidak tersimpan di Baperjakat. | **VALID (Lolos)** |
| **TC-NEG-06** | **Integritas SPK TOPSIS** | Admin mencoba menyimpan pembobotan kriteria yang totalnya tidak sama dengan $100\%$ ($\ne 1.00$). | `POST /master-kriteria/bobot` dengan total bobot $1.20$ ($120\%$) | Sistem menggagalkan penyimpanan (*Flash Error*: Total bobot wajib tepat 100% sesuai standar Baperjakat dan skripsi). | Database tidak berubah, sistem mewajibkan koreksi bobot. | **VALID (Lolos)** |
| **TC-NEG-07** | **Integritas Relasional Unit** | Admin mencoba menghapus (*destroy*) Unit Kerja yang masih menaungi pegawai aktif. | `DELETE /unit-kerja/{id}` pada kantor dengan `pegawai_count > 0` | Sistem menggagalkan penghapusan demi menjaga integritas data relasional ASN aktif. | Ditolak dengan pesan *"Unit tidak dapat dihapus karena masih menaungi pegawai aktif"*. | **VALID (Lolos)** |
| **TC-NEG-08A** | **Validasi Unik NIP ASN** | Admin mendaftarkan pegawai baru menggunakan NIP 18 digit yang sudah terdaftar sebelumnya. | `POST /asn` dengan NIP eksisting | Form Request Validation menolak dengan pesan error *“NIP sudah digunakan”*. | Error validasi `nip` terdeteksi, insert dibatalkan. | **VALID (Lolos)** |
| **TC-NEG-08B** | **Validasi Spasial Koordinat** | Admin memasukkan koordinat latitude di luar jangkauan bola bumi (misal $\text{Lat} > 90^\circ$). | `POST /asn` dengan `domisili_lat: 195.4321` | Form validation Laravel mendeteksi pelanggaran aturan `between:-90,90` dan membatalkan simpan. | Error validasi `domisili_lat` terdeteksi. | **VALID (Lolos)** |

---

## 3. Matriks Pengujian Kasus Positif (*Positive Test Cases Matrix*)

Berikut adalah 4 skenario alur normal (*happy path*) yang diuji pada `tests/Feature/EndToEndUserJourneyTest.php`:

| ID Uji | Aktor | Skenario Alur Pengujian | Respon yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- |
| **TC-POS-01** | **Pegawai ASN** | Login via 18 Digit NIP $\rightarrow$ Sistem otomatis mengarahkan ke Simulasi Mandiri $\rightarrow$ Pembaruan titik domisili via peta. | Login berhasil, redirect ke `/simulasi-mandiri`, update koordinat berhasil. | **PASSED** |
| **TC-POS-02** | **Pegawai ASN** | Simulasi Mandiri (TOPSIS Tahap 2) $\rightarrow$ Pengukuran rute riil OSRM $\rightarrow$ Generate alasan mutasi dengan AI $\rightarrow$ Pengajuan permohonan ke database Baperjakat. | Alternatif terurut $V_i$, rute OSRM terlukis, alasan AI terisi, permohonan berstatus `diajukan_baperjakat`. | **PASSED** |
| **TC-POS-03** | **Admin BKPSDM** | Kelola Master Data (ASN, Unit Kerja, Formasi Jabatan Tracing 2025/2026, Wilayah 31 Kec, Kriteria TOPSIS) $\rightarrow$ Eksekusi SPK TOPSIS Tahap 1. | Seluruh form CRUD berfungsi normal, matriks keputusan Euclidean $R$ dan $Y$ terhitung presisi. | **PASSED** |
| **TC-POS-04** | **Admin / Baperjakat** | Membuka daftar usulan Baperjakat $\rightarrow$ Menyetujui usulan & Menerbitkan SK Mutasi $\rightarrow$ Cetak Berita Acara Sidang. | Status usulan berubah menjadi `disetujui`, unit ASN berpindah, jarak dinas baru terhitung ulang, format kop surat resmi tercetak rapi. | **PASSED** |

---

## 4. Relevansi Akademik untuk Sidang Skripsi

1. **Pencegahan Human Error & Kecurangan Sistem:**  
   Pengujian membuktikan bahwa sistem tidak dapat dimanipulasi dengan memasukkan bobot TOPSIS sesuka hati atau memindahkan pegawai ke unit yang sudah kelebihan pegawai (*overstaffed*).
2. **Kepatuhan Terhadap Standar ISO/IEC 25010:**  
   * **Functional Suitability:** Fitur berfungsi sesuai rancangan PRD dan aturan BKN.
   * **Security:** Perlindungan terhadap URL traversal, bypass middleware, dan duplikasi identitas kepegawaian.
   * **Reliability:** Sistem tidak pernah *crash* (500 Internal Server Error) saat menerima data ekstrem atau tidak valid, melainkan memberikan penanganan kesalahan (*graceful error handling*) yang informatif bagi pengguna.

