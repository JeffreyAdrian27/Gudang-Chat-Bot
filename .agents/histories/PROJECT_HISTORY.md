# SAPG (Sistem Analisis Produk Gudang) — Project History & Architecture Log

> **Tujuan Dokumen:**  
> File ini berisi rangkuman komprehensif seluruh histori pengerjaan, arsitektur sistem, keputusan teknis, masalah yang dihadapi, serta solusinya dari awal hingga kondisi terkini. Dokumen ini disimpan di `.agents/histories/PROJECT_HISTORY.md` agar agent atau developer pada sesi chat baru dapat langsung memahami konteks proyek secara utuh tanpa kehilangan informasi penting.

---

## 1. Identitas & Lingkungan Proyek

- **Nama Aplikasi:** SAPG (Sistem Analisis Produk di Gudang)
- **Repositori Git:** `JeffreyAdrian27/GudangChatBot`
- **Branch Terkini:** `fix2` (remote: `origin/fix2`)
- **Lokasi Proyek (Local):** `d:\laragon\www\GudangChatBot`
- **Lingkungan Server:** Laragon (Apache 2.4, PHP >= 8.1, MySQL 8.0/MariaDB)
- **URL Base Lokal:** `http://localhost/GudangChatBot/`
- **Spesifikasi Induk (PRD):** `.agents/PRD.md`

---

## 2. Tech Stack & Standar Implementasi

| Komponen | Teknologi & Pendekatan |
|---|---|
| **Backend** | PHP Native (Strict Typing `declare(strict_types=1);`, OOP/Procedural terstruktur tanpa framework eksternal). |
| **Database** | MySQL menggunakan PDO dengan Prepared Statements & pencegahan SQL Injection. |
| **Frontend** | Vanilla HTML5 Semantik, CSS3 murni (Design tokens, glassmorphism, responsive dashboard layout, dark/neutral mode), dan Vanilla JavaScript modern (Async/Await Fetch API). |
| **AI / Chatbot** | Google Gemini API (Model: `gemini-3.1-flash-lite` / `gemini-flash-latest`) dengan arsitektur RAG (Retrieval-Augmented Generation). |
| **Keamanan** | - Password di-hash menggunakan `password_hash()` BCRYPT.<br>- CSRF Protection via session token dengan validasi rotasi.<br>- Rate Limiting login (maksimal 5 percobaan gagal, lockout 15 menit).<br>- Proteksi SQL Injection via PDO prepared statement.<br>- XSS Sanitization via helper `e()` (`htmlspecialchars`).<br>- Sesi aman (`httponly`, `samesite=Strict`, rotasi session ID saat login).<br>- Direct-access guard (`requireLogin()`).<br>- Secret management via `.env` yang diabaikan oleh `.gitignore`. |
| **SEO** | Meta tags komprehensif, file `robots.txt`, file `sitemap.xml`, heading hierarki semantik `<h1>`, semantic landmarks. |

---

## 3. Kronologi & Histori Pengembangan

### Fase 1: Perencanaan & Inisialisasi Repositori
- Mempersiapkan repositori Git lokal dan remote (`JeffreyAdrian27/GudangChatBot`).
- Menambahkan direktori `.agents/` berisi `PRD.md` dan skill pendukung pengembangan.
- Menentukan skema database dan alur CRUD untuk Admin gudang tunggal.

### Fase 2: Implementasi Inti Aplikasi (Commit `3199a5a`)
Pembangunan 39 file inti aplikasi (5.700+ baris kode) mencakup:
1. **Struktur Database (`database/`)**:
   - `schema.sql`: Membuat tabel `users`, `kategori`, `produk`, dan `stok_log` (dengan Foreign Key integritas relasional).
   - `seed.php`: Pengisian data awal otomatis:
     - User default: username `admin`, password `admin`.
     - Kategori default (Elektronik, Alat Tulis, Makanan, dsb).
     - Sample produk gudang beserta stok dan harga awal.
2. **Landing Page Publik (`index.php`)**:
   - Halaman depan berestetika tinggi (Dark hero, preview fitur, badge live status, call-to-action ke Login).
   - Menggunakan CSS khusus `assets/css/landing.css`.
3. **Autentikasi & Keamanan (`login.php`, `logout.php`, `includes/auth.php`)**:
   - Form login elegan dengan feedback error jelas dan penanganan lockout IP.
   - Pengecekan sesi ketat pada setiap modul admin melalui `requireLogin()`.
