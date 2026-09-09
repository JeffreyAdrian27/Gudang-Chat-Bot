<?php
/**
 * SAPG -------------------------------------- Chatbot AJAX Endpoint
 * POST /modules/chatbot/ask.php
 *
 * Implementasi RAG sesuai PRD 5.4.2:
 * - Jika total produk < 500: kirim semua data
 * - Jika >= 500: query agregasi terarah berdasarkan keyword pertanyaan
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Auth guard
if (!isLoggedIn()) {
    jsonResponse(false, 'Sesi habis. Silakan login kembali.', ['redirect' => '/login.php']);
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode tidak diizinkan.');
}

// CSRF
$csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCsrfToken($csrfToken)) {
    jsonResponse(false, 'Token keamanan tidak valid.');
}

// Validasi API key
if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'your_gemini_api_key_here') {
    jsonResponse(false, 'API key Gemini belum dikonfigurasi. Silakan isi GEMINI_API_KEY di file .env.');
}

// Ambil pertanyaan user
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    jsonResponse(false, 'Pesan tidak boleh kosong.');
}
if (mb_strlen($message) > 2000) {
    jsonResponse(false, 'Pesan terlalu panjang (maks 2000 karakter).');
}

$db = getDB();

//  Langkah 1: Hitung total produk ----------------------------------------------------------------------------
$totalProduk = (int) $db->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalKategori = (int) $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn();

//  Langkah 2: Retrieval Strategy (RAG) 
$contextData = [];
$msg = strtolower($message);

if ($totalProduk < CHAT_MAX_ROWS_FULL) {
    //  Mode: Kirim semua data (< 500 baris) 
    $rows = $db->query(
        "SELECT p.id_produk, p.nama_produk, p.stok, p.harga, p.gambar,
                k.nama_kategori
         FROM produk p
         JOIN kategori k ON p.id_kategori = k.id_kategori
         ORDER BY k.nama_kategori, p.nama_produk"
    )->fetchAll();

    $contextData = [
        'mode'         => 'full_data',
        'total_produk' => $totalProduk,
        'total_kategori' => $totalKategori,
        'produk'       => array_map(fn($r) => [
            'id'        => $r['id_produk'],
            'nama'      => $r['nama_produk'],
            'kategori'  => $r['nama_kategori'],
            'stok'      => (int) $r['stok'],
            'harga'     => (float) $r['harga'],
            'harga_fmt' => 'Rp ' . number_format((float)$r['harga'], 0, ',', '.'),
            'gambar_url' => !empty($r['gambar']) ? APP_BASE . '/assets/public/upload/' . $r['gambar'] : null,
        ], $rows),
    ];

} else {
    //  Mode: Query terarah berdasarkan keyword --------------------------------------
    $contextData = [
        'mode'         => 'aggregated',
        'total_produk' => $totalProduk,
        'total_kategori' => $totalKategori,
    ];

    // Harga termurah / termahal
    if (preg_match('/termurah|paling murah|harga rendah|harga terendah/i', $msg)) {
        $row = $db->query("SELECT p.nama_produk, p.harga, p.stok, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.harga ASC LIMIT 5")->fetchAll();
        $contextData['harga_termurah'] = $row;
    }
    if (preg_match('/termahal|paling mahal|harga tinggi|harga tertinggi/i', $msg)) {
        $row = $db->query("SELECT p.nama_produk, p.harga, p.stok, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.harga DESC LIMIT 5")->fetchAll();
        $contextData['harga_termahal'] = $row;
    }

    // Stok habis / rendah
    if (preg_match('/stok habis|kehabisan|stok 0|stok nol|out of stock/i', $msg)) {
        $rows = $db->query("SELECT p.nama_produk, p.stok, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.stok = 0 LIMIT 20")->fetchAll();
        $contextData['stok_habis'] = $rows;
        $contextData['stok_habis_count'] = count($rows);
    }
    if (preg_match('/stok rendah|stok sedikit|hampir habis|minim stok/i', $msg)) {
        $rows = $db->query("SELECT p.nama_produk, p.stok, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.stok > 0 AND p.stok < 10 ORDER BY p.stok ASC LIMIT 20")->fetchAll();
        $contextData['stok_rendah'] = $rows;
    }
    if (preg_match('/stok terbanyak|stok terbesar|paling banyak stok/i', $msg)) {
        $rows = $db->query("SELECT p.nama_produk, p.stok, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.stok DESC LIMIT 10")->fetchAll();
        $contextData['stok_terbanyak'] = $rows;
    }

    // Kategori analisis
    if (preg_match('/kategori|kelompok|jenis/i', $msg)) {
        $rows = $db->query(
            "SELECT k.nama_kategori, COUNT(p.id_produk) as jumlah_produk,
                    COALESCE(SUM(p.stok), 0) as total_stok,
                    COALESCE(AVG(p.harga), 0) as rata_harga
             FROM kategori k
             LEFT JOIN produk p ON k.id_kategori = p.id_kategori
             GROUP BY k.id_kategori, k.nama_kategori
             ORDER BY jumlah_produk DESC"
        )->fetchAll();
        $contextData['distribusi_kategori'] = $rows;
    }

    // Nilai total stok
    if (preg_match('/nilai stok|total nilai|total harga|investasi|aset|nilai inventaris/i', $msg)) {
        $row = $db->query("SELECT SUM(stok * harga) as total_nilai, SUM(stok) as total_unit FROM produk")->fetch();
        $contextData['nilai_stok'] = [
            'total_nilai' => (float) $row['total_nilai'],
            'total_nilai_fmt' => 'Rp ' . number_format((float)$row['total_nilai'], 0, ',', '.'),
            'total_unit' => (int) $row['total_unit'],
        ];
    }

    // Daftar produk / semua produk (default fallback dengan limit)
    if (
        preg_match('/daftar|semua produk|list produk|produk apa/i', $msg)
        || empty(array_filter($contextData, fn($v) => is_array($v)))
    ) {
        $rows = $db->query(
            "SELECT p.nama_produk, p.stok, p.harga, k.nama_kategori
             FROM produk p
             JOIN kategori k ON p.id_kategori = k.id_kategori
             ORDER BY k.nama_kategori, p.nama_produk
             LIMIT 50"
        )->fetchAll();
        $contextData['sample_produk']  = $rows;
        $contextData['catatan'] = "Data ini hanya 50 dari total {$totalProduk} produk.";
    }
}

//  Langkah 3: Bangun riwayat chat
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Ambil max 10 turn terakhir (sesuai PRD)
$maxTurns    = CHAT_MAX_HISTORY_TURNS * 2; // setiap turn = 2 pesan (user+model)
$historySlice = array_slice($_SESSION['chat_history'], -$maxTurns);

// Langkah 4: Susun request ke Gemini
$contextJson = json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$contents = [];

// Konteks data sebagai turn pertama
$contents[] = [
    'role'  => 'user',
    'parts' => [['text' => "Data gudang saat ini (JSON):\n{$contextJson}"]],
];
$contents[] = [
    'role'  => 'model',
    'parts' => [['text' => 'Baik, saya sudah memahami data gudang tersebut. Saya siap menjawab pertanyaan Anda.']],
];

// Tambahkan riwayat percakapan sebelumnya
foreach ($historySlice as $turn) {
    $contents[] = [
        'role'  => $turn['role'],
        'parts' => [['text' => $turn['text']]],
    ];
}

// Pertanyaan user saat ini
$contents[] = [
    'role'  => 'user',
    'parts' => [['text' => $message]],
];

$requestBody = [
    'system_instruction' => [
        'parts' => [[
            'text' =>
                'Kamu adalah SAPG AI Assistant, asisten analisis data untuk sistem manajemen gudang. ' .
                'Tugasmu HANYA menjawab pertanyaan berdasarkan data gudang yang diberikan di konteks. ' .
                'Jika data tidak cukup untuk menjawab, katakan dengan jujur bahwa data tidak tersedia. ' .
                'Jawab dalam Bahasa Indonesia yang jelas, singkat, dan profesional. ' .
                'Gunakan format yang rapi (bullet point jika perlu daftar, bold untuk angka penting). ' .
                'JANGAN membuat data fiktif atau mengarang informasi. ' .
                'JANGAN menjawab pertanyaan di luar topik manajemen gudang dan produk.',
        ]],
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature'    => 0.3,
        'maxOutputTokens' => 1024,
        'topK'           => 40,
        'topP'           => 0.95,
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
    ],
];

// Langkah 5: Panggil Gemini via cURL
$apiUrl = GEMINI_ENDPOINT . '?key=' . urlencode(GEMINI_API_KEY);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($requestBody),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$rawResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError   = curl_error($ch);
// curl_close($ch); // Unneeded & deprecated in PHP 8.5

if ($curlError) {
    error_log('[GEMINI cURL ERROR] ' . $curlError);
    jsonResponse(false, 'Gagal menghubungi layanan AI. Periksa koneksi server.');
}

$responseData = json_decode($rawResponse, true);

if ($httpCode !== 200) {
    $errMsg = $responseData['error']['message'] ?? 'Unknown error';
    error_log("[GEMINI API ERROR] HTTP {$httpCode}: {$errMsg}");

    $userMsg = match (true) {
        $httpCode === 429 => 'Batas kuota AI tercapai. Coba lagi dalam beberapa saat.',
        $httpCode === 403 => 'API key tidak valid atau tidak memiliki akses.',
        $httpCode >= 500  => 'Layanan AI sedang tidak tersedia. Coba lagi nanti.',
        default           => 'Terjadi kesalahan saat menghubungi AI.',
    };

    jsonResponse(false, $userMsg);
}

// Ekstrak teks jawaban
$candidates = $responseData['candidates'] ?? [];
if (empty($candidates)) {
    // Bisa juga karena safety filter
    $blockReason = $responseData['promptFeedback']['blockReason'] ?? '';
    error_log('[GEMINI] No candidates. BlockReason: ' . $blockReason);
    jsonResponse(false, 'AI tidak dapat memproses pertanyaan ini.');
}

$candidate   = $candidates[0];
$finishReason = $candidate['finishReason'] ?? '';

if ($finishReason === 'SAFETY') {
    jsonResponse(false, 'Pertanyaan ini tidak dapat diproses karena alasan keamanan konten.');
}

$aiAnswer = $candidate['content']['parts'][0]['text'] ?? '';
if (empty($aiAnswer)) {
    jsonResponse(false, 'AI tidak memberikan jawaban. Coba ulangi pertanyaan.');
}

$aiAnswer = trim($aiAnswer);

// Kumpulkan gambar produk yang nama-nya DISEBUTKAN dalam jawaban AI
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
// Maks 8 produk agar tidak memenuhi layar
$produkGambar = array_slice($produkGambar, 0, 8);

$timeNow = date('H:i');
$_SESSION['chat_history'][] = ['role' => 'user',  'text' => $message,  'time' => $timeNow];
$_SESSION['chat_history'][] = ['role' => 'model', 'text' => $aiAnswer, 'time' => $timeNow];

try {
    $tokensIn  = $responseData['usageMetadata']['promptTokenCount']     ?? 0;
    $tokensOut = $responseData['usageMetadata']['candidatesTokenCount'] ?? 0;

    $db->prepare(
        "INSERT INTO chat_log (id_user, pertanyaan, jawaban, tokens_in, tokens_out) VALUES (?, ?, ?, ?, ?)"
    )->execute([$_SESSION['admin_id'], $message, $aiAnswer, $tokensIn, $tokensOut]);
} catch (PDOException $e) {
    // Log tapi jangan gagalkan response utama
    error_log('[CHAT_LOG ERROR] ' . $e->getMessage());
}

jsonResponse(true, 'OK', [
    'answer'        => $aiAnswer,
    'csrf_token'    => generateCsrfToken(), // kirim token baru untuk request berikutnya
    'produk_gambar' => $produkGambar,       // gambar produk yang disebut dalam jawaban
]);


