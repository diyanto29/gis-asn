# DOKUMEN PERANCANGAN SISTEM (BAB 3 SKRIPSI)
## UML, Flowchart, dan Entity-Relationship Diagram (ERD) Sistem Independen SIMUTASI-GIS

> **Peruntukan Dokumen:** Bab 3 Metodologi & Perancangan Sistem pada Naskah Skripsi.  
> **Karakteristik Sistem:** Sistem Independen (*Standalone*) dengan Autentikasi Mandiri (Role-Based Access Control: Admin BKPSDM vs Pegawai ASN), Mesin Komputasi Spasial OSRM/Haversine, dan SPK TOPSIS 2-Tahap.

---

## 1. Flowchart Sistem

### 1.1 Flowchart Autentikasi Mandiri & Kontrol Akses (RBAC)

```mermaid
flowchart TD
    Start(["Mulai"]) --> InputLogin[/"Pengguna menginput NIP/Email dan Password"/]
    InputLogin --> CekTipe{"Format input berupa Email?"}
    
    CekTipe -- "Ya" --> QueryEmail["Cari user berdasarkan Email"]
    CekTipe -- "Tidak" --> QueryNIP["Cari user berdasarkan 18 Digit NIP"]
    
    QueryEmail --> VerifPass{"Verifikasi Hash Password (Bcrypt)"}
    QueryNIP --> VerifPass
    
    VerifPass -- "Gagal" --> NotifGagal[/"Tampilkan pesan error: Kredensial tidak valid"/]
    NotifGagal --> InputLogin
    
    VerifPass -- "Berhasil" --> SetSession["Inisialisasi Sesi Login & Session Regenerate"]
    SetSession --> CekRole{"Cek Nilai Kolom Role"}
    
    CekRole -- "admin" --> DashAdmin["Buka Dashboard Admin BKPSDM<br/>(Akses Penuh: Kelola Kriteria, Unit, & Eksekusi Mutasi)"]
    CekRole -- "asn" --> DashASN["Buka Dashboard Pegawai ASN<br/>(Akses Terbatas: Peta, Profil, & Simulasi Rekomendasi)"]
    
    DashAdmin --> SelesaiAuth(["Selesai"])
    DashASN --> SelesaiAuth
```

---

### 1.2 Flowchart Komputasi SPK TOPSIS 2-Tahap

```mermaid
flowchart TD
    StartTOPSIS(["Mulai Proses Rekomendasi"]) --> PilihTahap{"Pilih Mode Evaluasi"}
    
    PilihTahap -- "Tahap 1: Top-Down (Admin)" --> InputUnit[/"Pilih Unit Kerja Tujuan (Kuota > 0)"/]
    InputUnit --> FilterKandidat["Ambil data ASN yang bukan dari unit tujuan & punya koordinat"]
    FilterKandidat --> HitungJarak1["Hitung jarak rute OSRM domisili ke kantor tujuan"]
    HitungJarak1 --> BentukMatriks1["Bentuk Matriks Keputusan X (Jarak, Masa Jabatan, Masa Kerja, Beban Asal)"]
    
    PilihTahap -- "Tahap 2: Bottom-Up (Mandiri ASN)" --> FilterSyarat{"Cek Syarat Kelayakan (Masa Kerja >= 5/10 thn)?"}
    FilterSyarat -- "Tidak Memenuhi" --> TolakSimulasi[/"Tampilkan peringatan: Belum memenuhi masa kerja minimal"/]
    FilterSyarat -- "Memenuhi" --> AmbilUnitBuka["Ambil seluruh Unit Kerja yang memiliki sisa kuota formasi"]
    AmbilUnitBuka --> HitungJarak2["Hitung jarak rute OSRM dari rumah ASN ke setiap unit alternatif"]
    HitungJarak2 --> BentukMatriks2["Bentuk Matriks Keputusan X (Jarak OSRM, Kuota Formasi, Kesesuaian Jabatan, Hemat Jarak)"]
    
    BentukMatriks1 --> Normalisasi["Langkah 1: Normalisasi Euclidean Matriks R"]
    BentukMatriks2 --> Normalisasi
    
    Normalisasi --> Terbobot["Langkah 2: Matriks Terbobot Y = R * W (Total Bobot W = 1.00)"]
    Terbobot --> SolusiIdeal["Langkah 3: Tentukan Solusi Ideal Positif (A+) & Negatif (A-)"]
    SolusiIdeal --> Separasi["Langkah 4: Hitung Jarak Separasi Euclidean (D+ dan D-)"]
    Separasi --> NilaiVi["Langkah 5: Hitung Nilai Preferensi Relatif Vi = D- / (D+ + D-)"]
    NilaiVi --> SortingRank["Langkah 6: Urutkan Nilai Vi secara Descending (Rank 1..m)"]
    
    SortingRank --> SimpanRiwayat["Tampilkan Tabel Rekomendasi & Visualisasi Rute Peta GIS"]
    SimpanRiwayat --> SelesaiTOPSIS(["Selesai"])
    TolakSimulasi --> SelesaiTOPSIS
```

