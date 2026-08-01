# Ide Modul Absensi — Makna Finance (`maknafinace.id`)

> Dokumen perencanaan + status implementasi (port dari pola Wofins).  
> Nama model & field memakai **bahasa Indonesia** (pola mirip `NotaDinas`, `DataPembayaran`).  
> Acuan: `User`, `Employee`, cuti (`LeaveRequest`), Filament admin.  
> **Catatan:** API mobile / iOS absensi **hanya di Wofins** untuk saat ini.  
> Update: 27 Jul 2026

---

## 1. Tujuan

Sistem absensi karyawan yang:

- Terintegrasi dengan SDM (`User` ↔ `Employee`)
- Dipakai admin (Filament) dan app iOS (masuk / pulang)
- Selaras dengan cuti (hari cuti disetujui ≠ alfa)
- Siap rekap harian/bulanan (nanti ke payroll)
- **Wajib foto kamera** saat absen masuk/pulang (bukti kehadiran)
- **Wajib lokasi + peta**: absen hanya diizinkan jika berada dekat kantor (geofence); absen dari jauh **ditolak**

---

## 2. Model yang sudah ada (jangan dibuat ulang)

| Model | Peran |
|-------|--------|
| `User` | Login; pemilik absensi (`user_id`) |
| `Employee` | Profil karyawan via `user_id` |
| `LeaveRequest` | Cuti disetujui → status absensi = `cuti` |
| `LeaveType` / `LeaveBalance` | Konteks jenis cuti di rekap |
| `Company` | Opsional untuk default perusahaan |

**Keputusan:** absensi memakai `user_id` (sama seperti cuti).

---

## 3. Daftar model baru (bahasa Indonesia)

| # | Model (class) | Tabel | Fase |
|---|---------------|-------|------|
| 1 | `PengaturanAbsensi` | `pengaturan_absensis` | MVP |
| 2 | `LokasiAbsensi` | `lokasi_absensis` | MVP (titik kantor + radius peta) |
| 3 | `Absensi` | `absensis` | MVP |
| 4 | `LogAbsensi` | `log_absensis` | MVP (foto + GPS) |
| 5 | `HariLibur` | `hari_liburs` | Fase 2 |
| 6 | `JadwalKerja` | `jadwal_kerjas` | Fase 2 |
| 7 | `HariJadwalKerja` | `hari_jadwal_kerjas` | Fase 2 |
| 8 | `PenugasanJadwal` | `penugasan_jadwals` | Fase 2 |
| 9 | `KoreksiAbsensi` | `koreksi_absensis` | Opsional |
| 10 | `PengajuanLembur` | `pengajuan_lemburs` | Opsional |

---

## 4. Isi masing-masing model

### 4.1 `PengaturanAbsensi` — aturan absen global

**Fungsi:** satu (atau beberapa) aturan jam kerja, toleransi, GPS, foto.  
**Jangan hardcode** jam kerja di controller.

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `nama` | string | Mis. “Aturan Kantor Default” |
| `jam_masuk` | time | Mis. `09:00:00` |
| `jam_pulang` | time | Mis. `18:00:00` |
| `toleransi_terlambat_menit` | int | Mis. `15` |
| `toleransi_pulang_cepat_menit` | int | Mis. `10` |
| `wajib_pulang` | bool | Wajib absen pulang |
| `wajib_lokasi` | bool | **Default `true`** — wajib GPS |
| `wajib_foto` | bool | **Default `true`** — wajib kamera/selfie |
| `tolak_jika_di_luar_radius` | bool | **Default `true`** — absen jauh ditolak (bukan hanya peringatan) |
| `akurasi_gps_maksimal_meter` | int\|null | Tolak jika akurasi GPS terlalu kasar (mis. > 100 m) |
| `ukuran_foto_maks_kb` | int | Mis. `2048` (2 MB) |
| `zona_waktu` | string | Default `Asia/Jakarta` |
| `aktif` | bool | Hanya satu yang aktif biasanya |
| `catatan` | text\|null | |
| `created_at` / `updated_at` | timestamp | |

