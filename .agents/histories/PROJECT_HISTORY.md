# SAPG (Sistem Analisis Produk Gudang) — Project History & Architecture Log

> **Tujuan Dokumen:**  
> File ini berisi rangkuman komprehensif seluruh histori pengerjaan, arsitektur sistem, keputusan teknis, masalah yang dihadapi, serta solusinya dari awal hingga kondisi terkini. Dokumen ini disimpan di `.agents/histories/PROJECT_HISTORY.md` agar agent atau developer pada sesi chat baru dapat langsung memahami konteks proyek secara utuh tanpa kehilangan informasi penting.

---

## 1. Identitas & Lingkungan Proyek

- **Nama Aplikasi:** SAPG (Sistem Analisis Produk di Gudang)
- **Repositori Git:** `JeffreyAdrian27/Gudang-Chat-Bot`
- **Branch Terkini:** `fix` (remote: `origin/fix`)
- **Lokasi Proyek (Local):** `d:\laragon\www\GudangChatbot2`
- **Lingkungan Server:** Laragon (Apache 2.4, PHP >= 8.1, MySQL 8.0/MariaDB)
- **URL Base Lokal:** `http://localhost/gudangchatbot2` (atau virtual host root)
- **Spesifikasi Induk (PRD):** [PRD.md](file:///d:/laragon/www/GudangChatbot2/.agents/PRD.md)

---

## 2. Tech Stack & Standar Implementasi

| Komponen | Teknologi & Pendekatan |
|---|---|
| **Backend** | PHP Native (Strict Typing `declare(strict_types=1);`, OOP/Procedural terstruktur tanpa framework eksternal). |
| **Database** | MySQL menggunakan PDO dengan Prepared Statements & pencegahan SQL Injection. |
| **Frontend** | Vanilla HTML5 Semantik, CSS3 murni (Design tokens, glassmorphism, responsive dashboard layout, dark/neutral mode), dan Vanilla JavaScript modern (Async/Await Fetch API). |
| **AI / Chatbot** | Google Gemini API (Model: `gemini-1.5-flash`) dengan arsitektur RAG (Retrieval-Augmented Generation). |
| **Keamanan** | - Password di-hash menggunakan `password_hash()` BCRYPT.<br>- CSRF Protection via session token dengan validasi rotasi.<br>- Rate Limiting login (maksimal 5 percobaan gagal, lockout 15 menit).<br>- Proteksi SQL Injection via PDO prepared statement.<br>- XSS Sanitization via helper `e()` (`htmlspecialchars`).<br>- Sesi aman (`httponly`, `samesite=Strict`, rotasi session ID saat login).<br>- Direct-access guard (`requireLogin()`). |
| **SEO** | Meta tags komprehensif, file [robots.txt](file:///d:/laragon/www/GudangChatbot2/robots.txt), file [sitemap.xml](file:///d:/laragon/www/GudangChatbot2/sitemap.xml), heading hierarki semantik `<h1>`, semantic landmarks. |

---

## 3. Kronologi & Histori Pengembangan

### Fase 1: Perencanaan & Inisialisasi Repositori
- Mempersiapkan repositori Git lokal dan remote (`JeffreyAdrian27/Gudang-Chat-Bot`).
- Menambahkan direktori `.agents/` berisi [PRD.md](file:///d:/laragon/www/GudangChatbot2/.agents/PRD.md) dan skill pendukung pengembangan frontend/desain/arsitektur.
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
   - Menggunakan CSS khusus [assets/css/landing.css](file:///d:/laragon/www/GudangChatbot2/assets/css/landing.css).
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
   - `ask.php`: Engine RAG (Retrieval-Augmented Generation):
     - Jika data produk < 500 baris: Mengirim seluruh konteks produk & kategori ke Gemini AI.
     - Jika data produk >= 500 baris: Melakukan query agregasi cerdas berbasis kata kunci pertanyaan pengguna.
     - System prompt dirancang khusus agar AI bertindak sebagai analis inventaris gudang dan hanya menjawab berbasis database internal.

---

### Fase 3: Troubleshooting Error 404 pada Aset & Perbaikan Rute UI (Commit `0560540`)

#### Masalah yang Ditemukan
Saat membuka halaman modul di browser:
- `http://localhost/gudangchatbot2/modules/kategori/index.php`
- `http://localhost/gudangchatbot2/modules/produk/index.php`
- `http://localhost/gudangchatbot2/modules/chatbot/index.php`

Terjadi error konsol browser:
```text
index.php:15  GET http://localhost/gudangchatbot2/modules/kategori/assets/css/style.css net::ERR_ABORTED 404 (Not Found)
index.php:433 GET http://localhost/gudangchatbot2/modules/kategori/assets/js/main.js net::ERR_ABORTED 404 (Not Found)
```
Akibatnya, halaman kategori, produk, dan chatbot tampil polos tanpa styling (raw HTML), navigasi menu sidebar rusak, dan request AJAX gagal.

#### Akar Masalah (Root Cause Analysis)
Di dalam [config/config.php](file:///d:/laragon/www/GudangChatbot2/config/config.php), deteksi otomatis konstanta dasar aplikasi `APP_BASE` menggunakan regex greedy:
```php
// KODE LAMA BERMASALAH:
if (preg_match('#^(/[^/]+(?:/[^/]+)*)(?:/(?:index|login|logout|dashboard)\.php|/modules/|/assets/)#', $scriptPath, $m)) {
    $_detectedBase = $m[1];
}
```
Ketika script dieksekusi dari `/gudangchatbot2/modules/kategori/index.php`:
- Quantifier `(/[^/]+(?:/[^/]+)*)` bersifat rakus (greedy), mengambil string sampai segmen terdalam: `/gudangchatbot2/modules/kategori`.
- Bagian akhir regex mencocokkan `index.php`.
- Hasilnya, `APP_BASE` diset salah menjadi **`/gudangchatbot2/modules/kategori`** (seharusnya **`/gudangchatbot2`**).
- URL CSS berubah menjadi `/gudangchatbot2/modules/kategori/assets/css/style.css` (404 Not Found).

#### Solusi yang Diterapkan
1. **Perbaikan Deteksi Path di [config/config.php](file:///d:/laragon/www/GudangChatbot2/config/config.php)**:
   - Mengubah regex menjadi non-greedy (lazy) `^(.*?)` sebelum kata kunci folder root:
     ```php
     if (preg_match('#^(.*?)(?:/(?:index|login|logout|dashboard)\.php|/modules/|/assets/|/errors/|/includes/|/config/)#i', $scriptPath, $m)) {
         $_detectedBase = $m[1];
     }
     ```
   - Menambahkan fallback perbandingan filesystem `DOCUMENT_ROOT` vs `dirname(__DIR__)` untuk kompatibilitas virtual host maupun subdirektori.
2. **Perbaikan Breadcrumb di [includes/header.php](file:///d:/laragon/www/GudangChatbot2/includes/header.php)**:
   - Memastikan tautan breadcrumb secara dinamis diawali dengan prefix `APP_BASE`.
3. **Perbaikan Paginasi di [includes/functions.php](file:///d:/laragon/www/GudangChatbot2/includes/functions.php)**:
   - Fungsi `renderPagination` kini otomatis menormalisasi `$baseUrl` dengan `APP_BASE`.
4. **Perbaikan Redirect Sesi Expired AJAX**:
   - Di [modules/produk/add_stok.php](file:///d:/laragon/www/GudangChatbot2/modules/produk/add_stok.php) dan [modules/chatbot/ask.php](file:///d:/laragon/www/GudangChatbot2/modules/chatbot/ask.php), URL redirect session timeout diset menggunakan `APP_BASE . '/login.php'`.
   - Di [assets/js/produk.js](file:///d:/laragon/www/GudangChatbot2/assets/js/produk.js), penanganan URL redirect disesuaikan agar selalu menyertakan base path.

---

### Fase 4: Git Commit & Sinkronisasi Remote
- Dilakukan commit:
  ```bash
  git commit -m "FIX : UI and route access"
  ```
- Branch diubah/direname menjadi `fix`:
  ```bash
  git branch -m fix
  ```
- Perubahan berhasil di-push ke remote GitHub:
  ```bash
  git push origin fix
  ```

---

## 4. Struktur Direktori Proyek

```text
d:\laragon\www\GudangChatbot2\
├── .agents\
│   ├── histories\
│   │   └── PROJECT_HISTORY.md    <-- File dokumentasi histori ini
│   ├── skills\                   <-- Definisi skill agent (UI/UX, design, dll)
│   └── PRD.md                    <-- Dokumen spesifikasi kebutuhan produk
├── assets\
│   ├── css\
│   │   ├── chat.css              <-- Styling antarmuka chatbot AI
│   │   ├── landing.css           <-- Styling landing page publik
│   │   └── style.css             <-- Design system utama admin (layout, tabel, modal, form)
│   └── js\
│       ├── main.js               <-- Script global (toggle sidebar, toast, auto-dismiss alert)
│       └── produk.js             <-- Logika AJAX modal Tambah Stok & confirm dialog
├── config\
│   └── config.php                <-- Konfigurasi DB, konstanta aplikasi, auto-detect APP_BASE
├── database\
│   ├── schema.sql                <-- Skema tabel DDL MySQL
│   ├── seed.php                  <-- Seeder data awal (admin & produk)
│   ├── init_db.php               <-- Eksekutor inisialisasi cepat
│   └── verify.php                <-- Skrip verifikasi isi DB
├── errors\
│   ├── 403.php                   <-- Halaman error Access Forbidden
│   └── 404.php                   <-- Halaman error Not Found
├── includes\
│   ├── auth.php                  <-- Fungsi autentikasi, requireLogin, CSRF generator/validator
│   ├── db.php                    <-- Koneksi PDO singleton (getDB)
│   ├── footer.php                <-- Template footer admin & script loader
│   ├── functions.php             <-- Helper global (sanitasi, formatting rupiah/tanggal, pagination)
│   └── header.php                <-- Template header & sidebar admin terintegrasi
├── logs\
│   └── error.log                 <-- Log error runtime PHP
├── modules\
│   ├── chatbot\
│   │   ├── ask.php               <-- AJAX endpoint penerima pertanyaan & integrasi Gemini RAG
│   │   └── index.php             <-- Tampilan antarmuka ruang chat AI
│   ├── kategori\
│   │   ├── create.php            <-- Form tambah kategori
│   │   ├── delete.php            <-- Handler hapus kategori (dengan proteksi FK)
│   │   ├── edit.php              <-- Form edit kategori
│   │   └── index.php             <-- Daftar kategori produk + pagination
│   └── produk\
│       ├── add_stok.php          <-- AJAX endpoint penambahan stok instan
│       ├── create.php            <-- Form tambah produk baru
│       ├── delete.php            <-- Handler hapus produk
│       ├── edit.php              <-- Form edit detail produk
│       ├── get_token.php         <-- Endpoint refresh CSRF token jika diperlukan
│       └── index.php             <-- Daftar katalog produk + filter kategori + modal stok
├── dashboard.php                 <-- Dashboard analitik & ringkasan stok
├── index.php                     <-- Landing page publik
├── login.php                     <-- Form login admin
├── logout.php                    <-- Handler logout & penghancuran sesi
├── robots.txt                    <-- Konfigurasi crawling bot & SEO
└── sitemap.xml                   <-- Sitemap mesin pencari
```

---

## 5. Kredensial & Konfigurasi Penting

1. **Akun Login Administrator:**
   - **Username:** `admin`
   - **Password:** `admin`
   - *Tersimpan di tabel `users` dengan hash bcrypt.*
2. **Koneksi Database (Default Laragon):**
   - **Host:** `localhost:3306`
   - **Database:** `sapg_db`
   - **Username:** `root`
   - **Password:** *(kosong)*
3. **Konfigurasi API AI Gemini:**
   - Endpoint: `gemini-1.5-flash:generateContent`
   - Kunci API: Dikonfigurasi melalui environment variable `GEMINI_API_KEY` (di file `.env` pada root direktori jika diisi, atau default di `config/config.php`).
   - *Catatan:* Jika kunci API belum diisi saat bertanya di Chatbot AI, sistem akan memberikan respons ramah meminta pengisian API Key di `.env`.

---

## 6. Panduan Melanjutkan Pengembangan di Sesi Berikutnya

Ketika membuka sesi chat baru di Antigravity:
1. Baca file ini: `@[d:\laragon\www\GudangChatbot2\.agents\histories\PROJECT_HISTORY.md]`
2. Periksa status git lokal: pastikan berada pada branch `fix` atau buat merge request ke `main`.
3. Jika ingin menguji Chatbot AI dengan jawaban langsung dari Google Gemini, buat file `.env` di root direktori dengan isi:
   ```env
   GEMINI_API_KEY=AIzaSy... (kunci api Anda)
   ```
4. Semua halaman utama (`/dashboard.php`, `/modules/kategori/index.php`, `/modules/produk/index.php`, `/modules/chatbot/index.php`) kini sudah memiliki rute dan aset CSS/JS yang konsisten di semua skenario URL.