---

### 1.3 Flowchart Alur Asisten AI Context-Aware Smart Reason Generator

```mermaid
flowchart TD
    StartAI(["ASN Membuka Modal Permohonan Mutasi"]) --> AmbilKonteks["Sistem Membaca Otomatis Konteks Data:<br/>- Identitas: Nama, NIP, Jabatan<br/>- Kantor Asal & Jarak Harian Lama (km)<br/>- Kantor Rekomendasi & Jarak Rute Baru (km)<br/>- Selisih Efisiensi Jarak (km)<br/>- Sisa Formasi Kosong ABK"]
    
    AmbilKonteks --> TampilkanModal[/"Tampilkan Form Usulan & Pilihan Preset Narasi AI"/]
    TampilkanModal --> PilihPreset{"Pengguna Memilih Gaya Narasi AI"}
    
    PilihPreset -- "Gaya 1" --> GenJarak["Generate Narasi Efisiensi Jarak:<br/>Fokus pada penghematan waktu & energi fisik"]
    PilihPreset -- "Gaya 2" --> GenKeluarga["Generate Narasi Domisili Keluarga:<br/>Fokus pada stabilitas sosial & kedekatan tempat tinggal"]
    PilihPreset -- "Gaya 3" --> GenABK["Generate Narasi Formasi ABK:<br/>Fokus pada pemenuhan kebutuhan SDM instansi tujuan"]
    
    GenJarak --> InjectTeks["Injeksi Teks Formal ke Textarea Alasan Mutasi"]
    GenKeluarga --> InjectTeks
    GenABK --> InjectTeks
    
    InjectTeks --> EditManual{"Pengguna Ingin Mengubah/Menambah Teks?"}
    EditManual -- "Ya" --> ModifTeks[/"ASN Menyesuaikan Kalimat Tambahan"/]
    EditManual -- "Tidak" --> KlikKirim[/"Klik Tombol 'Kirim ke Baperjakat'"/]
    ModifTeks --> KlikKirim
    
    KlikKirim --> ValidasiAlasan{"Alasan Tidak Boleh Kosong?"}
    ValidasiAlasan -- "Kosong" --> NotifIsi[/"Tampilkan Peringatan: Wajib Mengisi Alasan"/]
    NotifIsi --> InjectTeks
    
    ValidasiAlasan -- "Valid" --> SimpanUsulan["Simpan Rekomendasi Mutasi ke Database:<br/>Status: 'diajukan_baperjakat'"]
    SimpanUsulan --> SelesaiAI(["Selesai: Usulan Siap Disidangkan"])
```

---

## 2. Diagram UML (Unified Modeling Language)

### 2.1 Use Case Diagram (Sistem Independen SIMUTASI-GIS)