4. **Dashboard Admin (`dashboard.php`)**:
   - Kartu metrik ringkasan (Total Produk, Total Kategori, Stok Habis, Stok Rendah < 10, Nilai Total Stok).
   - Indikator produk kritis yang butuh restock segera.
   - Tabel riwayat log perubahan stok (`stok_log`).
5. **Modul Kategori (`modules/kategori/`)**:
   - `index.php`: Tabel kategori dengan pagination dan pencarian instan.
   - `create.php` & `edit.php`: Form validasi server-side dan pengecekan duplikasi nama kategori.
   - `delete.php`: Proteksi penghapusan; kategori tidak dapat dihapus jika masih ada produk terkait di dalamnya.
6. **Modul Produk (`modules/produk/`)**:
   - `index.php`: Tabel katalog produk dengan filter dropdown kategori, pencarian, dan pagination.
   - `create.php` & `edit.php`: Pengelolaan data produk, penetapan kategori, stok awal, dan harga satuan.
   - `delete.php`: Hapus produk dengan cascade delete ke `stok_log`.
   - `add_stok.php` + `assets/js/produk.js`: Fitur modal popup **"Tambah Stok"** via AJAX tanpa reload halaman. Berhasil memperbarui badge jumlah stok dan mencatat riwayat ke `stok_log`.
7. **Modul Chatbot AI (`modules/chatbot/`)**:
   - `index.php`: Antarmuka percakapan interaktif mirip modern AI assistant dengan quick suggestion chips.
   - `ask.php`: Engine RAG (Retrieval-Augmented Generation) berbasis data real database.

---

### Fase 3: Troubleshooting Error 404 pada Aset & Perbaikan Rute UI
- Memperbaiki regex greedy `APP_BASE` pada `config/config.php`.
- Menambahkan fallback `DOCUMENT_ROOT` sehingga rute selalu akurat baik di subfolder maupun root virtual host.
- Memperbaiki breadcrumb dan pagination di `includes/header.php` dan `includes/functions.php`.

---

### Fase 4: Troubleshooting GitHub Push Protection (Secret Detection) & Env Fix (Branch `fix2`)

#### Masalah:
1. Saat push ke `origin fix2`, GitHub menolak dengan error `GH013: Repository rule violations found for refs/heads/fix2 (Push cannot contain secrets)`.
2. Commit `9964e12` berisi Google API Key yang tertulis langsung di `modules/chatbot/ask.php`.
3. User mencoba melakukan commit baru `d3771a4` untuk mengembalikan file, namun commit `9964e12` masih tersimpan di dalam histori Git sehingga GitHub tetap menolak push.
4. Muncul error `error: src refspec main does not match any` dan `fatal: ambiguous argument 'main'` karena branch `main` lokal belum dibuat/di-track dari `origin/main`.
5. File `.env` sebelumnya salah ditaruh di dalam `modules/.env` (bukan di root), sehingga `config.php` tidak bisa membaca `GEMINI_API_KEY`.

#### Solusi yang Diterapkan:
1. **Menghapus Commit Berisi Secret dari Histori**:
   - Melakukan `git reset --soft 22fb45e` untuk membongkar commit `9964e12` dan `d3771a4`.
   - Memastikan `modules/chatbot/ask.php` kembali bersih tanpa secret apa pun.
   - Commit `9964e12` resmi hilang dari commit graph.
2. **Memperbaiki Lokasi `.env`**:
   - Memindahkan file `.env` dari `modules/.env` ke root proyek `d:\laragon\www\GudangChatBot\.env`.
   - Memperkuat `.gitignore` dengan pola `.env`, `.env.*`, `*.env`, `**/.env`.
3. **Memperbarui Endpoint Model Gemini**:
   - Mengarahkan `GEMINI_ENDPOINT` ke `gemini-3.1-flash-lite:generateContent` yang terbukti merespons dengan HTTP 200 OK.
4. **Membuat Branch `main` Lokal**:
   - Menjalankan `git branch main origin/main` sehingga `main` lokal sinkron dengan remote.
5. **Push Bersih**:
   - Melakukan commit bersih `9ff50e9` dan berhasil push ke `origin fix2` dengan sukses (HTTP 200 / zero errors).

---

## 4. Kredensial & Konfigurasi Penting

1. **Akun Login Administrator:**
   - **Username:** `admin`
   - **Password:** `admin`
2. **Koneksi Database:**
   - **Host:** `localhost:3306`
   - **Database:** `sapg_db`
   - **Username:** `root`
   - **Password:** *(kosong)*
3. **Konfigurasi API AI Gemini:**
   - File konfigurasi: `.env` di root direktori proyek.
   - Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent`
   - Teruji: HTTP 200 OK.
