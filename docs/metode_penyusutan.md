# Dokumentasi Metode Penyusutan Aset Tetap

Dokumen ini menjelaskan tiga metode penyusutan (depresiasi) aset tetap yang tersedia dalam sistem Makna Finance, beserta logika perhitungan dan peruntukannya.

---

## 1. Metode Garis Lurus (Straight Line)
Metode ini adalah yang paling umum digunakan karena kesederhanaannya. Nilai aset disusutkan dengan jumlah yang **sama rata** setiap periode (bulan/tahun) selama masa manfaatnya.

### Rumus Perhitungan
```
(Harga Perolehan - Nilai Sisa) / Masa Manfaat (Bulan)
```

### Contoh Kasus
- **Aset:** Laptop MacBook Pro
- **Harga Beli:** Rp 24.000.000
- **Nilai Sisa (Residu):** Rp 0
- **Masa Manfaat:** 4 Tahun (48 Bulan)

**Perhitungan:**
Rp 24.000.000 / 48 Bulan = **Rp 500.000 / bulan**

### Kapan Menggunakan Metode Ini?
- Cocok untuk aset yang nilainya turun secara stabil seiring waktu.
- Contoh: Gedung kantor, furnitur (meja/kursi), instalasi listrik.

---

## 2. Metode Saldo Menurun (Declining Balance)
Metode ini mempercepat penyusutan. Nilai penyusutan akan **sangat besar di awal masa manfaat**, lalu mengecil secara bertahap di periode-periode berikutnya.

### Konsep Dasar
Biasanya menggunakan tarif persentase tetap (misal 2x lipat dari tarif garis lurus) yang dikalikan dengan Nilai Buku saat ini (bukan Harga Awal).

### Contoh Kasus
- **Aset:** Mobil Operasional
- **Harga Beli:** Rp 200.000.000
- **Masa Manfaat:** 5 Tahun

**Simulasi (Tarif 40% per tahun):**
- **Tahun 1:** Rp 200jt x 40% = **Rp 80.000.000** (Sisa Nilai Buku: Rp 120jt)
- **Tahun 2:** Rp 120jt x 40% = **Rp 48.000.000** (Sisa Nilai Buku: Rp 72jt)
- **Tahun 3:** Rp 72jt x 40% = **Rp 28.800.000** (dan seterusnya...)

### Kapan Menggunakan Metode Ini?
- Cocok untuk aset yang cepat usang karena teknologi atau aset yang memberikan manfaat terbesar di tahun-tahun awal.
- Contoh: Komputer/Server canggih, Smartphone, Kendaraan.

---

## 3. Metode Unit Produksi (Units of Production)
Metode ini mendasarkan penyusutan pada **aktivitas penggunaan fisik** aset, bukan berjalannya waktu. Jika aset tidak digunakan (tidak berproduksi), maka tidak ada biaya penyusutan.

### Rumus Perhitungan
```
(Harga Perolehan - Nilai Sisa) x (Produksi Bulan Ini / Total Kapasitas Produksi)
```

### Contoh Kasus
- **Aset:** Mesin Cetak Banner
- **Harga Beli:** Rp 100.000.000
- **Kapasitas Total:** 500.000 lembar cetak (Estimasi umur mesin)

**Perhitungan Bulan Januari:**
- Produksi Januari: 10.000 lembar
- Penyusutan: (Rp 100jt) x (10.000 / 500.000) = **Rp 2.000.000**

**Perhitungan Bulan Februari:**
- Produksi Februari: 2.000 lembar (Sepi order)
- Penyusutan: (Rp 100jt) x (2.000 / 500.000) = **Rp 400.000**

### Kapan Menggunakan Metode Ini?
- Cocok untuk mesin pabrik atau alat berat yang keausannya bergantung pada seberapa keras mesin itu bekerja.
- Contoh: Mesin produksi, kendaraan tambang (berdasarkan kilometer/jam operasional).

---

## Status Implementasi Saat Ini
Saat ini, sistem Makna Finance secara default telah mengimplementasikan logika otomatis untuk **Metode Garis Lurus (Straight Line)**. Metode Saldo Menurun dan Unit Produksi tersedia sebagai opsi data, namun logika perhitungan otomatisnya perlu disesuaikan lebih lanjut jika ingin diterapkan secara penuh.
