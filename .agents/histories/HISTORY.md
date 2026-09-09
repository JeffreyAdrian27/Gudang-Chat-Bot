# 📜 RINGKASAN LENGKAP HISTORI PENGEMBANGAN PROYEK SAPG
**Sistem Analisis Produk Gudang (SAPG)**
*Dokumen ini dibuat otomatis untuk menyimpan konteks lengkap dari awal hingga akhir sesi percakapan agar sesi chat berikutnya dapat langsung melanjutkan tanpa kehilangan konteks.*

---

## 📌 1. Informasi Umum & Arsitektur Sistem

- **Nama Aplikasi**: SAPG (Sistem Analisis Produk Gudang)
- **Lingkungan**: Lokal Laragon (Windows, Apache, MySQL, PHP 8.x)
- **Folder Proyek**: `c:\laragon\www\GudangChatBot`
- **Base URL**: `http://localhost/GudangChatBot/`
- **Tech Stack**:
  - **Backend**: Native PHP (Strict Types, PDO Prepared Statements, Session Guard, CSRF Protection, Rate Limiting)
  - **Database**: MySQL (`sapg_db`)
  - **Frontend / UI**: Vanilla CSS (Modern Dark Mode, Glassmorphism, Font Inter, Palette Indigo `#6366f1` & Cyan `#22d3ee`), Vanilla JavaScript (Fetch API async).
  - **AI / LLM**: Google Gemini API via server-side PHP cURL dengan pendekatan **RAG (Retrieval-Augmented Generation)** dari basis data gudang.

---

## 🗄️ 2. Basis Data & Akun Default

### Struktur Tabel (`sapg_db`)
1. **`users`**: Menyimpan kredensial admin dan role (`superadmin`, `admin`).
2. **`kategori`**: Kategori produk gudang (Elektronik, Fashion, Makanan, dll.).
3. **`produk`**: Data produk gudang (`kode_produk`, `nama_produk`, `kategori_id`, `stok`, `harga`, `deskripsi`).
4. **`stok_log`**: Riwayat audit mutasi stok barang (masuk, keluar, penyesuaian).
5. **`chat_log`**: Riwayat prompt dan balasan AI Chatbot beserta waktu request.

### Kredensial Login Bawaan
- **Halaman Login**: `http://localhost/GudangChatBot/login.php`
- **Username**: `admin`
- **Password**: `admin`
- **Role**: `superadmin`

---

## 📂 3. Struktur File & Folder Proyek

```
GudangChatBot/
├── .agents/
│   └── histories/              ← Arsip ringkasan histori sesi
│       └── HISTORY.md
├── .env                        ← Konfigurasi lingkungan & API Key Gemini
├── .gitignore
├── .htaccess                   ← Security headers, blokir .env & folder sensitif
├── robots.txt
├── sitemap.xml
├── index.php                   ← Landing page publik
├── login.php                   ← Halaman autentikasi login (brute-force protected)
├── logout.php                  ← Session destroy & redirect
├── dashboard.php               ← Ringkasan metrik stok & grafik cepat
│
├── config/
│   └── config.php              ← Parser .env, konstanta global, GEMINI_ENDPOINT
│
├── includes/
│   ├── db.php                  ← Database connection (PDO Singleton)
│   ├── auth.php                ← Session check, CSRF token helper, rate limiter
│   ├── functions.php           ← Helper formatting Rupiah, sanitasi HTML e(), alert
│   ├── header.php              ← Template Sidebar & Topbar navigasi
│   └── footer.php              ← Footer & penutup tag HTML
│
├── modules/
│   ├── kategori/               ← CRUD Kategori
│   │   ├── index.php           ← Daftar kategori
│   │   ├── create.php          ← Tambah kategori
│   │   ├── edit.php            ← Edit kategori
│   │   └── delete.php          ← Hapus kategori (CSRF verified)
│   │
│   ├── produk/                 ← CRUD Produk & AJAX Stok
│   │   ├── index.php           ← Daftar produk & tombol modal tambah stok
│   │   ├── create.php          ← Tambah produk baru
│   │   ├── edit.php            ← Edit data produk
│   │   ├── delete.php          ← Hapus produk
│   │   └── add_stok.php        ← Endpoint AJAX mutasi stok cepat
│   │
│   └── chatbot/                ← AI Assistant (RAG)
│       ├── index.php           ← Tampilan UI chat room & chip saran pertanyaan
│       └── ask.php             ← Endpoint backend pemroses query SQL + cURL Gemini
│
├── assets/
│   ├── css/
│   │   ├── style.css           ← Style sistem global (dark mode & tabel)
│   │   ├── chat.css            ← Tampilan bubble chat, avatar, & chip suggestions
│   │   └── landing.css         ← Style landing page depan
│   └── js/
│       ├── main.js             ← Interaksi sidebar mobile, auto-dismiss alert
│       └── produk.js           ← Handler AJAX modal stok
│
├── database/
│   ├── schema.sql              ← Skema DDL lengkap
│   ├── seed.php                ← Sample seeder data produk & admin
│   ├── init_db.php             ← Skrip inisialisasi otomatis
│   └── verify.php              ← Verifikasi integritas tabel & data
│
├── errors/
│   ├── 403.php                 ← Halaman Access Forbidden
│   └── 404.php                 ← Halaman Not Found
│
└── logs/
    └── error.log               ← Log PHP runtime error & cURL debug
```

---

## 🔄 4. Kronologi Percakapan & Solusi yang Diterapkan

Berikut adalah riwayat kendala dari awal hingga akhir percakapan beserta tindakan perbaikan yang telah dilakukan:

### 1. Pembangunan Fondasi Awal (Phase 1 – Phase 10)
- **Latar Belakang**: Membangun aplikasi gudang lengkap dengan fitur CRUD, AJAX mutasi stok, sistem keamanan lengkap (CSRF, Brute Force protection, SQL Injection protection via PDO), dan integrasi AI Assistant berbasis Gemini.
- **Hasil**: Seluruh modul, seeder database, dan aset CSS/JS berhasil dibuat.

### 2. Isu Nama Folder Mengandung Spasi (`Gudang Chat Bot` ➔ `GudangChatBot`)
- **Masalah**: Nama folder awal memiliki spasi (`Gudang Chat Bot`), yang menyebabkan URL encoding `%20` sehingga routing sering bermasalah dan memerlukan regex khusus di `config.php`.
- **Permintaan User**: Mengubah nama folder menjadi tanpa spasi (`GudangChatBot`) dan menghapus seluruh pengecekan regex spasi.
- **Tindakan**:
  - Folder resmi diubah menjadi `c:\laragon\www\GudangChatBot`.
  - Kode pengecekan regex spasi di `config/config.php` dibersihkan.
  - Konstanta `APP_BASE` disederhanakan: `define('APP_BASE', env('APP_BASE', '/GudangChatBot'));`.
  - Dibuat junction directory di Windows antara nama folder lama dan baru untuk menjamin backward compatibility IDE.

### 3. Perbaikan Routing Tombol "Kembali" pada Modul Produk
- **Masalah**: Saat berada di halaman form produk (seperti `modules/produk/edit.php` atau `create.php`), tombol "Kembali" mengarahkan URL ke rute yang salah/tidak ditemukan.
- **Tindakan**:
  - Memperbarui tautan tombol kembali pada form produk agar selalu mengarah ke `<?= BASE_URL ?>modules/produk/index.php`.

### 4. Perbaikan UI Halaman Chatbot yang Tidak Muncul
- **Masalah**: UI modul Kategori dan Produk tampil normal, namun saat mengakses menu Chatbot (`modules/chatbot/index.php`), tampilan kosong / tidak muncul layout semestinya.
- **Tindakan**:
  - Memperbaiki pemanggilan layout `header.php` dan `footer.php` pada `modules/chatbot/index.php`.
  - Memastikan CSS `assets/css/chat.css` termuat dengan urutan yang benar.
  - Memastikan container chat terbungkus sempurna di dalam layout grid sidebar.

### 5. Penanganan Respons Error AI Bot (HTTP 404 Google Gemini API)
- **Masalah**: Saat user mengirim pesan ke chatbot, balasan dari bot selalu menampilkan pesan error/gagal ("*Maaf, terjadi kendala teknis saat menghubungi AI...*").
- **Investigasi Mendalam**:
  - Memeriksa log di `logs/error.log` dan respon raw cURL dari Google Gemini.
  - Ditemukan respon cURL: `HTTP 404 Not Found` dari server Google.
  - Penyebab: Endpoint semula menggunakan nama model lama `gemini-1.5-flash:generateContent`. Google memperbarui penamaan versi API sehingga alias tersebut tidak lagi menerima request v1beta pada API key tertentu.
  - Melakukan probing ke endpoint daftar model Google (`https://generativelanguage.googleapis.com/v1beta/models`). Dikonfirmasi bahwa model aktif dan stabil saat ini adalah `gemini-flash-latest`.
- **Tindakan**:
  - Memperbarui konstanta `GEMINI_ENDPOINT` di `config/config.php`:
    ```php
    define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');
    ```
  - Menguji cURL ke API dengan query data gudang. AI merespons dengan format JSON/Markdown yang rapi dan kontekstual.

---

## ⚙️ 5. Pengaturan Environment (`.env`) Saat Ini

Pastikan file `c:\laragon\www\GudangChatBot\.env` memiliki format berikut:

```env
APP_NAME="SAPG — Sistem Analisis Produk Gudang"
APP_ENV=development
APP_TIMEZONE=Asia/Jakarta
APP_BASE=/GudangChatBot

DB_HOST=localhost
DB_PORT=3306
DB_NAME=sapg_db
DB_USER=root
DB_PASS=

# API Key Google Gemini (didapat dari Google AI Studio)
GEMINI_API_KEY=your_gemini_api_key_here
```

---

## 🧠 6. Cara Kerja RAG pada Chatbot (`modules/chatbot/ask.php`)

1. User mengirim pesan melalui form chat di browser.
2. `ask.php` membaca pesan user dan mengambil snapshot data gudang dari database:
   - Jika jumlah produk < 500: Mengambil seluruh daftar produk dan kategori.
   - Jika produk ≥ 500: Menjalankan query agregasi terarah (mencari termurah, termahal, stok habis, stok menipis, atau nilai total aset stok).
3. Data gudang dan 10 riwayat percakapan terakhir (`$_SESSION['chat_history']`) disusun ke dalam *System Prompt*.
4. Request dikirim ke Google Gemini (`gemini-flash-latest`) via server-side PHP cURL.
5. Jawaban AI disimpan ke database `chat_log` dan dirender langsung ke UI user.

---

## 🚀 7. Panduan Memulai Chat / Sesi Baru

Jika Anda membuka jendela chat baru di masa mendatang, cukup beritahukan kepada AI:
> *"Baca histori proyek di `.agents/histories/HISTORY.md` untuk memahami konteks dan konfigurasi aplikasi GudangChatBot."*

Semua konteks teknis, credential, struktur folder, dan solusi bug di atas akan langsung dipahami oleh AI pada sesi baru!
