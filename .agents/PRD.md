# PRD — Sistem Analisis Produk di Gudang

## 1. Ringkasan Project

**Nama Project:** Sistem Analisis Produk di Gudang (SAPG)

**Deskripsi:**
Aplikasi web berbasis PHP Native untuk mengelola data produk gudang (kategori & produk), dilengkapi dashboard admin dan fitur chatbot AI yang bisa menjawab pertanyaan analitis seputar data produk (harga, stok, kategori) secara real-time berdasarkan data di database.

**Target Pengguna:** Admin gudang (single role).

**Platform Pengembangan:** Google Antigravity (Agentic IDE) — dokumen ini dipakai sebagai instruksi/spesifikasi untuk agent coding.

---

## 2. Tech Stack

| Komponen | Teknologi |
|---|---|
| Bahasa Backend | PHP Native (tanpa framework) |
| Versi PHP | >= 8.1 |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript (Vanilla / boleh + Bootstrap/Tailwind) |
| AI/Chatbot | AI API (lihat bagian 7 — Integrasi Chatbot) |
| Arsitektur | MVC sederhana (manual, tanpa framework) |
| Session/Auth | PHP native session |

**Catatan keamanan:** Simpan API key AI, kredensial DB, dan secret lainnya di file `.env` atau `config/config.php` yang **tidak** ter-commit ke Git (masukkan ke `.gitignore`). Jangan hardcode langsung di source code.

---

## 3. Tujuan Project

- Admin bisa login & logout dengan aman.
- Admin bisa mengelola data kategori produk (CRUD).
- Admin bisa mengelola data produk (CRUD), termasuk relasi ke kategori.
- Admin bisa menambah stok produk melalui modal popup langsung dari tabel produk (tanpa reload halaman penuh, idealnya via AJAX).
- Admin bisa bertanya ke chatbot AI dan mendapat jawaban analisis berbasis data real dari database (bukan jawaban generik AI).
- Website SEO-friendly dengan keyword: **Gudang, produk, chatbot, analisis**, dst.
- Sistem aman dari serangan umum (SQL Injection, dorking, akses tanpa login, dst).

---

## 4. User Role

Hanya 1 role: **Admin**

- Default credential (harus di-seed ke DB, password di-hash dengan `password_hash()`):
  - Username: `admin`
  - Password: `admin`

---

## 5. Fitur & Spesifikasi Detail

### 5.1 Autentikasi (Login/Logout)

**Deskripsi:** Halaman login sederhana, validasi ke tabel `users`, set session setelah berhasil.

**Acceptance Criteria:**
- Form login: username & password.
- Password di-hash (bcrypt via `password_hash` / `password_verify`), **jangan** plaintext.
- Setelah login sukses → redirect ke dashboard, set `$_SESSION['admin_id']`, `$_SESSION['username']`.
- Logout → destroy session, redirect ke halaman login.
- Semua halaman selain login **wajib** cek session aktif (lihat bagian Security).
- Rate limiting sederhana / delay untuk mencegah brute force (opsional tapi disarankan: max percobaan login, lockout sementara).

---

### 5.2 Kelola Kategori (CRUD)

**Tabel:** `kategori`

| Field | Tipe |
|---|---|
| id_kategori | INT, PK, AUTO_INCREMENT |
| nama_kategori | VARCHAR(100) |
| created_at | TIMESTAMP |
| updated_at | TIMESTAMP |

**Fitur:**
- List kategori (tabel dengan search/pagination).
- Tambah kategori (form nama_kategori).
- Edit kategori.
- Hapus kategori — **validasi**: cek dulu apakah kategori masih dipakai produk, jika ya beri warning/konfirmasi (agar tidak orphan foreign key).
- Validasi input: nama_kategori wajib diisi, unik (tidak boleh duplikat).

---

### 5.3 Kelola Produk (CRUD)

**Tabel:** `produk`

| Field | Tipe |
|---|---|
| id_produk | INT, PK, AUTO_INCREMENT |
| nama_produk | VARCHAR(150) |
| id_kategori | INT, FK → kategori.id_kategori |
| stok | INT, DEFAULT 0 |
| harga | DECIMAL(15,2) |
| created_at | TIMESTAMP |
| updated_at | TIMESTAMP |

