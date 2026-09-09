-- ============================================================
-- SAPG — Database Schema
-- Jalankan file ini sekali untuk membuat struktur database.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Tabel users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id_user    INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    role       ENUM('admin', 'superadmin') NOT NULL DEFAULT 'admin',
    is_active  TINYINT(1)  NOT NULL DEFAULT 1,
    created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabel kategori ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS kategori (
    id_kategori  INT         AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    created_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabel produk ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS produk (
    id_produk   INT            AUTO_INCREMENT PRIMARY KEY,
    nama_produk  VARCHAR(150)  NOT NULL,
    id_kategori  INT           NOT NULL,
    stok         INT           NOT NULL DEFAULT 0,
    harga        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    gambar       VARCHAR(255)  NULL DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kategori (id_kategori),
    INDEX idx_stok     (stok),
    INDEX idx_harga    (harga),
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabel stok_log ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stok_log (
    id_log       INT       AUTO_INCREMENT PRIMARY KEY,
    id_produk    INT       NOT NULL,
    jumlah_tambah INT      NOT NULL,
    stok_sebelum INT       NOT NULL,
    stok_sesudah INT       NOT NULL,
    keterangan   VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_produk (id_produk),
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tabel chat_log ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chat_log (
    id_chat    INT       AUTO_INCREMENT PRIMARY KEY,
    id_user    INT       NOT NULL,
    pertanyaan TEXT      NOT NULL,
    jawaban    TEXT,
    tokens_in  INT       DEFAULT 0,
    tokens_out INT       DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (id_user),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