```mermaid
flowchart LR
    subgraph Aktor
        Admin["Administrator BKPSDM"]
        Pegawai["Pegawai ASN"]
    end

    subgraph "Sistem SIMUTASI-GIS"
        UC1(["Login Mandiri (NIP/Email)"])
        UC2(["Melihat Dashboard Peta Spasial GIS"])
        UC3(["Melihat Profil Pegawai & Domisili"])
        UC4(["Memperbarui Titik Koordinat Domisili"])
        UC5(["Melakukan Simulasi Rekomendasi Unit Terdekat"])
        UC6(["Mengajukan Permohonan Mutasi Mandiri"])
        UC6a(["Generate Alasan Mutasi Formal Berbasis AI"])
        UC7(["Melihat Rekomendasi Smart Swap Match (Zero-ABK)"])
        UC8(["Mengelola Master Unit Kerja & Kuota Formasi"])
        UC9(["Mengatur Kriteria & Bobot TOPSIS (Dynamic Weighting)"])
        UC10(["Menjalankan Eksekusi SPK TOPSIS (Tahap 1)"])
        UC11(["Mencetak Laporan Rekomendasi Baperjakat"])
        UC12(["Logout Sesi"])
        UC13(["Mengelola Master Pegawai ASN (CRUD & Akun Login)"])
        UC14(["Mengelola Master Wilayah & Lokasi Spasial (CRUD 31 Kec)"])
        UC15(["Mengelola Master Formasi Jabatan & Tracing ABK"])
        UC16(["Menyetujui & Mengeksekusi SK Mutasi ASN (Closed-Loop)"])
        UC17(["Mengajukan Paket Tukar Guling (Swap Match) ke Baperjakat"])
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC7
    Admin --> UC8
    Admin --> UC9
    Admin --> UC10
    Admin --> UC11
    Admin --> UC12
    Admin --> UC13
    Admin --> UC14
    Admin --> UC15
    Admin --> UC16
    Admin --> UC17

    Pegawai --> UC1
    Pegawai --> UC2
    Pegawai --> UC3
    Pegawai --> UC4
    Pegawai --> UC5
    Pegawai --> UC6
    Pegawai --> UC7
    Pegawai --> UC12
    Pegawai --> UC17

    UC6 -.->|<<include>>| UC6a
```

---

### 2.2 Sequence Diagram: Proses Eksekusi SPK Rekomendasi Mutasi

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin BKPSDM
    participant View as TopsisView (Blade UI)
    participant Ctrl as TopsisController
    participant OSRM as OSRM Routing Engine
    participant ModelASN as AsnMaster Model
    participant Svc as TopsisService
    participant DB as MariaDB / Database

    Admin->>View: Buka menu SPK TOPSIS & Pilih Unit Tujuan
    View->>Ctrl: POST /topsis/proses (unit_tujuan_id)
    activate Ctrl
    
    Ctrl->>ModelASN: Query kandidat ASN (bukan dari unit tujuan & ada koordinat)
    activate ModelASN
    ModelASN-->>Ctrl: Return Collection Data ASN
    deactivate ModelASN
    
    loop Setiap Kandidat ASN
        Ctrl->>OSRM: Request Jarak Rute (lng_domisili, lat_domisili -> lng_kantor, lat_kantor)
        OSRM-->>Ctrl: Return distance_km & duration_minutes
    end
    
    Ctrl->>DB: Query Kriteria Aktif & Bobot
    DB-->>Ctrl: Return Kriteria (C1..Cn, Bobot, Atribut)
    
    Ctrl->>Svc: calculate(alternatives, criteria)
    activate Svc
    Note over Svc: 1. Normalisasi Euclidean R<br/>2. Matriks Terbobot Y<br/>3. Solusi Ideal A+ & A-<br/>4. Jarak Separasi D+ & D-<br/>5. Nilai Preferensi Vi
    Svc-->>Ctrl: Return Array Ranked [rank, Vi, D+, D-] & Details
    deactivate Svc
    
    Ctrl-->>View: Render topsis.hasil (Tabel Perangkingan & Peta Rute)
    deactivate Ctrl
    View-->>Admin: Menampilkan Rekomendasi Calon ASN Terbaik
