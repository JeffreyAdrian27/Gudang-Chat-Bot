# SAPG (Sistem Analisis Produk Gudang) - Project History & Architecture Log (Part 2)

> **Tujuan Dokumen:**
> Dokumen ini adalah kelanjutan resmi dari `PROJECT_HISTORY.md` yang merangkum seluruh perubahan sistem, fitur baru, penyelesaian bug kritis, aturan teknis, dan arsitektur terkini per September 2026. Dokumen ini disimpan di `.agents/histories/PROJECT_HISTORY_PART2.md` untuk memberikan konteks lengkap bagi developer dan AI agent pada sesi berikutnya.

---

## 1. Aturan & Batasan Kritis Pengembangan (Mandatory Rules)

1. **LARANGAN GIT COMMIT & PUSH:**
   - Dilarang keras melakukan `git add`, `git commit`, atau `git push` ke GitHub.
   - Seluruh modifikasi kode dilakukan langsung secara lokal di direktori `D:\laragon\www\GudangChatBot\`.
2. **LARANGAN PENGGUNAAN EMOJI (ZERO EMOJI POLICY):**
   - Dilarang menggunakan emoji Unicode apa pun di seluruh file proyek (PHP, HTML, CSS, JS).
   - Penggunaan multi-byte emoji terbukti memicu kerusakan encoding (mojibake) saat file dibaca/ditulis di lingkungan Windows.
   - Sebagai pengganti emoji:
     - Gunakan ikon SVG murni (`<svg>...</svg>`).
     - Gunakan badge teks semantik (misalnya teks `AI` untuk avatar asisten bot, inisial huruf untuk avatar user).
     - Gunakan entitas HTML standar (misalnya `&rsaquo;` untuk separator breadcrumb).
3. **STANDARD ENCODING (NO BOM):**
   - Semua file PHP, CSS, JS wajib tersimpan dalam UTF-8 murni TANPA Byte Order Mark (BOM: `\xEF\xBB\xBF`).
   - BOM di file PHP akan memicu error fatal `strict_types declaration must be the very first statement` atau `headers already sent`.
4. **UPLOAD DIREKTORI KEAMANAN:**
   - Seluruh berkas upload disimpan di `assets/public/upload/`.
   - File `.htaccess` di dalam direktori upload memblokir eksekusi script PHP untuk keamanan server.

---

## 2. Modul Produk & Penanganan Gambar (CRUD + Upload)

### 2.1. Perubahan Skema Database
- Telah dijalankan migrasi penambahan kolom gambar pada tabel `produk`:
  ```sql
  ALTER TABLE produk ADD COLUMN gambar VARCHAR(255) NULL DEFAULT NULL AFTER harga;
  ```
- Kolom `gambar` menyimpan nama file acak unik, contoh: `produk_6aa10e34cd4fb7.73432221.png`.

### 2.2. Spesifikasi Implementasi CRUD Produk
1. **Tambah Produk (`modules/produk/create.php`):**
   - Input file gambar opsional dengan drag-and-drop area dan live preview thumbnail.
   - Validasi MIME type: `image/jpeg`, `image/png`, `image/webp`.
   - Validasi ukuran maksimum: 2MB.
   - Penamaan file menggunakan `uniqid('produk_', true) . '.' . $ext`.
   - Disimpan ke `D:\laragon\www\GudangChatBot\assets\public\upload\`.
2. **Edit Produk (`modules/produk/edit.php`):**
   - Menampilkan preview gambar yang sedang aktif.
   - Checkbox "Hapus Gambar" untuk menghapus file lama dari disk (`unlink()`) dan mengubah nilai database menjadi `NULL`.
   - Fitur ganti gambar dengan file baru (gambar lama otomatis dihapus dari disk).
3. **Hapus Produk (`modules/produk/delete.php`):**
   - Menghapus record produk dari database sekaligus menghapus file gambar fisik dari `assets/public/upload/` via `unlink()`.
4. **Daftar Produk (`modules/produk/index.php`):**
   - Menampilkan kolom thumbnail gambar (`52px x 52px`, rounded corners).
   - Fitur modal popup preview untuk melihat gambar ukuran besar saat thumbnail diklik.
   - Jika produk belum memiliki gambar, ditampilkan ikon placeholder netral tanpa error.

---

## 3. Modul Chatbot AI & RAG Architecture

### 3.1. Penampilan Gambar Produk di Chatbot (`modules/chatbot/ask.php`)
- Query data produk di `ask.php` kini menyertakan kolom `p.gambar`.
- URL gambar dibangun secara absolut: `APP_BASE . '/assets/public/upload/' . $r['gambar']`.
- Ketika AI merespon, sistem memeriksa produk apa saja yang **namanya disebutkan** dalam jawaban AI:
  ```php
  $produkGambar = [];
  if (isset($contextData['produk']) && is_array($contextData['produk'])) {
      foreach ($contextData['produk'] as $p) {
          if (!empty($p['gambar_url']) && mb_stripos($aiAnswer, $p['nama']) !== false) {
              $produkGambar[] = [
                  'nama'       => $p['nama'],
                  'gambar_url' => $p['gambar_url'],
                  'kategori'   => $p['kategori'],
                  'stok'       => $p['stok'],
                  'harga_fmt'  => $p['harga_fmt'],
              ];
          }
      }
  }
  $produkGambar = array_slice($produkGambar, 0, 8); // Dibatasi maks 8 item
  ```
- **Aturan Ketat Produk Tanpa Gambar:** Jika produk yang disebut tidak memiliki gambar, produk tersebut **TIDAK DITAMPILKAN** dalam kartu gambar (tidak menampilkan placeholder gambar kosong).
- JSON Response ask.php:
  ```json
  {
    "success": true,
    "message": "OK",
    "answer": "...",
    "csrf_token": "...",
    "produk_gambar": [ ... ]
  }
  ```

### 3.2. Desain Kartu Produk Side-to-Side (Horizontal Layout)
- Kartu produk di chatbot dirancang dengan layout horizontal (**Side-to-Side**), konsisten dengan tabel kelola produk:
  - **Sisi Kiri:** Thumbnail gambar persegi `52px x 52px`, `border-radius: 8px`, `object-fit: cover`.
  - **Sisi Kanan:** Deskripsi lengkap:
    - Nama Produk (bold, ellipsis jika terlalu panjang).
    - Badge Kategori + Jumlah Stok (`Stok: X`).
    - Harga Produk (warna aksen ungu cerah terformat rupiah).
  - Dilengkapi event `onerror="this.closest('.chat-product-card').remove()"` untuk mencegah tampilan gambar rusak jika file fisik tidak ditemukan.

### 3.3. Fitur Saran Pertanyaan Lanjutan (Post-Response Suggestion Chips)
- Disediakan tombol saran interaktif agar pengguna tidak bingung untuk melanjutkan percakapan:
  1. **Layar Awal (Welcome Screen):** Menampilkan tombol pilihan saat belum ada pesan.
  2. **Setelah AI Menjawab (Live Response):** Menampilkan suggestion chips di bawah jawaban AI & kartu gambar produk.
  3. **Pada Riwayat Chat (History Load):** Otomatis dirender di bagian bawah percakapan saat halaman di-refresh.
- Daftar pertanyaan lanjutan:
  - "Cek produk dengan stok menipis"
  - "Produk termahal dan termurah"
  - "Ringkasan stok per kategori"
  - "Total nilai estimasi aset gudang"
  - "Tampilkan 5 produk dengan stok terbanyak"
- Setiap chip dapat langsung diklik untuk mengirimkan pesan secara instan.

---

## 4. Troubleshooting & Solusi Bug Penting

| Masalah / Error | Akar Masalah | Solusi |
|---|---|---|
| **Mojibake & Karakter Aneh (`????...`)** | Karakter multi-byte emoji terdistorsi saat diubah berulang kali di shell Windows, diperparah dengan UTF-8 BOM. | Seluruh file di-rewrite ulang menggunakan karakter standar ASCII + entitas HTML murni. Nol emoji di seluruh file. |
| **PHP 8.5 Deprecated Notice pada cURL** | Pemanggilan `curl_close($ch)` di PHP 8.5 memicu warning `Deprecated`, yang tercetak sebelum header JSON dikirim. Akibatnya browser gagal melakukan `await res.json()` dan memicu error merah. | Baris `curl_close($ch)` dihapus (curl otomatis di-clean up oleh PHP runtime). JSON output kembali bersih 100%. |
| **Strict Types Fatal Error** | Muncul fatal error saat file diawali karakter UTF-8 BOM (`\xEF\xBB\xBF`). | BOM dieliminasi menggunakan script byte cleaning, memastikan `<?php` berada tepat pada byte 0. |

---

## 5. Ringkasan File Kunci Terkini

- `d:\laragon\www\GudangChatBot\modules\chatbot\index.php`: Antarmuka utama chatbot, bebas emoji, kartu produk side-to-side, suggestion chips.
- `d:\laragon\www\GudangChatBot\modules\chatbot\ask.php`: Endpoint RAG Gemini, ekstraksi data produk dengan gambar, filter JSON tanpa warning.
- `d:\laragon\www\GudangChatBot\assets\css/chat.css`: Styling layout chat, side-to-side product cards, typing bubble, suggestion chips.
- `d:\laragon\www\GudangChatBot\modules\produk\index.php`: Tabel produk dengan kolom foto thumbnail dan modal lightbox.
- `d:\laragon\www\GudangChatBot\modules\produk\create.php`: Form penambahan produk + validasi & upload file gambar.
- `d:\laragon\www\GudangChatBot\modules\produk\edit.php`: Form edit produk + preview, ganti foto, atau hapus foto.
- `d:\laragon\www\GudangChatBot\modules\produk\delete.php`: Penghapusan data produk beserta file fisik gambar terkait.
- `d:\laragon\www\GudangChatBot\assets\public\upload/`: Direktori penyimpanan fisik gambar produk terproteksi `.htaccess`.