**Relasi:** dibaca service saat masuk/pulang; titik kantor diambil dari `LokasiAbsensi` yang aktif.

---

### 4.2 `Absensi` — rekap 1 orang / 1 hari

**Fungsi:** ringkasan kehadiran harian (untuk laporan, dashboard, “status hari ini”).  
**Unique:** (`user_id`, `tanggal`).

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `user_id` | FK → users | Karyawan |
| `tanggal` | date | Tanggal kerja |
| `status` | string | Lihat enum di §5 |
| `jam_masuk` | datetime\|null | Waktu masuk (dari log pertama) |
| `jam_pulang` | datetime\|null | Waktu pulang (dari log terakhir) |
| `menit_kerja` | int\|null | Durasi kerja |
| `menit_terlambat` | int | Default `0` |
| `menit_pulang_cepat` | int | Default `0` |
| `sumber` | string\|null | `mobile`, `web`, `admin` |
| `catatan` | text\|null | Catatan sistem / admin |
| `leave_request_id` | FK\|null → leave_requests | Jika status `cuti` |
| `disetujui_oleh` | FK\|null → users | Jika dikoreksi/diinput admin |
| `created_at` / `updated_at` | timestamp | |

**Relasi:**

- `belongsTo` User  
- `belongsTo` LeaveRequest (opsional)  
- `hasMany` LogAbsensi  
- `hasMany` KoreksiAbsensi (fase 3)

---

### 4.3 `LogAbsensi` — jejak setiap aksi absen

**Fungsi:** audit trail clock-in / clock-out (+ GPS, foto, device).

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `absensi_id` | FK → absensis | Rekap hari itu |
| `user_id` | FK → users | Denormalisasi (query cepat) |
| `jenis` | string | `masuk` \| `pulang` (nanti: `mulai_istirahat`, `selesai_istirahat`) |
| `waktu` | datetime | Waktu absen |
| `lokasi_absensi_id` | FK\|null → lokasi_absensis | Lokasi kantor yang dipakai validasi |
| `lintang` | decimal(10,7) | Latitude user (**wajib** jika `wajib_lokasi`) |
| `bujur` | decimal(10,7) | Longitude user (**wajib**) |
| `akurasi_meter` | float\|null | Akurasi GPS dari device |
| `jarak_ke_kantor_meter` | int\|null | Hasil hitung Haversine (disimpan untuk audit) |
| `dalam_radius` | bool | `true` jika jarak ≤ radius lokasi |
| `path_foto` | string | Path storage foto kamera (**wajib** jika `wajib_foto`) |
| `nama_perangkat` | string\|null | Mis. `ios-wofins` |
| `alamat_ip` | string\|null | |
| `meta` | json\|null | Versi app, orientasi kamera, dll. |
| `valid` | bool | Default `true`; admin bisa tandai invalid |
| `alasan_tolak` | string\|null | Mis. `di_luar_radius`, `tanpa_foto`, `gps_tidak_akurat` |
| `created_at` / `updated_at` | timestamp | |

**Relasi:**

- `belongsTo` Absensi  
- `belongsTo` User  
- `belongsTo` LokasiAbsensi (opsional)  

**Alur:**

1. App ambil GPS + buka kamera → user ambil foto  
2. App tampilkan peta (posisi user vs kantor + lingkaran radius)  
3. Jika di luar radius → tombol absen disabled / API menolak  
4. Jika lolos → buat/update `Absensi` + insert `LogAbsensi` (`masuk` / `pulang`) + upload foto  
5. Hitung `status`, `menit_terlambat`, `menit_kerja` dari `PengaturanAbsensi`

---