```

---

### 2.3 Sequence Diagram: Simulasi Penempatan Mandiri & Pengajuan Mutasi Berbantuan AI

```mermaid
sequenceDiagram
    autonumber
    actor ASN as Pegawai ASN
    participant View as SimulasiMandiriView (Blade UI)
    participant Ctrl as SimulasiMandiriController
    participant ModelUnit as UnitKerja Model
    participant OSRM as OSRM Routing Engine
    participant Svc as TopsisService
    participant AI as AI Reason Engine (Client JS)
    participant ModelRek as RekomendasiMutasi Model

    ASN->>View: Buka Halaman /simulasi-mandiri
    View->>Ctrl: GET /simulasi-mandiri
    activate Ctrl
    
    Ctrl->>ModelUnit: Query unit dengan kuota > 0
    ModelUnit-->>Ctrl: Return List Unit Kerja
    
    loop Setiap Unit Alternatif
        Ctrl->>OSRM: Request Jarak Rute (domisili -> kantor)
        OSRM-->>Ctrl: Return distance_km & duration
    end
    
    Ctrl->>Svc: calculate(alternatives, criteria [C1, C2, C3])
    Svc-->>Ctrl: Return Ranked Alternatives
    Ctrl-->>View: Render Hasil Simulasi, Peta Rute, & Ranking
    deactivate Ctrl

    ASN->>View: Klik "Ajukan Permohonan Mutasi" pada Unit #1
    View->>View: Tampilkan Modal Pengajuan Mutasi
    
    ASN->>AI: Klik Preset AI (Efisiensi Jarak / Domisili / ABK)
    activate AI
    AI->>AI: Baca konteks (Nama, NIP, Jarak Lama, Jarak Baru, Selisih, Kuota)
    AI-->>View: Injeksi narasi formal ke textarea alasan mutasi
    deactivate AI

    ASN->>View: Review & Klik "Kirim ke Baperjakat"
    View->>Ctrl: POST /simulasi-mandiri/ajukan (id_unit_tujuan, alasan_mutasi)
    activate Ctrl
    
    Ctrl->>ModelRek: create([id_asn, id_unit_tujuan, alasan, status: 'diajukan_baperjakat'])
    ModelRek-->>Ctrl: Usulan Tersimpan
    Ctrl-->>View: Redirect back with success flash message
    deactivate Ctrl
    View-->>ASN: Tampilkan Notifikasi Usulan Berhasil Dikirim
