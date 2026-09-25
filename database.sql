-- =========================================================
-- Database: db_inventory
-- Deskripsi: Skema database untuk Pendataan Alat Keluar/Masuk
--            (Alat Perbaikan Gangguan & Pemasangan WiFi)
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_inventory;

-- Tabel Pengguna (untuk login/register)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Kategori Alat (opsional, untuk pengelompokan)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Alat/Barang
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT DEFAULT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Transaksi Keluar/Masuk Alat
CREATE TABLE IF NOT EXISTS stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    type ENUM('IN','OUT') NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Data Contoh (Sample Data) - Alat Teknisi Jaringan / FTTH
-- =========================================================

INSERT INTO categories (name, description) VALUES
('Perangkat Jaringan', 'Router, OLT, ONU, switch, ODP, splitter, dan perangkat inti jaringan lainnya'),
('Kabel & Konektor', 'Kabel UTP, kabel fiber optic, konektor, dan aksesoris kelistrikan'),
('Alat Instalasi', 'Alat bantu pemasangan, perbaikan, dan pengukuran jaringan');

-- Kategori: 1 = Perangkat Jaringan, 2 = Kabel & Konektor, 3 = Alat Instalasi

INSERT INTO items (sku, name, category_id, unit, stock, min_stock) VALUES
-- 1. Perangkat Jaringan (Networking Devices)
('NET-MTK01', 'MikroTik RB450Gx4 (atau tipe lain)', 1, 'pcs', 5, 2),
('NET-OLT01', 'OLT (Optical Line Terminal) Epon/Gpon', 1, 'pcs', 2, 1),
('NET-RTR01', 'Router Wi-Fi / Access Point (Rumahan)', 1, 'pcs', 15, 5),
('NET-SWT01', 'Switch Hub 8/16/24 Port', 1, 'pcs', 6, 2),
('NET-ONU01', 'Modem ONT / ONU Client', 1, 'pcs', 20, 5),

-- 2. Kabel & Konektor (Cables & Connectors)
('KAB-CAT6', 'Kabel UTP Cat6', 2, 'meter', 300, 50),
('KAB-FO1C', 'Kabel Dropcore Fiber Optic 1 Core', 2, 'meter', 500, 100),
('KAB-FO2C', 'Kabel Dropcore Fiber Optic 2 Core', 2, 'meter', 300, 100),
('KON-RJ45', 'Konektor RJ45', 2, 'pcs', 200, 50),
('KON-FAS01', 'Fast Connector FO (SC/UPC atau SC/APC)', 2, 'pcs', 100, 30),

-- 3. Perangkat Luar Ruangan & Distribusi (ODN / Outside Plant)
('OUT-ODP08', 'ODP (Optical Distribution Box) 8 Core', 1, 'pcs', 10, 3),
('OUT-ODP16', 'ODP (Optical Distribution Box) 16 Core', 1, 'pcs', 6, 2),
('OUT-SPL04', 'PLC Splitter 1:4 / 1:8', 1, 'pcs', 12, 4),
('OUT-SOP01', 'Splicing On Connector (SOC) / Joint Closure', 1, 'pcs', 8, 3),
('OUT-BRK01', 'Bracket Tiang / Clamp Ring', 3, 'pcs', 25, 10),

-- 4. Alat Kerja & Instalasi (Tools & Accessories)
('TL-OPM01', 'OPM (Optical Power Meter)', 3, 'pcs', 3, 1),
('TL-VFL01', 'VFL (Visual Fault Locator / Laser FO)', 3, 'pcs', 3, 1),
('TL-SFC01', 'Fusion Splicer (Alat Sambung FO)', 3, 'pcs', 2, 1),
('TL-TSR01', 'LAN Tester', 3, 'pcs', 4, 2),
('TL-CRMP1', 'Tang Crimping RJ45', 3, 'pcs', 4, 2),
('TL-STR01', 'Fiber Stripper & Cleaver', 3, 'pcs', 3, 1),

-- 5. Aksesoris & Kelistrikan (Power & Accessories)
('PWR-ADP05', 'Adaptor 5 Volt', 2, 'pcs', 20, 5),
('PWR-ADP12', 'Adaptor 12 Volt', 2, 'pcs', 20, 5),
('PWR-POE01', 'PoE Injector 24V / 48V', 2, 'pcs', 15, 5),
('PWR-UPS01', 'Mini UPS untuk Backup Router / OLT', 1, 'pcs', 5, 2);

-- Catatan stok awal untuk setiap alat (agar riwayat transaksi ikut terisi)
INSERT INTO stock_transactions (item_id, type, quantity, note)
SELECT id, 'IN', stock, 'Stok awal' FROM items WHERE stock > 0;