### 4.4 `HariLibur` — libur nasional / perusahaan

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `tanggal` | date | Unique |
| `nama` | string | Mis. “Hari Raya Idul Fitri” |
| `nasional` | bool | Libur nasional vs internal |
| `tetap_masuk` | bool | Jika `true`, tetap hari kerja |
| `catatan` | text\|null | |
| `created_at` / `updated_at` | timestamp | |

**Dipakai untuk:** status `libur`, skip alfa otomatis.

---

### 4.5 `JadwalKerja` — pola jam kerja (header)

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `nama` | string | Mis. “Kantor Reguler”, “Crew Event” |
| `kode` | string\|null | Mis. `REG`, `CREW` |
| `default` | bool | Jadwal default perusahaan |
| `deskripsi` | text\|null | |
| `aktif` | bool | |
| `created_at` / `updated_at` | timestamp | |

**Relasi:** `hasMany` HariJadwalKerja, `hasMany` PenugasanJadwal

---

### 4.6 `HariJadwalKerja` — detail Senin–Minggu

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `jadwal_kerja_id` | FK → jadwal_kerjas | |
| `hari` | tinyint | `0`=Minggu … `6`=Sabtu |
| `hari_kerja` | bool | Apakah masuk |
| `jam_masuk` | time\|null | |
| `jam_pulang` | time\|null | |
| `menit_istirahat` | int | Default `60` |
| `created_at` / `updated_at` | timestamp | |

**Unique:** (`jadwal_kerja_id`, `hari`).

---

### 4.7 `PenugasanJadwal` — karyawan ↔ jadwal

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `user_id` | FK → users | |
| `jadwal_kerja_id` | FK → jadwal_kerjas | |
| `berlaku_dari` | date | |
| `berlaku_sampai` | date\|null | Null = masih berlaku |
| `catatan` | text\|null | |
| `created_at` / `updated_at` | timestamp | |

**Tanpa model ini (MVP):** semua orang memakai `PengaturanAbsensi` aktif saja.

---

### 4.8 `LokasiAbsensi` — titik kantor di peta (MVP)

**Fungsi:** koordinat kantor + radius geofence yang ditampilkan di MapKit iOS dan divalidasi di server.

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `nama` | string | Mis. “Kantor HQ”, “Studio” |
| `lintang` | decimal(10,7) | Titik pusat di peta |
| `bujur` | decimal(10,7) | |
| `radius_meter` | int | Mis. `150` — lingkaran hijau di peta |
| `aktif` | bool | Bisa absen di lokasi ini |
| `alamat` | text\|null | Teks alamat untuk UI |
| `urutan` | int | Prioritas jika multi-lokasi |
| `created_at` / `updated_at` | timestamp | |

**Aturan jarak:**

- Hitung jarak user → lokasi dengan rumus Haversine (di app + di server)
- Jika `jarak > radius_meter` **dan** `tolak_jika_di_luar_radius = true` → **absen ditolak**
- Jika multi-lokasi aktif: lolos jika user masuk **salah satu** radius lokasi aktif

**MVP:** minimal 1 lokasi kantor diisi admin lewat Filament.

---

### 4.9 `KoreksiAbsensi` — pengajuan ubah jam (opsional)

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `absensi_id` | FK → absensis | |
| `user_id` | FK → users | Pemohon |
| `jam_masuk_diajukan` | datetime\|null | |
| `jam_pulang_diajukan` | datetime\|null | |
| `alasan` | text | |
| `status` | string | `menunggu` \| `disetujui` \| `ditolak` |
| `ditinjau_oleh` | FK\|null → users | |
| `ditinjau_pada` | datetime\|null | |
| `catatan_peninjau` | text\|null | |
| `created_at` / `updated_at` | timestamp | |

---

### 4.10 `PengajuanLembur` — lembur (opsional)