```

---

### 2.4 Class Diagram (Arsitektur MVC & Service Layer)

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string nip
        +string email
        +string password
        +string role
        +int asn_id
        +isAdmin() bool
        +isAsn() bool
        +asn() AsnMaster
    }

    class AsnMaster {
        +int id
        +string nip
        +string nama
        +string jabatan
        +string pangkat_golongan
        +int masa_kerja_tahun
        +int id_unit_saat_ini
        +float domisili_lat
        +float domisili_lng
        +float jarak_ke_kantor_km
        +string zona_jarak
        +string status_permohonan_mutasi
        +unitSaatIni() UnitKerja
        +user() User
    }

    class UnitKerja {
        +int id
        +string kode_unit
        +string nama_unit
        +string kategori
        +float kantor_lat
        +float kantor_lng
        +int kuota_formasi_kosong
        +int total_asn_saat_ini
        +pegawai() List~AsnMaster~
    }

    class TopsisKriteria {
        +int id
        +string kode_kriteria
        +string nama_kriteria
        +string parameter_key
        +string atribut
        +float bobot
        +bool is_active
        +string keterangan
    }

    class RekomendasiMutasi {
        +int id
        +int id_asn
        +int id_unit_asal
        +int id_unit_tujuan
        +float jarak_baru_km
        +float penghematan_jarak_km
        +float nilai_topsis
        +int peringkat
        +string status_usulan
        +string catatan_baperjakat
    }

    class TopsisService {
        +calculate(array alternatives, array criteria) array
    }

    class HaversineService {
        +const EARTH_RADIUS_KM = 6371.0
        +calculateDistance(float lat1, float lng1, float lat2, float lng2) float
        +classifyZone(float distanceKm) string
        +getZoneColor(string zone) string
    }

    class FormasiJabatan {
        +int id
        +int id_unit_kerja
        +int tahun_anggaran
        +string periode
        +string nama_jabatan
        +string jenjang_jabatan
        +string kualifikasi_pendidikan
        +int kebutuhan_abk
        +int bezetting_riil
        +int kuota_lowong
        +string status
        +string keterangan
    }

    class FormasiJabatanController {
        +index(Request request) View
        +create() View
        +store(Request request) RedirectResponse
        +edit(int id) View
        +update(Request request, int id) RedirectResponse
        +destroy(int id) RedirectResponse
    }

    class MasterLokasi {
        +int id
        +string kode_kecamatan
        +string nama_kecamatan
        +float pusat_lat
        +float pusat_lng
        +int jumlah_desa
        +string keterangan
    }

    class MasterLokasiController {
        +index(Request request) View
        +create() View
        +store(Request request) RedirectResponse
        +edit(int id) View
        +update(Request request, int id) RedirectResponse
        +destroy(int id) RedirectResponse
    }

    class KriteriaController {
        +index() View
        +store(Request request) RedirectResponse
        +toggle(int id) RedirectResponse
        +updateBobot(Request request) RedirectResponse
        +destroy(int id) RedirectResponse
    }

    class AsnController {
        +index(Request request) View
        +create() View
        +store(Request request) RedirectResponse
        +show(int id) View
        +edit(int id) View
        +update(Request request, int id) RedirectResponse
        +destroy(int id) RedirectResponse
        +editLokasi(int id) View
        +updateLokasi(Request request, int id) RedirectResponse
    }

    class UnitKerjaController {
        +index(Request request) View
        +create() View
        +store(Request request) RedirectResponse
        +edit(int id) View
        +update(Request request, int id) RedirectResponse
        +destroy(int id) RedirectResponse
    }

    class SimulasiMandiriController {
        +index(Request request) View
        +ajukan(Request request) RedirectResponse
    }

    class TopsisController {
        +index() View
        +proses(Request request) View
        +simpanUsulan(Request request) JsonResponse
    }

    User "1" --> "0..1" AsnMaster : memiliki profil
    AsnMaster "*" --> "1" UnitKerja : bertugas di
    RekomendasiMutasi "*" --> "1" AsnMaster : merekomendasikan
    RekomendasiMutasi "*" --> "1" UnitKerja : unit tujuan
    TopsisController ..> TopsisService : memanggil kalkulasi
    TopsisController ..> HaversineService : fallback jarak
    SimulasiMandiriController ..> TopsisService : memanggil kalkulasi
    SimulasiMandiriController ..> RekomendasiMutasi : menyimpan usulan
    AsnController ..> AsnMaster : mengelola data
    AsnController ..> User : auto-provisioning
    UnitKerjaController ..> UnitKerja : mengelola formasi
    MasterLokasiController ..> MasterLokasi : mengelola wilayah
    KriteriaController ..> TopsisKriteria : mengelola kriteria
    UnitKerja "1" --> "*" FormasiJabatan : memiliki alokasi jabatan
    FormasiJabatanController ..> FormasiJabatan : mengelola formasi & tracing
```

---

## 3. Entity-Relationship Diagram (ERD) Relasional

### 3.1 Diagram ERD