**Fitur:**
- List produk (tabel: nama produk, kategori, stok, harga, aksi) dengan search & pagination.
- Tambah produk (form: nama_produk, pilih kategori via dropdown, stok awal, harga).
- Edit produk.
- Hapus produk (dengan konfirmasi).
- **Tambah Stok via Modal:**
  - Setiap baris produk ada tombol "Tambah Stok".
  - Klik tombol → muncul modal popup (tanpa reload halaman) berisi: nama produk (readonly), input jumlah tambahan stok.
  - Submit → update via AJAX (fetch/XHR ke endpoint PHP) → stok bertambah → tabel ter-update tanpa reload penuh (atau reload partial via JS).
  - Opsional (rekomendasi): catat riwayat penambahan stok di tabel `stok_log` (id_produk, jumlah_tambah, tanggal) — berguna juga untuk histori dan sebagai bahan chatbot.

---

### 5.4 Chatbot AI (Analisis Data)

**Deskripsi:** Chatbot berbasis AI API yang bisa menjawab pertanyaan admin dengan menganalisis data real dari database (bukan jawaban ngarang/generik).

**Contoh pertanyaan yang harus bisa dijawab:**
- "Berapa harga produk termurah / termahal?"
- "Apa saja produknya?" (list produk)
- "Kategori apa yang paling sedikit jumlah produknya?"
- "Produk apa yang stoknya habis (stok = 0)?"

**Cara Kerja (Alur Teknis - PENTING):**
Karena AI tidak punya akses langsung ke database, gunakan pola **RAG sederhana / function-calling manual**:

1. Admin mengetik pertanyaan di UI chatbot.
2. Backend PHP menerima pertanyaan.
3. Backend melakukan **query ke MySQL** untuk mengambil ringkasan data relevan, contoh:
   - Produk dengan harga MIN/MAX.
   - Jumlah produk per kategori (GROUP BY).
   - Produk dengan stok = 0.
   - Atau, untuk pendekatan lebih general: ambil seluruh data produk+kategori (jika jumlah data tidak terlalu besar) dalam format ringkas (JSON/teks).
4. Data hasil query tersebut dimasukkan ke dalam **prompt** yang dikirim ke AI API, contoh system prompt:
   ```
   Kamu adalah asisten analisis data gudang. Berikut data produk saat ini (format JSON): {data_json}
   Jawab pertanyaan user HANYA berdasarkan data di atas. Jika data tidak cukup, katakan tidak tahu.
   Pertanyaan user: {pertanyaan_admin}
   ```
5. AI API mengembalikan jawaban dalam bahasa natural → ditampilkan di UI chatbot.

**UI Chatbot:**
- Widget chat (bisa berupa panel/modal) dengan riwayat percakapan (minimal per-session, tidak wajib persist ke DB — opsional simpan ke tabel `chat_log`).
- Loading indicator saat menunggu respon AI.

**Konfigurasi API:**
- Endpoint & API key AI disimpan di `config/config.php` atau `.env`, contoh:
  ```php
  define('AI_API_KEY', getenv('AI_API_KEY')); // JANGAN hardcode key asli di sini
  define('AI_API_ENDPOINT', 'https://.../v1/chat/completions'); // sesuaikan provider AI
  ```
- **Catatan:** Perlu dikonfirmasi provider AI dari API key yang dimaksud (formatnya perlu dicek, apakah kompatibel dengan format OpenAI-style `chat/completions`, Anthropic-style `/v1/messages`, atau provider lain), agar request body & parsing response-nya sesuai. Sertakan fallback error handling jika API AI gagal/timeout (tampilkan pesan error yang ramah, jangan expose error mentah/API key di response ke user).

---

## 6. Struktur Database (MySQL)

```sql
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- hashed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    id_kategori INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    harga DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE RESTRICT
);

-- opsional, untuk histori tambah stok & bahan analisis chatbot
CREATE TABLE stok_log (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    jumlah_tambah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
);

-- opsional, riwayat chat
CREATE TABLE chat_log (
    id_chat INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    jawaban TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);
```

Seed default admin (jalankan sekali):
```sql
-- password hash untuk 'admin' harus digenerate via password_hash() di PHP, contoh placeholder di bawah
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$REPLACE_WITH_REAL_HASH');
```

---

## 7. Struktur Folder (Saran)

```
/sapg
├── config/
│   ├── config.php          # koneksi DB, konfigurasi umum
│   └── .env                # secret (API key, DB credential) - JANGAN commit
├── includes/
│   ├── auth.php            # fungsi cek session/login guard
│   ├── db.php              # koneksi PDO
│   └── functions.php       # helper umum (sanitasi, dsb)
├── modules/
│   ├── kategori/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── produk/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── delete.php
│   │   └── add_stok.php    # endpoint AJAX tambah stok
│   └── chatbot/
│       ├── index.php       # UI chatbot
│       └── ask.php         # endpoint AJAX -> query DB + call AI API
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── public/                  # document root (jika ingin pisahkan dari source)
│   └── index.php
├── login.php
├── logout.php
├── dashboard.php
└── .htaccess                # security & SEO (rewrite, security headers)
```