| Kolom | Tipe | Isi / keterangan |
|-------|------|------------------|
| `id` | bigint | PK |
| `user_id` | FK → users | |
| `absensi_id` | FK\|null → absensis | |
| `tanggal` | date | |
| `mulai_pada` | datetime | |
| `selesai_pada` | datetime | |
| `menit` | int | Durasi |
| `alasan` | text | |
| `status` | string | `menunggu` \| `disetujui` \| `ditolak` |
| `disetujui_oleh` | FK\|null → users | |
| `disetujui_pada` | datetime\|null | |
| `catatan` | text\|null | |
| `created_at` / `updated_at` | timestamp | |

---

## 5. Enum `status` pada `Absensi`

| Nilai | Arti |
|-------|------|
| `hadir` | Masuk tepat waktu |
| `terlambat` | Masuk tapi terlambat |
| `alfa` | Tidak hadir |
| `cuti` | Ada `LeaveRequest` approved |
| `libur` | Ada di `HariLibur` |
| `libur_mingguan` | Bukan hari kerja menurut jadwal |
| `setengah_hari` | Opsional |
| `remote` | WFH / remote (opsional) |

---

## 6. Diagram relasi

```text
User
 ├── Employee
 ├── LeaveRequest
 ├── Absensi ────────── LogAbsensi
 │      └── leave_request_id (opsional)
 ├── PenugasanJadwal ── JadwalKerja ── HariJadwalKerja
 ├── KoreksiAbsensi
 └── PengajuanLembur

PengaturanAbsensi   (global)
HariLibur
LokasiAbsensi       (MVP — titik kantor + radius peta)
```

---

## 7. Fitur kamera & peta (wajib)

### 7.1 Kamera (foto saat absensi)

**Tujuan:** bukti visual bahwa karyawan benar-benar hadir (bukan titip absen).

| Aspek | Ketentuan |
|-------|-----------|
| Kapan | Saat **masuk** dan **pulang** (mengikuti `wajib_foto`) |
| Sumber | Kamera device — **bukan** galeri (hindari upload foto lama) |
| iOS | Kamera native; permission `NSCameraUsageDescription` |
| Upload | `multipart/form-data` ke API; path disimpan di `LogAbsensi.path_foto` |
| Validasi server | File wajib jika `wajib_foto`; mime `image/jpeg`/`image/png`; ukuran ≤ `ukuran_foto_maks_kb` |
| Admin | Bisa lihat foto di Filament (detail log/absensi) |

**Alur UI iOS:**

1. User tekan Absen Masuk / Pulang  
2. App cek izin kamera → buka kamera  
3. User ambil foto → preview + konfirmasi  
4. Foto dikirim bersama GPS ke API  

Jika izin kamera ditolak → absen **tidak bisa dilanjutkan**.

### 7.2 Peta & geofence (hindari absen dari jauh)

**Tujuan:** user melihat posisi sendiri vs kantor; sistem menolak absen di luar radius.

| Aspek | Ketentuan |
|-------|-----------|
| Data kantor | Dari `LokasiAbsensi` (lintang, bujur, `radius_meter`) |
| GPS user | Core Location; permission `NSLocationWhenInUseUsageDescription` |
| Peta iOS | MapKit: pin kantor + pin user + **lingkaran radius** |
| Indikator UI | Hijau = dalam radius; merah = terlalu jauh + jarak (meter) |
| Validasi app | Tombol absen **disabled** jika di luar radius |
| Validasi server | **Wajib** hitung ulang Haversine (jangan percaya client saja) |
| Kebijakan | Absen dari jauh = **tidak diperkenankan** → HTTP 422 |

**Pesan error usulan:**

> Anda berada ±{jarak} m dari kantor (batas {radius} m). Absensi hanya diizinkan di area kantor.

**Endpoint pendukung peta:**

| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/absensi/lokasi` | Daftar `LokasiAbsensi` aktif (gambar peta + radius) |
| GET | `/absensi/cek-lokasi` | query: `lintang`, `bujur` → `{ dalam_radius, jarak_meter, lokasi }` |

---

## 8. Integrasi cuti

1. Saat isi/update `Absensi` tanggal `D`:
   - Cek `LeaveRequest` status approved, `start_date ≤ D ≤ end_date`
   - Jika ya → `status = cuti`, isi `leave_request_id`
2. Tanggal cuti **tidak** dihitung `alfa`
3. Absen masuk di hari cuti: **tolak** atau **izinkan** (keputusan bisnis — §11)

---

## 9. Ide API iOS (`/api/v1`)

> **Ditunda** di Makna Finance. Implementasi mobile/API absensi hanya di **Wofins** untuk saat ini.  
> Endpoint di bawah ini adalah acuan desain jika nanti diaktifkan kembali.

| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/absensi/hari-ini` | Status hari ini |
| GET | `/absensi/lokasi` | Titik kantor + radius untuk MapKit |
| GET | `/absensi/cek-lokasi` | Cek apakah user sudah dalam radius |
| POST | `/absensi/masuk` | `multipart`: lintang, bujur, akurasi, foto |
| POST | `/absensi/pulang` | sama seperti masuk |
| GET | `/absensi/riwayat` | from, to, page |
| GET | `/absensi/ringkasan` | bulan ini |

Filament: Resource untuk `Absensi`, `LogAbsensi`, `LokasiAbsensi`, `PengaturanAbsensi`, `HariLibur`.

---

## 10. Fase implementasi

### Fase 1 — MVP (admin Filament)

1. `PengaturanAbsensi` (wajib foto + tolak luar radius)  
2. `LokasiAbsensi` (titik kantor + radius + peta picker)  
3. `Absensi`  
4. `LogAbsensi` (foto + GPS + jarak)  
5. Service `AbsensiService` (validasi foto, Haversine, hitung terlambat)  
6. Filament: rekap, log, lokasi, pengaturan  

### Fase 1b — Mobile (hanya Wofins)

7. API masuk/pulang + lokasi + Sanctum  
8. iOS: MapKit + kamera + layar absensi  

### Fase 2

9. `HariLibur` — **Selesai**  
10. Rekap harian otomatis (cuti/libur/alfa) — **Selesai**  
11. Integrasi otomatis `LeaveRequest` → status `cuti` — **Selesai**  
12. `JadwalKerja` + `HariJadwalKerja` + `PenugasanJadwal` — **Selesai**  
13. Multi-lokasi kantor — sudah didukung `LokasiAbsensi` aktif  

### Fase 3

14. `KoreksiAbsensi` — **Selesai** (admin approve + pengajuan dari profile)  
15. `PengajuanLembur` — **Selesai** (admin approve + pengajuan dari profile)  
16. Laporan PDF/Excel + hook payroll (selesai: export Filament/profile, ringkasan di form Payroll)  

---

## 10b. Alur operasional (end-to-end)

> Ringkasan visual interaktif juga ada di canvas Cursor: `absensi-alur.canvas.tsx`.

### Urutan besar

```mermaid
flowchart LR
  A[Setup Admin] --> B[Absen Karyawan]
  B --> C[Rekap Harian]
  C --> D[Koreksi / Lembur]
  D --> E[Laporan PDF/Excel]
  E --> F[Usulan Payroll]
```

### 1) Setup admin (sekali / saat berubah)

```mermaid
flowchart TD
  P[Pengaturan Absensi<br/>jam, wajib foto/GPS, libur Sab/Min, tarif] --> L[Lokasi Absensi<br/>titik + radius di peta]
  L --> H[Hari Libur]
  H --> J[Jadwal Kerja + Penugasan]
  J --> R[Siap dipakai]
```

**Tempat:** Filament → group **Absensi**.

### 2) Absen masuk / pulang (karyawan)

```mermaid
flowchart TD
  U[Buka /profile/absensi] --> G[Kamera + GPS]
  G --> Cek{Cuti / libur efektif?}
  Cek -->|Ya| Tolak[Tolak absen]
  Cek -->|Tidak| Val{Foto + dalam radius?}
  Val -->|Tidak| Tolak
  Val -->|Ya| Simpan[Absensi + LogAbsensi]
  Simpan --> Hitung[Status & menit<br/>jadwal / pengaturan]
```