```mermaid
erDiagram
    USERS ||--o| ASN_MASTER : "memiliki profil (role=asn)"
    UNIT_KERJA ||--o{ ASN_MASTER : "menampung pegawai saat ini"
    ASN_MASTER ||--o{ REKOMENDASI_MUTASI : "menjadi subjek rekomendasi"
    UNIT_KERJA ||--o{ REKOMENDASI_MUTASI : "sebagai unit tujuan"
    UNIT_KERJA ||--o{ REKOMENDASI_MUTASI : "sebagai unit asal"
    UNIT_KERJA ||--o{ FORMASI_JABATAN : "membuka riwayat formasi tahunan"

    FORMASI_JABATAN {
        bigint id PK
        bigint id_unit_kerja FK "Indexed"
        int tahun_anggaran "Indexed (2025, 2026, ...)"
        string periode "Semester / Reguler"
        string nama_jabatan "Indexed"
        string jenjang_jabatan
        string kualifikasi_pendidikan
        int kebutuhan_abk
        int bezetting_riil
        int kuota_lowong "Sisa Lowong"
        string status "terbuka / terisi / ditutup"
        text keterangan
        timestamp created_at
        timestamp updated_at
    }

    MASTER_LOKASI {
        bigint id PK
        string kode_kecamatan UK "Kode Kemendagri"
        string nama_kecamatan "Nama 31 Kecamatan"
        decimal pusat_lat "Titik Pusat Latitude"
        decimal pusat_lng "Titik Pusat Longitude"
        int jumlah_desa "Total Desa/Kelurahan"
        text keterangan
        timestamp created_at
        timestamp updated_at
    }

    USERS {
        bigint id PK
        string name
        string nip UK "Indexed"
        string email UK
        string password "Bcrypt Hash"
        string role "admin / asn"
        bigint asn_id FK "Nullable"
        timestamp email_verified_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    ASN_MASTER {
        bigint id PK
        string nip UK "Indexed"
        string nama
        string gelar_depan
        string gelar_belakang
        string jabatan
        string pangkat_golongan
        int masa_kerja_tahun
        date tmt_kerja_unit
        bigint id_unit_saat_ini FK "Indexed"
        decimal domisili_lat
        decimal domisili_lng
        string provinsi
        string kabupaten_kota
        string kecamatan
        string kelurahan_desa
        string kode_desa "Indexed"
        text alamat_detail
        decimal jarak_ke_kantor_km "Indexed"
        string zona_jarak "dekat / sedang / jauh"
        string status_permohonan_mutasi
        text alasan_mutasi
        timestamp created_at
        timestamp updated_at
    }

    UNIT_KERJA {
        bigint id PK
        string kode_unit UK
        string nama_unit
        string kategori "Dinas / Badan / Kecamatan / Puskesmas"
        decimal kantor_lat
        decimal kantor_lng
        int kuota_formasi_kosong "ABK Available"
        int total_asn_saat_ini
        timestamp created_at
        timestamp updated_at
    }

    TOPSIS_KRITERIA {
        bigint id PK
        string kode_kriteria UK "C1, C2, C3, ..."
        string nama_kriteria
        string parameter_key "jarak, masa_kerja, golongan, dll"
        string atribut "cost / benefit"
        decimal bobot "Total harus 1.00"
        boolean is_active
        string keterangan
        timestamp created_at
        timestamp updated_at
    }

    REKOMENDASI_MUTASI {
        bigint id PK
        bigint id_asn FK "Indexed"
        bigint id_unit_asal FK
        bigint id_unit_tujuan FK "Indexed"
        decimal jarak_baru_km
        decimal penghematan_jarak_km
        decimal nilai_topsis "Preferensi Vi"
        int peringkat "Rank 1..m"
        string status_usulan "draft / disetujui / ditolak"
        text catatan_baperjakat
        timestamp created_at
        timestamp updated_at
    }
```

---

## 4. Kamus Data Database (Data Dictionary untuk Bab 3)

