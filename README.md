# 📱 Sistem Informasi Inventory Nita Cell

**Perancangan Sistem Informasi Inventory Berbasis Web**  
Konter Nita Cell, Jl. Raya Pengasinan, Sawangan, Depok – Jawa Barat  
Kerja Praktik – Teknik Informatika – Universitas Pamulang (2026)

---

## 🛠️ Teknologi yang Digunakan

| Komponen       | Teknologi                            |
|----------------|--------------------------------------|
| Backend        | PHP Native (tanpa framework)         |
| Database       | MySQL                                |
| Frontend       | Bootstrap 5, Bootstrap Icons         |
| Grafik         | Chart.js 4                           |
| Server         | XAMPP (Apache + MySQL)               |

---

## 📁 Struktur Folder

```
nitacell/
├── index.php                  ← Halaman Login (entry point)
├── database.sql               ← Skrip SQL untuk import database
├── README.md
├── testing.txt
│
├── config/
│   ├── database.php           ← Konfigurasi koneksi MySQL
│   └── session.php            ← Helper autentikasi & session
│
├── includes/
│   ├── header.php             ← Sidebar, topbar, layout pembuka
│   └── footer.php             ← Penutup layout + script global
│
├── pages/
│   ├── dashboard.php          ← Statistik + grafik 7 hari
│   ├── data_barang.php        ← CRUD data barang
│   ├── data_supplier.php      ← CRUD data supplier
│   ├── data_pengguna.php      ← Manajemen user (Admin only)
│   ├── barang_masuk.php       ← Input + riwayat barang masuk
│   ├── barang_keluar.php      ← Input + riwayat barang keluar
│   ├── laporan_stok.php       ← Laporan + Export Excel/Print PDF
│   └── logout.php             ← Hancurkan session, redirect login
│
└── assets/
    └── css/
        └── style.css          ← Tema biru-tosca custom
```

---

## ⚙️ Cara Instalasi

### Prasyarat
- XAMPP versi 8.x (PHP 8.0+ dan MySQL 5.7+)
- Browser modern (Chrome, Firefox, Edge)

---

### Langkah 1 – Unduh & Letakkan Folder

1. Salin folder **`nitacell`** ke direktori `htdocs` XAMPP:
   ```
   C:\xampp\htdocs\nitacell\
   ```

---

### Langkah 2 – Jalankan XAMPP

1. Buka **XAMPP Control Panel**
2. Klik **Start** pada **Apache** dan **MySQL**
3. Pastikan kedua service berstatus hijau (Running)

---

### Langkah 3 – Import Database

1. Buka browser, akses: `http://localhost/phpmyadmin`
2. Klik **New** di panel kiri → beri nama database: `db_nitacell` → klik **Create**
3. Klik tab **Import** di bagian atas
4. Klik **Choose File** → pilih file `nitacell/database.sql`
5. Klik **Go** / **Import** di bagian bawah
6. Pastikan muncul pesan "Import has been successfully finished"

---

### Langkah 4 – Konfigurasi Database (jika perlu)

Jika password MySQL Anda berbeda dari default XAMPP, edit file:

```
nitacell/config/database.php
```

Ubah bagian ini sesuai dengan konfigurasi Anda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // username MySQL
define('DB_PASS', '');          // password MySQL (default XAMPP: kosong)
define('DB_NAME', 'db_nitacell');
```

---

### Langkah 5 – Akses Aplikasi

Buka browser dan akses:

```
http://localhost/nitacell/
```

---

## 🔑 Akun Login Default

| Role      | Username | Password  |
|-----------|----------|-----------|
| Admin     | admin    | admin123  |
| Karyawan  | budi01   | admin123  |

> **Catatan:** Segera ganti password setelah pertama kali login melalui menu **Data Pengguna** (khusus Admin).

---

## ✨ Fitur Utama

| Fitur                     | Admin | Karyawan |
|---------------------------|:-----:|:--------:|
| Login & Logout            | ✅    | ✅       |
| Dashboard Statistik       | ✅    | ✅       |
| CRUD Data Barang          | ✅    | ❌ (lihat saja) |
| CRUD Data Supplier        | ✅    | ❌ (lihat saja) |
| Manajemen Pengguna        | ✅    | ❌       |
| Input Barang Masuk        | ✅    | ✅       |
| Input Barang Keluar       | ✅    | ✅       |
| Laporan Stok              | ✅    | ✅       |
| Export Excel (CSV)        | ✅    | ✅       |
| Cetak PDF (Print)         | ✅    | ✅       |

---

## 🔒 Keamanan

- Password disimpan menggunakan `password_hash()` (bcrypt) – **tidak plaintext**
- Seluruh query menggunakan **Prepared Statement** → aman dari SQL Injection
- Autentikasi berbasis **PHP Session**
- Validasi input di sisi **client** (JavaScript) dan **server** (PHP)
- Halaman admin dilindungi fungsi `cekRole()`

---

## 📊 Aturan Bisnis Inventori

```
Barang Masuk  → Stok_Baru = Stok_Sekarang + Jumlah_Masuk
Barang Keluar → Stok_Baru = Stok_Sekarang - Jumlah_Keluar
Validasi      → Jika Jumlah_Keluar > Stok → transaksi ditolak + pesan error
Notifikasi    → Jika Stok_Akhir ≤ Stok_Minimum → tampil di dashboard & tabel
```

---

## 👥 Tim Pengembang

| Nama                       | NIM           |
|----------------------------|---------------|
| Ronald Parsaulian Simanjuntak | 231011403027 |
| Satria Anugra Putra        | 231011401474  |
| Eko Christian Aritonang    | 231011402518  |

**Dosen Pembimbing:** Ahmad Nursodiq, S.Kom., M.Kom  
**Pembimbing Lapangan:** Yunita Veronika Silalahi

---

*Universitas Pamulang – Program Studi Teknik Informatika – 2025/2026*
