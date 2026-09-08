<?php
/**
 * SAPG — Helper Functions
 */

declare(strict_types=1);

// ─── Output Sanitization ───────────────────────────────────────────────────────

/**
 * Escape output untuk mencegah XSS.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ─── Flash Messages ────────────────────────────────────────────────────────────

/**
 * Simpan flash message ke session.
 * @param string $type  'success' | 'error' | 'warning' | 'info'
 */
function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

/**
 * Ambil dan hapus flash message dari session.
 * @return array{type: string, message: string}|null
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Formatting ────────────────────────────────────────────────────────────────

/**
 * Format angka ke format Rupiah.
 */
function formatRupiah(int|float|string $angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia.
 */
function formatTanggal(string $tanggal, string $format = 'd M Y, H:i'): string
{
    $bulan = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
    ];

    $ts    = strtotime($tanggal);
    $bulan_num = date('m', $ts);
    $result    = date($format, $ts);
    return str_replace(date('M', $ts), $bulan[$bulan_num], $result);
}

/**
 * Format angka dengan pemisah ribuan.
 */
function formatAngka(int|float $angka): string
{
    return number_format($angka, 0, ',', '.');
}

// ─── Pagination ────────────────────────────────────────────────────────────────

/**
 * Hitung data pagination.
 * @return array{offset: int, limit: int, total_pages: int, current_page: int}
 */
function paginate(int $totalRows, int $limit = 15): array
{
    $currentPage = max(1, (int) ($_GET['page'] ?? 1));
    $totalPages  = max(1, (int) ceil($totalRows / $limit));
    $currentPage = min($currentPage, $totalPages);
    $offset      = ($currentPage - 1) * $limit;

    return [
        'offset'       => $offset,
        'limit'        => $limit,
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
    ];
}

/**
 * Render HTML pagination links.
 */
function renderPagination(int $totalPages, int $currentPage, string $baseUrl = ''): string
{
    if ($totalPages <= 1) {
        return '';
    }

    if ($baseUrl !== '' && defined('APP_BASE') && APP_BASE !== '' && !str_starts_with($baseUrl, APP_BASE)) {
        $baseUrl = APP_BASE . '/' . ltrim($baseUrl, '/');
    }
    $baseUrl = $baseUrl ?: strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $params  = $_GET;
    unset($params['page']);
    $query   = $params ? '&' . http_build_query($params) : '';

    $html = '<nav class="pagination" aria-label="Navigasi halaman"><ul class="pagination__list">';

    // Prev
    if ($currentPage > 1) {
        $html .= sprintf(
            '<li><a class="pagination__btn" href="%s?page=%d%s">&#8249; Prev</a></li>',
            $baseUrl, $currentPage - 1, $query
        );
    }

    // Page numbers (max 5 around current)
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= sprintf('<li><a class="pagination__btn" href="%s?page=1%s">1</a></li>', $baseUrl, $query);
        if ($start > 2) {
            $html .= '<li><span class="pagination__ellipsis">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' pagination__btn--active' : '';
        $html  .= sprintf(
            '<li><a class="pagination__btn%s" href="%s?page=%d%s">%d</a></li>',
            $active, $baseUrl, $i, $query, $i
        );
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li><span class="pagination__ellipsis">…</span></li>';
        }
        $html .= sprintf(
            '<li><a class="pagination__btn" href="%s?page=%d%s">%d</a></li>',
            $baseUrl, $totalPages, $query, $totalPages
        );
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= sprintf(
            '<li><a class="pagination__btn" href="%s?page=%d%s">Next &#8250;</a></li>',
            $baseUrl, $currentPage + 1, $query
        );
    }

    $html .= '</ul></nav>';
    return $html;
}

// ─── Validation ────────────────────────────────────────────────────────────────

/**
 * Validasi dan sanitasi integer positif dari input.
 */
function sanitizeInt(mixed $value, int $min = 0): ?int
{
    $val = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
    return $val !== false ? (int) $val : null;
}

/**
 * Validasi decimal positif.
 */
function sanitizeDecimal(mixed $value): ?float
{
    $val = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $val !== false && $val >= 0 ? $val : null;
}

// ─── Stock Badge ───────────────────────────────────────────────────────────────

/**
 * Render badge HTML untuk status stok.
 */
function stokBadge(int $stok): string
{
    if ($stok === 0) {
        return '<span class="badge badge--danger">Habis</span>';
    } elseif ($stok < 10) {
        return '<span class="badge badge--warning">Rendah (' . $stok . ')</span>';
    } else {
        return '<span class="badge badge--success">' . formatAngka($stok) . '</span>';
    }
}

// ─── JSON Response Helper ──────────────────────────────────────────────────────

/**
 * Kirim JSON response dan exit.
 */
function jsonResponse(bool $success, string $message, array $data = []): never
{
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