### Tabel `users` (Akun Pengguna Mandiri)
| Nama Kolom | Tipe Data | Kunci | Nullable | Deskripsi |
| :--- | :--- | :---: | :---: | :--- |
| `id` | BIGINT UNSIGNED | PK | No | Identifikator unik akun |
| `name` | VARCHAR(255) | - | No | Nama lengkap pengguna |
| `nip` | VARCHAR(25) | UK | Yes | Nomor Induk Pegawai 18 digit (kredensial login ASN) |
| `email` | VARCHAR(255) | UK | No | Alamat email resmi dinas |
| `password` | VARCHAR(255) | - | No | Hash kata sandi terenkripsi (Bcrypt) |
| `role` | VARCHAR(20) | - | No | Peran otorisasi: `admin` (BKPSDM) atau `asn` (Pegawai) |
| `asn_id` | BIGINT UNSIGNED | FK | Yes | Relasi ke baris tabel `asn_master.id` |

### Tabel `unit_kerja` (Master Kantor & Formasi ABK)
| Nama Kolom | Tipe Data | Kunci | Nullable | Deskripsi |
| :--- | :--- | :---: | :---: | :--- |
| `id` | BIGINT UNSIGNED | PK | No | Identifikator unik unit kerja |
| `kode_unit` | VARCHAR(50) | UK | No | Kode resmi SKPD/Instansi |
| `nama_unit` | VARCHAR(255) | - | No | Nama instansi kantor dinas/puskesmas/kecamatan |
| `kantor_lat` | DECIMAL(10,8) | - | No | Titik lintang geografis kantor |
| `kantor_lng` | DECIMAL(11,8) | - | No | Titik bujur geografis kantor |
| `kuota_formasi_kosong` | INT | - | No | Sisa kuota kebutuhan pegawai menurut ABK ($\le 0$ dilarang mutasi masuk) |

### Tabel `topsis_kriteria` (Master Parameter & Bobot Keputusan)
| Nama Kolom | Tipe Data | Kunci | Nullable | Deskripsi |
| :--- | :--- | :---: | :---: | :--- |
| `id` | BIGINT UNSIGNED | PK | No | Identifikator kriteria |
| `kode_kriteria` | VARCHAR(10) | UK | No | Label kode kriteria ($C_1, C_2, C_3, \dots$) |
| `nama_kriteria` | VARCHAR(100) | - | No | Nama deskripsi kriteria penilaian |
| `atribut` | ENUM | - | No | Sifat kriteria: `cost` (biaya/jarak) atau `benefit` (keuntungan) |
| `bobot` | DECIMAL(5,4) | - | No | Nilai bobot kepentingan kriteria ($\sum W = 1.0000$) |
| `is_active` | BOOLEAN | - | No | Status aktif kriteria dalam kalkulasi matriks |

### Tabel `master_lokasi` (Master Wilayah & Koordinat Pusat Kecamatan)
| Nama Kolom | Tipe Data | Kunci | Nullable | Deskripsi |
| :--- | :--- | :---: | :---: | :--- |
| `id` | BIGINT UNSIGNED | PK | No | Identifikator unik wilayah |
| `kode_kecamatan` | VARCHAR(20) | UK | No | Kode wilayah standar Kemendagri (e.g. 32.12.01) |
| `nama_kecamatan` | VARCHAR(100) | - | No | Nama 31 kecamatan di Kabupaten Indramayu |
| `pusat_lat` | DECIMAL(10,8) | - | No | Koordinat lintang titik pusat wilayah kecamatan |
| `pusat_lng` | DECIMAL(11,8) | - | No | Koordinat bujur titik pusat wilayah kecamatan |
| `jumlah_desa` | INT | - | No | Jumlah desa/kelurahan dalam lingkup kecamatan |
| `keterangan` | TEXT | - | Yes | Catatan karakteristik/geografis wilayah |

