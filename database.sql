-- =====================================================
-- DATABASE: db_nitacell
-- Sistem Informasi Inventory Nita Cell
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_nitacell;
USE db_nitacell;

-- =====================================================
-- TABEL: user (Pengguna sistem - Admin & Karyawan)
-- =====================================================
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('Admin','Karyawan') NOT NULL DEFAULT 'Karyawan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: supplier
-- =====================================================
CREATE TABLE supplier (
    id_supplier INT AUTO_INCREMENT PRIMARY KEY,
    nama_supplier VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: barang
-- =====================================================
CREATE TABLE barang (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(20) NOT NULL UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'Pcs',
    harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimum INT NOT NULL DEFAULT 5,
    id_supplier INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_barang_supplier FOREIGN KEY (id_supplier)
        REFERENCES supplier(id_supplier) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: barang_masuk
-- =====================================================
CREATE TABLE barang_masuk (
    id_masuk INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    id_supplier INT NULL,
    id_user INT NOT NULL,
    jumlah_masuk INT NOT NULL,
    harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0,
    tanggal_masuk DATE NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_masuk_barang FOREIGN KEY (id_barang)
        REFERENCES barang(id_barang) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_masuk_supplier FOREIGN KEY (id_supplier)
        REFERENCES supplier(id_supplier) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_masuk_user FOREIGN KEY (id_user)
        REFERENCES user(id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: barang_keluar
-- =====================================================
CREATE TABLE barang_keluar (
    id_keluar INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    id_user INT NOT NULL,
    jumlah_keluar INT NOT NULL,
    tanggal_keluar DATE NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_keluar_barang FOREIGN KEY (id_barang)
        REFERENCES barang(id_barang) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_keluar_user FOREIGN KEY (id_user)
        REFERENCES user(id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATA AWAL (SEED DATA)
-- =====================================================

-- User default
-- Password: admin123  (di-hash dengan password_hash PHP - bcrypt)
INSERT INTO user (username, password, nama, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'Admin'),
('budi01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Karyawan', 'Karyawan');
-- Catatan: kedua akun di atas memakai password yang sama: admin123

-- Supplier
INSERT INTO supplier (nama_supplier, no_hp, alamat) VALUES
('Toko ABC Distributor', '081234567890', 'Jl. Raya Parung No. 10, Bogor'),
('CV Sumber Aksesori', '081298765432', 'Jl. Pasar Lama, Depok'),
('Agen Pulsa Jaya', '081211223344', 'Jl. Pengasinan, Sawangan');

-- Barang
INSERT INTO barang (kode_barang, nama_barang, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum, id_supplier) VALUES
('BRG001', 'Kabel USB Type-C', 'Aksesori', 'Pcs', 8000, 15000, 50, 10, 2),
('BRG002', 'Casing iPhone 15', 'Aksesori', 'Pcs', 25000, 45000, 20, 5, 2),
('BRG003', 'Headset JBL', 'Aksesori', 'Pcs', 60000, 95000, 4, 5, 2),
('BRG004', 'Voucher Fisik 25rb', 'Voucher & Pulsa', 'Pcs', 23000, 25000, 100, 20, 3),
('BRG005', 'Charger Fast Charging', 'Aksesori', 'Pcs', 35000, 60000, 15, 5, 1),
('BRG006', 'Screen Protector Universal', 'Aksesori', 'Pcs', 5000, 12000, 30, 10, 2),
('BRG007', 'Mouse Wireless', 'Aksesori', 'Pcs', 45000, 75000, 8, 5, 1),
('BRG008', 'Power Bank 10000mAh', 'Aksesori', 'Pcs', 80000, 130000, 6, 3, 1);

-- Barang Masuk (contoh data)
INSERT INTO barang_masuk (id_barang, id_supplier, id_user, jumlah_masuk, harga_beli, tanggal_masuk, keterangan) VALUES
(1, 2, 1, 50, 8000, '2026-05-01', 'Stok awal bulan Mei'),
(4, 3, 1, 100, 23000, '2026-05-02', 'Restok voucher fisik');

-- Barang Keluar (contoh data)
INSERT INTO barang_keluar (id_barang, id_user, jumlah_keluar, tanggal_keluar, keterangan) VALUES
(1, 2, 5, '2026-05-10', 'Penjualan ke pelanggan'),
(3, 1, 1, '2026-05-12', 'Penjualan ke pelanggan');