| Tab profile | Fungsi |
|-------------|--------|
| Hari Ini | Absen masuk/pulang |
| Peta & Lokasi | Lihat geofence kantor |
| Riwayat | 10 absensi terakhir |
| Ringkasan | Statistik bulan + unduh Excel/PDF |
| Koreksi | Ajukan perbaikan jam |
| Lembur | Ajukan lembur |

### 3) Rekap harian (sistem / admin)

```mermaid
flowchart TD
  S[Scheduler 01:15 Asia/Jakarta<br/>atau tombol Filament] --> Cmd[absensi:rekap-harian]
  Cmd --> Loop[Setiap karyawan Office]
  Loop --> Ada{Sudah absen masuk?}
  Ada -->|Ya| Skip[Jangan timpa]
  Ada -->|Tidak| Tanda[cuti / libur / libur mingguan / alfa]
```

### 4) Integrasi cuti

```mermaid
flowchart LR
  Ajukan[Ajukan LeaveRequest] --> Approve[Admin approve]
  Approve --> Obs[LeaveRequestObserver]
  Obs --> Cuti[Absensi status=cuti]
  Cuti --> Blok[Absen di hari itu ditolak]
```

### 5) Koreksi & lembur

```mermaid
flowchart TD
  subgraph Koreksi
    K1[Karyawan ajukan] --> K2[menunggu]
    K2 --> K3{Admin}
    K3 -->|Setujui| K4[Update jam Absensi]
    K3 -->|Tolak| K5[Selesai ditolak]
  end
  subgraph Lembur
    L1[Karyawan ajukan] --> L2[menunggu]
    L2 --> L3{Admin}
    L3 -->|Setujui| L4[Menit masuk ringkasan]
    L3 -->|Tolak| L5[Selesai ditolak]
  end
```

### 6) Laporan & payroll

```mermaid
flowchart TD
  F[Filter user / bulan / rentang] --> Svc[AbsensiLaporanService]
  Svc --> X[Excel]
  Svc --> P[PDF]
  Svc --> Pay[Form Payroll:<br/>ringkasan live]
  Pay --> A1[Salin ke catatan]
  Pay --> A2[Terapkan pengurangan]
  Pay --> A3[Terapkan bonus lembur]
```

**Rumus usulan (opsional):**  
`pengurangan = menit_terlambat × denda_terlambat_per_menit`  
`bonus = menit_lembur_disetujui × tarif_lembur_per_menit`  
(Tarif 0 = tidak mengisi angka otomatis.)

### Siapa mengerjakan apa

| Aktor | Tempat | Tugas utama |
|-------|--------|-------------|
| Admin | `/admin` → Absensi | Setup, rekap, approve, export, payroll |
| Karyawan | `/profile/absensi` | Masuk/pulang, koreksi, lembur, unduh sendiri |
| Sistem | Scheduler + Observer | Rekap harian, sinkron cuti, hitung status |

### Hak akses menu Filament (kehati-hatian)

| Menu | Siapa yang melihat |
|------|-------------------|
| Rekap Absensi | Role dengan permission Shield (operasional) |
| Log Absensi | Role dengan permission Shield (operasional) |
| Koreksi Absensi | Role dengan permission Shield (operasional) |
| Pengajuan Lembur | Role dengan permission Shield (operasional) |
| Lokasi Kantor | **`super_admin` saja** |
| Pengaturan Absensi | **`super_admin` saja** |
| Hari Libur | **`super_admin` saja** |
| Jadwal Kerja | **`super_admin` saja** |
| Penugasan Jadwal | **`super_admin` saja** |

Menu konfigurasi dikunci lewat `RestrictsToSuperAdmin` (navigasi + URL langsung).

---

