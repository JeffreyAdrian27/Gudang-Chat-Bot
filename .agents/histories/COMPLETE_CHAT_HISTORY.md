# 📋 SAPG (Sistem Analisis Produk Gudang) — Dokumentasi Histori Lengkap

> **Tujuan Dokumen:**  
> Dokumen ini memuat rangkuman menyeluruh dari percakapan, instruksi pengguna, keputusan arsitektur, proses debugging, dan pengembangan fitur dari awal proyek hingga kondisi paling mutakhir. Disusun khusus agar saat sesi chat baru dibuka, AI Agent maupun developer manusia dapat langsung memahami histori dan konteks proyek tanpa ada informasi yang terlewat.

---

## 1. Identitas & Lingkungan Kerja Proyek

- **Nama Aplikasi:** SAPG (Sistem Analisis Produk di Gudang)
- **Repositori GitHub:** [`JeffreyAdrian27/GudangChatBot`](https://github.com/JeffreyAdrian27/GudangChatBot)
- **Branch Aktif:** `fix2` (sinkron dengan `origin/fix2`)
- **Lokasi Direktori:** `c:\laragon\www\Gudang Chat Bot` (alias: `c:\laragon\www\GudangChatBot`)
- **Web Server:** Laragon (Apache 2.4, PHP >= 8.1 dengan PDO MySQL, MySQL 8.0 / MariaDB)
- **URL Akses Lokal:** `http://localhost/Gudang%20Chat%20Bot/` atau `http://localhost/GudangChatBot/`
- **Spesifikasi Induk:** `.agents/PRD.md`

---

## 2. Tech Stack & Standar Implementasi

| Layer | Teknologi & Ketentuan |
|---|---|
| **Backend** | PHP Native terstruktur (`declare(strict_types=1);`), tanpa framework eksternal, modular per fitur. |
| **Database** | MySQL menggunakan **PDO Singleton** (`includes/db.php`), prepared statement wajib untuk seluruh query (zero SQL injection). |
| **Frontend UI/UX** | Vanilla HTML5 Semantik, Vanilla CSS3 murni (Dark theme elegan, modern glassmorphism, design tokens HSL/Hex, responsive grid/flexbox), dan Vanilla JavaScript modern (Async/Await Fetch API, tanpa jQuery). |
| **Kecerdasan Buatan (AI)** | Google Gemini API (`gemini-3.1-flash-lite:generateContent`) dengan arsitektur **RAG (Retrieval-Augmented Generation)** berbasis data riil database gudang. |
| **Penyimpanan Media** | File sistem lokal di `assets/public/upload/` dengan nama berkas acak (hash/timestamp) dan sanitasi tipe MIME. |
| **Keamanan Sistem** | - Password dienkripsi dengan `password_hash()` BCRYPT.<br>- Proteksi CSRF berbasis token per sesi dengan auto-refresh token via meta tag/JSON.<br>- Rate limiting login (5 kali gagal percobaan, lockout 15 menit).<br>- Sanitasi XSS output menggunakan helper `e()` (`htmlspecialchars`).<br>- Proteksi session guard (`requireLogin()`).<br>- Secret API Key disimpan dalam `.env` di root yang diabaikan oleh `.gitignore`. |

---

## 3. Skema Database Terkini (`database/schema.sql`)

1. **`users`**: Akun pengguna sistem (admin/superadmin).
   - `id_user`, `username`, `password` (bcrypt hash), `role`, `is_active`, timestamps.
2. **`kategori`**: Kelompok kategori barang gudang.
   - `id_kategori`, `nama_kategori`, timestamps.
3. **`produk`**: Katalog barang di gudang.
   - `id_produk`, `nama_produk`, `id_kategori` (FK), `stok`, `harga`, **`gambar`** (`VARCHAR(255) NULL` - nama file foto), timestamps.
4. **`stok_log`**: Log riwayat perubahan jumlah stok.
   - `id_log`, `id_produk` (FK cascade), `jumlah_tambah`, `stok_sebelum`, `stok_sesudah`, `keterangan`, timestamps.
5. **`chat_log`**: Arsip audit percakapan chatbot.
   - `id_chat`, `id_user` (FK), `pertanyaan`, `jawaban`, `tokens_in`, `tokens_out`, timestamps.

---

## 4. Kronologi Lengkap Pengembangan (Awal Hingga Terkini)

### 🔹 Fase 1: Perencanaan & Inisialisasi Proyek
- Pembuatan PRD komprehensif di `.agents/PRD.md`.
- Inisialisasi repositori Git dan branch pengembangan.
- Perancangan struktur database relasional dan database seeder (`database/seed.php`) dengan user default `admin` : `admin`.

### 🔹 Fase 2: Pembangunan Modul Inti Aplikasi
- **Landing Page Publik (`index.php`)**: Desain modern bernuansa dark premium, memperkenalkan kapabilitas sistem dan akses masuk login.
- **Autentikasi (`login.php`, `logout.php`, `includes/auth.php`)**: Sesi aman, pembatasan brute-force, dan proteksi rute halaman admin.
- **Dashboard Admin (`dashboard.php`)**: Ringkasan statistik real-time (Total Produk, Kategori, Stok Habis, Stok Rendah, Total Nilai Aset Gudang) dan tabel audit riwayat perubahan stok.
- **Modul Kategori (`modules/kategori/`)**: Fitur CRUD lengkap dengan proteksi tidak boleh menghapus kategori jika masih memiliki produk.
- **Modul Produk (`modules/produk/`)**: Tabel katalog produk, filter kategori, pagination, form tambah/edit, serta modal pop-up AJAX **"Tambah Stok"** instan tanpa reload halaman.
- **Modul Chatbot AI (`modules/chatbot/`)**: Antarmuka percakapan interaktif asisten gudang berbasis Gemini API dan RAG data database.

### 🔹 Fase 3: Debugging UI & Masalah Path / Aset (Blank Page & CSS 404)
- **Masalah**: Tampilan halaman chatbot sempat tidak muncul (blank atau style tidak ter-load), serta link aset CSS menghasilkan 404.
- **Penyebab**: Perhitungan konstanta `APP_BASE` pada `config/config.php` terganggu karena spasi pada nama folder direktori Laragon (`Gudang Chat Bot`) dan regex deteksi subfolder.
- **Solusi**: Penyempurnaan formula deteksi `APP_BASE` pada `config/config.php` dengan fallback `DOCUMENT_ROOT` yang akurat, sehingga pemanggilan seluruh aset CSS/JS dan link modul berjalan stabil di semua halaman.

### 🔹 Fase 4: Troubleshooting GitHub Push Protection (Secret Detection) & Env Fix
- **Masalah**: Saat menjalankan `git push`, GitHub menolak dengan error `GH013: Repository rule violations (Push cannot contain secrets)`. Ditemukan API key Google Gemini sempat tertulis langsung di dalam file `modules/chatbot/ask.php` pada commit lama (`9964e12`).
- **Penyebab**: Meskipun file diubah di commit berikutnya, commit lama yang mengandung secret masih ada di riwayat git commit history. Selain itu, file `.env` sempat salah diletakkan di dalam folder `modules/.env`.
- **Solusi**:
  1. Menjalankan `git reset --soft 22fb45e` untuk menghapus commit yang berisi secret dari riwayat Git tanpa menghilangkan perubahan kode.
  2. Memindahkan file `.env` ke root direktori proyek (`.env`) dan memastikan `GEMINI_API_KEY` terbaca oleh `config.php`.
  3. Memperbarui `.gitignore` agar mengabaikan `.env`, `.env.*`, `*.env`.
  4. Mengganti endpoint model AI ke `gemini-3.1-flash-lite:generateContent` yang terbukti aktif dan cepat.
  5. Melakukan push ulang yang bersih ke remote branch `fix2`.

### 🔹 Fase 5: Fitur Upload Gambar Produk (Permintaan User)
- **Permintaan User**:
  - Menambahkan dukungan gambar untuk setiap produk di database.
  - Menyesuaikan penanganan form POST tambah (`create.php`), ubah (`edit.php`), hapus (`delete.php`), dan tabel utama (`index.php`).
  - Menyimpan file upload gambar ke folder `assets/public/upload/`.
  - Menyertakan gambar pada respons AI Chatbot.
- **Solusi**:
  1. Menambahkan kolom `gambar VARCHAR(255) NULL` pada tabel `produk`.
  2. Membuat folder `assets/public/upload/` dan logika upload file dengan validasi ekstensi (`jpg`, `jpeg`, `png`, `webp`), batas ukuran file (maks 2MB), dan nama file acak unik.
  3. Menyediakan live image preview pada form create/edit.
  4. Menambahkan auto-delete file fisik saat gambar diganti atau saat produk dihapus.
  5. Menampilkan thumbnail gambar di tabel produk dengan fallback placeholder.

### 🔹 Fase 6: Penyempurnaan Modul Chatbot (Suggestion Chips, Side-by-Side Layout & Lightbox)
- **Permintaan User**:
  - Setelah AI menjawab, berikan kembali pilihan pertanyaan (suggestion chips) agar pengguna tidak bingung untuk mengajukan pertanyaan berikutnya.
  - Gambar produk sebelumnya belum muncul saat memilih pertanyaan "tampilkan daftar produk".
  - Memperbaiki tata letak kartu produk di chatbot agar **gambar dan deskripsi bersebelahan (side-by-side)**, bukan gambar di atas dan teks bertumpuk di bawah.
- **Penyebab & Analisis**:
  1. *Chips*: Panggilan scroll DOM sinkron mendahului render frame browser sehingga suggestion chips tidak ter-paint dengan sempurna di viewport.
  2. *Gambar Chatbot*: AI hanya mengembalikan teks, URL gambar dalam konteks prompt hanya dibaca AI namun tidak dioper ke antarmuka frontend.
  3. *Layout*: Kartu produk sebelumnya berupa card vertikal sempit (140px) di mana gambar berada di atas dan teks deskripsi/nama produk terpotong di bawahnya.
- **Solusi Komprehensif yang Diterapkan**:
  1. **Backend (`modules/chatbot/ask.php`)**:
     - Array `products_preview` (nama, kategori, harga terformat, stok, `gambar_url`) dikirim dalam respons JSON saat user menanyakan daftar produk atau saat nama produk tertentu disebutkan dalam percakapan.
  2. **Suggestion Chips Dinamis (`modules/chatbot/index.php`)**:
     - Fungsi `appendSuggestions()` dipanggil melalui `requestAnimationFrame` dengan double frame scroll delay, sehingga chips selalu muncul kembali secara mulus setelah AI selesai membalas.
  3. **Layout Bersebelahan (Side-by-Side) (`assets/css/chat.css` & `modules/chatbot/index.php`)**:
     - Kartu produk (`.chat-prod-card`) menggunakan flexbox horizontal:
       - **Sisi Kiri (`.chat-prod-card__img-wrap`)**: Gambar thumbnail (`width: 100px`, `object-fit: cover`).
       - **Sisi Kanan (`.chat-prod-card__body`)**: Deskripsi lengkap produk (kategori, nama lengkap produk tanpa terpotong, harga aksen biru cerah, badge stok hijau/merah).
     - Kartu terintegrasi langsung di dalam bubble jawaban AI (`.chat-msg__bubble`).
  4. **Lightbox Image Modal**:
     - Gambar produk pada chat dapat diklik untuk memperbesar tampilan (pop-up modal overlay), dan dapat ditutup kembali dengan mengklik di sembarang area atau menekan tombol `Esc`.

---

## 5. Struktur Direktori File Saat Ini

```text
Gudang Chat Bot/
├── .agents/
│   ├── histories/
│   │   ├── COMPLETE_CHAT_HISTORY.md   ← (File ini: ringkasan lengkap dari awal-akhir)
│   │   └── PROJECT_HISTORY.md          ← (Catatan arsitektur & histori sebelumnya)
│   ├── skills/                         ← Skill Antigravity IDE
│   └── PRD.md                          ← Product Requirement Document
├── .env                                ← Konfigurasi secret (GEMINI_API_KEY, DB credentials)
├── .gitignore                          ← Mengabaikan .env dan upload cache
├── .htaccess                           ← Proteksi keamanan Apache & block akses file sensitif
├── index.php                           ← Landing page publik
├── login.php                           ← Halaman login admin
├── logout.php                          ← Script keluar sesi
├── dashboard.php                       ← Dashboard metrik gudang & log stok
├── robots.txt                          ← Konfigurasi bot crawling
├── sitemap.xml                         ← Peta situs
├── config/
│   └── config.php                      ← Inisialisasi env, session, error handler, APP_BASE
├── includes/
│   ├── db.php                          ← Koneksi PDO MySQL Singleton
│   ├── auth.php                        ← Session guard, proteksi CSRF, login rate limiter
│   ├── functions.php                   ← Helper formatting, sanitasi HTML, breadcrumbs
│   ├── header.php                      ← Komponen layout Sidebar + Topbar
│   └── footer.php                      ← Penutup tag HTML & script global
├── database/
│   ├── schema.sql                      ← Skema pembuatan tabel MySQL
│   └── seed.php                        ← Seeder pengisi data awal
├── modules/
│   ├── kategori/
│   │   ├── index.php                   ← Daftar kategori & pagination
│   │   ├── create.php                  ← Tambah kategori baru
│   │   ├── edit.php                    ← Ubah kategori
│   │   └── delete.php                  ← Hapus kategori (terproteksi)
│   ├── produk/
│   │   ├── index.php                   ← Katalog produk dengan thumbnail gambar
│   │   ├── create.php                  ← Tambah produk + upload gambar
│   │   ├── edit.php                    ← Edit produk + ganti gambar
│   │   ├── delete.php                  ← Hapus produk + hapus file gambar
│   │   ├── add_stok.php                ← Endpoint AJAX tambah stok
│   │   └── get_token.php               ← Helper token CSRF untuk AJAX
│   └── chatbot/
│       ├── index.php                   ← Antarmuka AI Chatbot + Side-by-side cards + Lightbox
│       ├── ask.php                     ← Engine RAG + Gemini API caller + products_preview
│       └── clear.php                   ← Endpoint reset riwayat sesi percakapan
└── assets/
    ├── css/
    │   ├── landing.css                 ← Styling khusus landing page
    │   ├── auth.css                    ← Styling halaman login
    │   ├── main.css                    ← Styling utama dashboard, tabel, form, modal
    │   └── chat.css                    ← Styling khusus chatbot, kartu side-by-side, lightbox
    ├── js/
    │   ├── main.js                     ← Interaksi UI umum (sidebar toggle, dismiss alert)
    │   └── produk.js                   ← AJAX modal tambah stok & quick filter
    └── public/
        └── upload/                     ← Direktori penyimpanan file upload foto produk
```

---

## 6. Kredensial & Konfigurasi Penting

1. **Akun Login Administrator Default:**
   - **URL Login:** `http://localhost/Gudang%20Chat%20Bot/login.php`
   - **Username:** `admin`
   - **Password:** `admin` (di-hash menggunakan BCRYPT pada database)

2. **Koneksi Database (`.env`):**
   - **Host:** `localhost` (Port: `3306`)
   - **Database:** `sapg_db`
   - **Username:** `root`
   - **Password:** *(kosong)*

3. **Konfigurasi AI Gemini (`.env`):**
   - **Environment Variable:** `GEMINI_API_KEY=AIzaSy...`
   - **Model:** `gemini-3.1-flash-lite`
   - **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent`

---

## 7. Panduan Cepat untuk Sesi Chat Baru

Jika Anda (AI Agent atau Pengembang) melanjutkan pekerjaan pada proyek ini di sesi chat baru:

1. **Cek Status Git:**
   - Selalu periksa branch aktif dengan `git status`. Saat ini seluruh pekerjaan stabil berada di branch `fix2`.
   - Hindari men-commit file `.env` ke Git untuk mencegah pemblokiran push oleh GitHub Push Protection.
2. **Konteks Gambar Produk:**
   - Direktori upload berada di `assets/public/upload/`.
   - URL publik gambar dibentuk melalui konstanta `APP_BASE . '/assets/public/upload/' . $nama_file`.
3. **Konteks Chatbot:**
   - Jika ingin memodifikasi logika AI, edit `modules/chatbot/ask.php` (pengambilan data RAG dan prompt system) dan `modules/chatbot/index.php` (antarmuka chat dan kartu produk side-by-side).
   - Pastikan styling kartu produk tetap menggunakan kelas `.chat-prod-card` dan `.chat-product-list` di `assets/css/chat.css`.
4. **Validasi Kode:**
   - Sebelum melakukan commit/push, selalu uji sintaks PHP dengan:
     ```powershell
     php -l modules/chatbot/ask.php
     php -l modules/chatbot/index.php
     ```

---
*Dokumen ini diperbarui secara otomatis pada tanggal 09 September 2026.*