---

## 8. SEO Requirements

- Setiap halaman publik (jika ada landing page) punya `<title>`, `<meta name="description">`, `<meta name="keywords">` relevan dengan: **gudang, produk, chatbot, analisis, manajemen stok**, dst.
- Gunakan struktur heading semantik (H1 satu per halaman, H2/H3 turunan).
- URL bersih/friendly (gunakan `.htaccess` rewrite, hindari `?page=produk&id=1` jika memungkinkan → `/produk/1`).
- Gunakan alt text pada gambar.
- Sitemap.xml & robots.txt (arahkan agar halaman admin **tidak** di-index: `Disallow: /dashboard`, `/produk`, `/kategori`, dll — karena ini area privat, bukan konten publik).
- Catatan: karena ini aplikasi admin/internal (bukan e-commerce publik), SEO sebaiknya fokus pada halaman landing/about (jika ada), sementara halaman admin justru **diblokir dari indexing**.

---

## 9. Security Requirements

| Ancaman | Mitigasi |
|---|---|
| **Anti Dorking** (Google dork mengekspos halaman admin/config) | `robots.txt` disallow semua path admin; jangan biarkan direktori listing terbuka (disable `Options -Indexes` di `.htaccess`); jangan expose file `.env`/`.sql`/`.git` (block akses via `.htaccess`); custom error page (403/404) agar tidak bocorkan struktur folder. |
| **Akses tanpa login** | Setiap file di `modules/` wajib `require_once 'includes/auth.php'` yang cek `$_SESSION['admin_id']`, jika tidak ada → redirect ke login. Terapkan di awal setiap file, bukan hanya di menu. |
| **SQL Injection** | Gunakan **PDO Prepared Statement** untuk semua query (tidak ada string concat query mentah). |
| **XSS** | `htmlspecialchars()` di semua output ke HTML dari input user. |
| **CSRF** | Token CSRF di setiap form (generate per session, validasi saat submit). |
| **Session Hijacking** | `session_regenerate_id()` setelah login; set cookie `HttpOnly`, `Secure` (jika HTTPS), `SameSite=Strict`. |
| **Brute Force Login** | Batasi percobaan login (misal max 5x lalu lockout sementara / captcha sederhana). |
| **Password Lemah** | Hash dengan `password_hash()` (bcrypt), jangan pernah simpan plaintext — meskipun default admin/admin, hash tetap wajib. |
| **File/Config Exposure** | `.env`, `config.php` di luar document root jika bisa, atau block via `.htaccess`. |
| **API Key Leak** | AI API key hanya dipanggil dari sisi server (PHP), tidak pernah dikirim ke JS/frontend. |
| **HTTPS** | Rekomendasikan force HTTPS di production. |

---

## 10. Non-Functional Requirements

- Kompatibel PHP 8.1 ke atas (gunakan fitur PHP modern: typed properties, match expression, dsb bila relevan).
- Responsive UI (bisa diakses dari desktop & tablet minimal).
- Query database dioptimasi (index pada `id_kategori` di tabel produk, dsb).
- Error handling yang tidak mengekspos stack trace/query mentah ke user (log error ke file, tampilkan pesan generik ke user).

---

## 11. Milestone / Urutan Pengerjaan (Saran untuk Agent)

1. Setup project structure, koneksi DB (PDO), file `.env`/config.
2. Buat tabel database + seed admin.
3. Implementasi Auth (login, logout, session guard, CSRF).
4. Implementasi CRUD Kategori.
5. Implementasi CRUD Produk + relasi kategori.
6. Implementasi fitur Tambah Stok via modal (AJAX).
7. Implementasi Chatbot: endpoint query analisis DB + integrasi AI API.
8. Terapkan hardening security (poin 9) & SEO (poin 8).
9. Testing end-to-end (functional + security basic check).

---

## 12. Hal yang Perlu Dikonfirmasi Sebelum Development

- **Provider AI API** dari key yang diberikan — perlu dipastikan formatnya (OpenAI-compatible, Anthropic, atau lainnya) agar request/response body sesuai dokumentasi resmi provider tersebut.
- Apakah butuh multi-admin (multi user) di masa depan, atau cukup 1 akun admin fix.
- Apakah butuh landing page publik (untuk kebutuhan SEO), atau seluruh sistem private/internal saja.