## 11. Keputusan sebelum coding

- [x] Wajib GPS + peta geofence — **ya**
- [x] Absen dari jauh — **ditolak** (bukan hanya peringatan)
- [x] Wajib foto kamera (bukan galeri) — **ya**
- [x] Pakai `user_id` saja
- [x] Multi-lokasi via beberapa `LokasiAbsensi` aktif
- [x] Radius default **150 m**
- [x] Jam kerja global dulu; jadwal per karyawan menyusul
- [x] Absen di hari cuti: **tolak**
- [x] Absen di hari libur efektif: **tolak**
- [ ] Admin boleh input absensi manual di Filament? (tanpa foto/GPS)

---

## 12. Ringkas jawaban

**Model yang dibuat (nama Indonesia):**

| Prioritas | Model | Isinya singkat |
|-----------|--------|----------------|
| Wajib | `PengaturanAbsensi` | Jam kerja, wajib foto/GPS, libur Sabtu/Minggu |
| Wajib | `LokasiAbsensi` | Titik kantor di peta + `radius_meter` |
| Wajib | `Absensi` | Rekap harian: status, jam masuk/pulang |
| Wajib | `LogAbsensi` | Masuk/pulang + foto kamera + GPS + jarak ke kantor |
| Fase 2 | `HariLibur` | Tanggal & nama libur (+ tetap_masuk) |
| Menyusul | `JadwalKerja` | Nama pola jadwal |
| Menyusul | `HariJadwalKerja` | Detail Senin–Minggu per jadwal |
| Menyusul | `PenugasanJadwal` | Karyawan pakai jadwal mana |
| Opsional | `KoreksiAbsensi` | Pengajuan ubah jam + approval |
| Opsional | `PengajuanLembur` | Lembur + approval |

**Tidak dibuat ulang:** `User`, `Employee`, `LeaveRequest`, `LeaveType`, `LeaveBalance`.

---

## 13. Status dokumen

| Item | Status |
|------|--------|
| Ide model (nama Indonesia + isi kolom) | Selesai |
| Requirement kamera + peta geofence | Selesai |
| Migration MVP | Selesai |
| Model PHP MVP | Selesai |
| `AbsensiService` | Selesai |
| `AbsensiSeeder` | Selesai |
| Filament Resources (Absensi group + peta) | **Selesai** |
| `HariLibur` + Filament | **Selesai** |
| `JadwalKerja` / `PenugasanJadwal` | **Selesai** |
| `KoreksiAbsensi` + approve | **Selesai** |
| `PengajuanLembur` + approve | **Selesai** |
| `AbsensiRekapService` + `absensi:rekap-harian` | **Selesai** |
| Sinkron cuti dari `LeaveRequest` | **Selesai** |
| Profile web absensi (tabs) | **Selesai** |
| API `/api/v1` + Sanctum | **Ditunda** (hanya di Wofins) |
| iOS tab Absensi | **Ditunda** (hanya di Wofins) |

### Cara uji cepat

1. Admin: `/admin` → group **Absensi**
2. Set **Lokasi Kantor**, **Pengaturan Absensi**, **Hari Libur**
3. Karyawan: `/profile/absensi`
4. Manual rekap: tombol **Rekap Kemarin** di Rekap Absensi, atau `php artisan absensi:rekap-harian`
5. Cuti approved otomatis menandai status `cuti`

### Catatan

- Seed lokasi default: Palembang (Jl. Sintraman) — **verifikasi pin di peta** sebelum produksi.
- Scheduler: `absensi:rekap-harian` tiap hari 01:15 Asia/Jakarta (butuh cron `schedule:run`).
- Absensi via app mobile tetap di **Wofins** saja.
- `wajib_pulang`: rekap harian menandai `setengah_hari` jika masuk tanpa pulang (tanggal kemarin ke bawah).
- `menit_kerja`: dikurangi `menit_istirahat` dari jadwal kerja efektif.
