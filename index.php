<?php
// index.php — OGORooms homepage (Airbnb-style)

session_start();
require_once __DIR__ . '/ogo-api/config.php';

$meId    = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$meEmail = $_SESSION['user_email'] ?? '';
$uid     = $_SESSION['user_id']   ?? 0;
$role    = $_SESSION['user_role'] ?? '';

$q = trim($_GET['where'] ?? ($_GET['q'] ?? ''));
$checkinRaw  = isset($_GET['checkin']) ? trim((string) $_GET['checkin']) : '';
$checkoutRaw = isset($_GET['checkout']) ? trim((string) $_GET['checkout']) : '';
$guestRaw    = isset($_GET['guests']) ? trim((string) $_GET['guests']) : '';

function normalize_search_date(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($dt === false) {
        return '';
    }

    return $dt->format('Y-m-d');
}

function format_search_date_display(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($dt === false) {
        return '';
    }

    return $dt->format('j M Y');
}

$checkin  = normalize_search_date($checkinRaw);
$checkout = normalize_search_date($checkoutRaw);
if ($checkin !== '' && $checkout !== '' && $checkout <= $checkin) {
    $checkout = '';
}
$checkinDisplay  = format_search_date_display($checkin);
$checkoutDisplay = format_search_date_display($checkout);

$guestCount = '';
if ($guestRaw !== '' && is_numeric($guestRaw)) {
    $guestValue = (int) $guestRaw;
    if ($guestValue > 0) {
        $guestCount = (string) min($guestValue, 32);
    }
}
$initialGuestTotal = $guestCount !== '' ? (int) $guestCount : 0;
$guestSummaryText  = $guestCount !== ''
    ? ($guestCount . ' tamu')
    : 'Tambahkan tamu';

$suggestedDestinations = [
    [
        'title'     => 'Di dekat lokasi Anda',
        'subtitle'  => 'Cari tujuan di sekitar lokasi Anda',
        'value'     => 'Dekat saya',
        'emoji'     => '📍',
    ],
    [
        'title'     => 'Jakarta, DKI Jakarta',
        'subtitle'  => 'Pusat bisnis dan hiburan nasional',
        'value'     => 'Jakarta',
        'emoji'     => '🌆',
    ],
    [
        'title'     => 'Bandung, Jawa Barat',
        'subtitle'  => 'Sangat cocok untuk liburan akhir pekan',
        'value'     => 'Bandung, Jawa Barat',
        'emoji'     => '🏞️',
    ],
    [
        'title'     => 'Yogyakarta, Yogyakarta',
        'subtitle'  => 'Temukan budaya dan kuliner autentik',
        'value'     => 'Yogyakarta',
        'emoji'     => '🕌',
    ],
    [
        'title'     => 'Lembang, Jawa Barat',
        'subtitle'  => 'Udara sejuk untuk akhir pekan singkat',
        'value'     => 'Lembang',
        'emoji'     => '🌲',
    ],
    [
        'title'     => 'Kuta, Bali',
        'subtitle'  => 'Pantai dan matahari sepanjang hari',
        'value'     => 'Kuta, Bali',
        'emoji'     => '🌊',
    ],
    [
        'title'     => 'Semarang, Jawa Tengah',
        'subtitle'  => 'Liburan keluarga yang menyenangkan',
        'value'     => 'Semarang',
        'emoji'     => '🏡',
    ],
];



try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  ]);
  
  $sql = "SELECT id,title,location_city,nightly_price,cover_photo_url,host_id
          FROM simple_listings
          WHERE status='published'
          ORDER BY COALESCE(approved_at, created_at) DESC
          LIMIT 48";
  $list = $pdo->query($sql)->fetchAll();
  
} catch (Exception $e) {
    $pdo     = null;
    $dbError = $e->getMessage();
}

$publicListings = [];
$myListings     = [];

if ($pdo) {
    try {
        // --- PUBLIC LISTINGS (PUBLISHED ONLY) ---
        $paramsPub = [];
        $wherePub  = ['status = "published"'];

        if ($q !== '') {
            $wherePub[] = '('
                . 'city LIKE :q'
                . ' OR country LIKE :q'
                . ' OR location_city LIKE :q'
                . ' OR location_country LIKE :q'
                . ' OR title LIKE :q'
                . ')';
            $paramsPub[':q'] = '%' . $q . '%';
        }

        $sqlPub = 'SELECT id,
                          host_user_id,
                          title,
                          city,
                          country,
                          location_city,
                          location_country,
                          nightly_price,
                          nightly_price_strike,
                          has_discount,
                          discount_label,
                          cover_photo_url,
                          status,
                          created_at
                   FROM simple_listings';

        if (!empty($wherePub)) {
            $sqlPub .= ' WHERE ' . implode(' AND ', $wherePub);
        }
        $sqlPub .= ' ORDER BY created_at DESC LIMIT 24';

        $stmPub = $pdo->prepare($sqlPub);
        $stmPub->execute($paramsPub);
        $publicListings = $stmPub->fetchAll();

        // --- OWNER'S NON-PUBLISHED LISTINGS (DRAFT / IN_REVIEW / REJECTED) ---
        if ($meId > 0) {
            $sqlMine = 'SELECT id,
                               host_user_id,
                               title,
                               city,
                               country,
                               location_city,
                               location_country,
                               nightly_price,
                               nightly_price_strike,
                               has_discount,
                               discount_label,
                               cover_photo_url,
                               status,
                               created_at
                        FROM simple_listings
                        WHERE host_user_id = :me_id
                          AND status <> "published"
                        ORDER BY created_at DESC';
            $stmMine = $pdo->prepare($sqlMine);
            $stmMine->execute([':me_id' => $meId]);
            $myListings = $stmMine->fetchAll();
        }

    } catch (Exception $e) {
        $dbError        = $e->getMessage();
        $publicListings = [];
        $myListings     = [];
    }
}

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function normalize_local_path(string $value): string
{
    $normalized = str_replace('\\', '/', $value);
    $normalized = preg_replace('#/{2,}#', '/', $normalized);

    while (strpos($normalized, '../') === 0) {
        $normalized = substr($normalized, 3);
    }

    $normalized = ltrim($normalized, './');

    return $normalized;
}

function app_base_uri(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($scriptName === '') {
        $base = '';
        return $base;
    }

    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $base = '';
        return $base;
    }

    $base = rtrim($dir, '/');

    return $base;
}

function public_path(string $value): string
{
    $trimmed = ltrim($value, '/');
    $base    = app_base_uri();

    $prefix = $base !== '' ? $base . '/' : '/';

    return $prefix . $trimmed;
}

function resolve_cover_url(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $trimmed)) {
        return $trimmed;
    }

    $normalized = normalize_local_path($trimmed);
    if ($normalized === '') {
        return '';
    }

    $full = __DIR__ . '/' . ltrim($normalized, '/');
    if (is_file($full)) {
        return public_path($normalized);
    }

    if (strpos($normalized, 'listing-photos/') === 0) {
        return public_path($normalized);
    }

    return '';
}

function cover_image_for(array $row): string
{
    $resolved = resolve_cover_url($row['cover_photo_url'] ?? null);
    if ($resolved !== '') {
        return $resolved;
    }

    return 'https://images.pexels.com/photos/15714503/pexels-photo-15714503.jpeg?auto=compress&cs=tinysrgb&w=1200';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OGORooms – Book stays, experiences, and services</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f7f7f8;
            --bg-card: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #b2743b; /* milk chocolate */
            --accent-soft: #f6e5d6;
            --border-subtle: #e5e7eb;
            --shadow-soft: 0 20px 55px rgba(15,23,42,0.14);
            --radius-xl: 999px;
            --radius-lg: 24px;
        }

        * { box-sizing:border-box; }

        body {
            margin:0;
            font-family:"Plus Jakarta Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:#ffffff;
            color:var(--text-main);
        }

        a { color:inherit;text-decoration:none; }

        .page {
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }

        /* NAVBAR */
        .nav {
            position:sticky;
            top:0;
            z-index:40;
            backdrop-filter:blur(16px);
            background:rgba(255,255,255,0.96);
            border-bottom:1px solid rgba(229,231,235,0.85);
        }
        .nav-inner {
            max-width:1240px;
            margin:0 auto;
            padding:10px 24px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }
        .nav-left {display:flex;align-items:center;gap:10px;}
        .nav-logo-circle {
            width:36px;height:36px;border-radius:50%;
            background:var(--accent);
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:700;letter-spacing:0.04em;font-size:15px;
        }
        .nav-brand-text {display:flex;flex-direction:column;line-height:1.1;}
        .nav-brand-title {font-size:18px;font-weight:700;letter-spacing:0.12em;}
        .nav-brand-sub {
            font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.18em;
        }

        .nav-center {
            display:flex;gap:18px;align-items:center;font-size:14px;font-weight:500;
        }
        .nav-center button {
            border:none;background:transparent;padding:8px 0;cursor:pointer;position:relative;color:#4b5563;
        }
        .nav-center button.active {color:var(--text-main);}
        .nav-center button.active::after{
            content:"";position:absolute;left:0;right:0;bottom:-6px;margin-inline:auto;
            width:20px;height:3px;border-radius:999px;background:var(--accent);
        }

        .nav-right {display:flex;align-items:center;gap:12px;font-size:14px;}
        .nav-link {
            padding:6px 10px;border-radius:999px;cursor:pointer;color:#374151;
        }
        .nav-link:hover {background:#f3f4f6;}
        .nav-pill{
            border-radius:999px;
            border:1px solid var(--border-subtle);
            padding:5px 10px 5px 14px;
            display:flex;align-items:center;gap:12px;
            background:#ffffff;cursor:pointer;
        }
        .nav-pill-icon{
            width:32px;height:32px;border-radius:999px;
            background:#4b5563;display:flex;align-items:center;justify-content:center;
            color:#fff;font-size:13px;font-weight:600;
        }

        /* MAIN */
        .main {
            flex:1;
            padding:24px 0 40px;
        }
        .main-inner {
            max-width:1240px;
            margin:0 auto;
            padding:0 24px;
        }

        /* HERO */
        .hero {
            margin-bottom:28px;
        }
        .hero-title {
            font-size:32px;
            font-weight:700;
            letter-spacing:-0.03em;
            margin-bottom:8px;
        }
        .hero-sub {
            font-size:14px;
            color:var(--text-muted);
            max-width:520px;
            margin-bottom:24px;
        }

        /* SEARCH BAR AIRBNB STYLE */
        .search-bar-wrap {
            display:flex;
            justify-content:center;
            margin-bottom:26px;
        }
        .search-bar-wrap form {
            width:100%;
            max-width:960px;
            position:relative;
        }
        .search-pill {
            display:flex;
            align-items:center;
            background:#ffffff;
            border-radius:999px;
            border:1px solid rgba(229,231,235,0.95);
            box-shadow:0 25px 80px rgba(15,23,42,0.15);
            padding:6px 12px 6px 22px;
            width:100%;
            gap:12px;
            position:relative;
            z-index:20;
        }
        @media(max-width:900px){
            .search-pill {
                flex-direction:column;
                border-radius:24px;
                align-items:stretch;
                padding:12px 14px;
                gap:10px;
            }
        }

        .pill-section {
            flex:1;
            min-width:0;
            display:flex;
            flex-direction:column;
            gap:2px;
            position:relative;
            padding:8px 0;
            border-radius:18px;
            transition:background 0.2s ease;
        }
        .pill-section-location {flex:1;}
        .pill-section-dates {flex:1.3;}
        .pill-section-guests {flex:0.9;}
        .pill-section::after {
            content:'';
            position:absolute;
            inset:4px 0;
            border-radius:14px;
            background:transparent;
            transition:background 0.2s ease;
            z-index:0;
        }
        .pill-section.is-active::after {
            background:rgba(249,250,251,0.8);
        }
        .pill-label {
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:#9ca3af;
            position:relative;
            z-index:1;
        }
        .pill-value {
            font-size:14px;
            color:#111827;
            position:relative;
            z-index:1;
        }
        .pill-value-compact {
            font-weight:600;
        }
        .pill-placeholder {
            color:#9ca3af;
        }

        .pill-input {
            border:none;
            background:transparent;
            padding:0;
            margin:0;
            font-size:14px;
            font-family:inherit;
            color:var(--text-main);
            outline:none;
            width:100%;
        }
        .pill-input::placeholder {
            color:#9ca3af;
        }

        .pill-divider {
            width:1px;height:42px;background:#e5e7eb;
            margin:0 4px;
        }
        @media(max-width:900px){
            .pill-divider {display:none;}
        }

        .search-overlay {
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.35);
            backdrop-filter:blur(2px);
            opacity:0;
            pointer-events:none;
            transition:opacity 0.2s ease;
            z-index:10;
        }
        .search-overlay.is-visible {
            opacity:1;
            pointer-events:auto;
        }
        body.no-js .search-overlay {display:none;}

        .pill-popover {
            position:absolute;
            top:calc(100% + 18px);
            left:0;
            width:360px;
            border-radius:28px;
            background:#fff;
            border:1px solid rgba(226,232,240,0.9);
            box-shadow:0 30px 80px rgba(15,23,42,0.25);
            padding:22px;
            opacity:0;
            pointer-events:none;
            transform:translateY(6px);
            transition:opacity 0.2s ease, transform 0.2s ease;
            z-index:30;
        }
        .pill-popover.is-visible {
            opacity:1;
            pointer-events:auto;
            transform:translateY(0);
        }
        .pill-popover-wide {
            width:520px;
        }
        body.no-js .pill-popover,
        body.no-js .pill-popover-wide {
            position:static;
            width:100%;
            opacity:1;
            pointer-events:auto;
            transform:none;
            margin-top:12px;
            box-shadow:none;
        }
        @media(max-width:900px){
            .pill-popover,
            .pill-popover-wide {
                position:static;
                width:100%;
                margin-top:12px;
                box-shadow:none;
                transform:none;
            }
        }

        .suggestion-heading {
            font-size:13px;
            font-weight:600;
            color:#4b5563;
            margin-bottom:12px;
        }
        .suggestion-list {
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        .suggestion-item {
            display:flex;
            align-items:center;
            gap:12px;
            padding:10px 14px;
            border-radius:18px;
            cursor:pointer;
            transition:background 0.2s ease;
            border:none;
            width:100%;
            text-align:left;
            background:transparent;
        }
        .suggestion-item:hover {
            background:#f3f4f6;
        }
        .suggestion-icon {
            width:42px;height:42px;border-radius:16px;
            background:#fef3c7;
            display:flex;align-items:center;justify-content:center;
            font-size:20px;
        }
        .suggestion-text {
            display:flex;
            flex-direction:column;
            font-size:13px;
        }
        .suggestion-title {
            font-weight:600;
            color:#111827;
        }
        .suggestion-subtitle {
            color:#6b7280;
            font-size:12px;
        }

        .calendar-chip-row {
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:14px;
        }
        .calendar-chip {
            border:none;
            border-radius:999px;
            padding:8px 14px;
            background:#f3f4f6;
            color:#374151;
            font-size:12px;
            cursor:pointer;
            transition:background 0.2s ease;
        }
        .calendar-chip:hover {
            background:#e5e7eb;
        }
        .calendar-preview {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px;
        }
        .calendar-month {
            border-radius:18px;
            border:1px solid rgba(229,231,235,0.8);
            padding:14px;
        }
        .calendar-month h4 {
            margin:0 0 10px;
            font-size:14px;
            font-weight:600;
        }
        .calendar-week {
            display:grid;
            grid-template-columns:repeat(7,minmax(0,1fr));
            gap:4px;
            font-size:11px;
            text-align:center;
            color:#9ca3af;
            margin-bottom:6px;
        }
        .calendar-days {
            display:grid;
            grid-template-columns:repeat(7,minmax(0,1fr));
            gap:4px;
            font-size:12px;
            text-align:center;
        }
        .calendar-day {
            padding:6px 0;
            border-radius:999px;
            transition:background 0.15s ease, color 0.15s ease;
        }
        .calendar-day.is-clickable {
            cursor:pointer;
        }
        .calendar-day.is-clickable:hover {
            background:#f3f4f6;
        }
        .calendar-day.is-selected {
            background:#111827;
            color:#fff;
            font-weight:600;
        }
        .calendar-day.is-in-range {
            background:rgba(17,24,39,0.08);
            color:#111827;
        }
        .calendar-day.is-muted {color:#d1d5db;}

        .native-date-fields {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:16px;
            margin-top:18px;
        }
        .native-date-fields label {
            display:block;
            font-size:12px;
            color:#6b7280;
            margin-bottom:4px;
        }
        .native-date-fields input {
            width:100%;
            border:1px solid #d1d5db;
            border-radius:12px;
            padding:10px 12px;
            font-size:14px;
            font-family:inherit;
        }

        .guest-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px 0;
            border-bottom:1px solid rgba(229,231,235,0.8);
        }
        .guest-row:last-child {border-bottom:none;}
        .guest-info {font-size:13px;}
        .guest-title {font-weight:600;color:#111827;}
        .guest-sub {color:#6b7280;font-size:12px;}
        .guest-counter {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .counter-btn {
            width:32px;height:32px;border-radius:999px;
            border:1px solid #d1d5db;
            background:#fff;
            cursor:pointer;
            font-size:16px;
            color:#111827;
            display:flex;align-items:center;justify-content:center;
            transition:border-color 0.2s ease, background 0.2s ease;
        }
        .counter-btn:disabled {
            opacity:0.4;
            cursor:not-allowed;
        }
        .counter-btn:not(:disabled):hover {
            border-color:#111827;
            background:#f3f4f6;
        }
        .guest-presets {
            margin-top:16px;
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
        .guest-preset {
            border:none;
            border-radius:16px;
            background:#fff7ed;
            color:#b45309;
            padding:8px 12px;
            font-size:12px;
            cursor:pointer;
        }
        .guest-hint {
            font-size:12px;
            color:#6b7280;
            margin-top:10px;
        }

        .pill-search-btn-wrap {
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .pill-search-btn {
            border:none;
            cursor:pointer;
            border-radius:999px;
            padding:0 24px;
            height:46px;
            display:flex;
            align-items:center;
            gap:8px;
            background:var(--accent);
            color:#fff;
            font-size:14px;
            font-weight:600;
            box-shadow:0 18px 40px rgba(178,116,59,0.7);
        }
        .pill-search-icon {
            width:26px;height:26px;border-radius:999px;
            background:#f9fafb;
            display:flex;align-items:center;justify-content:center;
            font-size:14px;color:var(--accent);
        }

        /* SECTION HEADER */
        .section-header {
            display:flex;
            justify-content:space-between;
            align-items:baseline;
            margin-bottom:12px;
        }
        .section-title {
            font-size:18px;
            font-weight:600;
        }
        .section-link {
            font-size:13px;
            color:#4b5563;
            text-decoration:underline;
            text-underline-offset:3px;
            cursor:pointer;
        }

        /* GRID / CARDS (Airbnb style) */
        .stays-grid {
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:16px;
        }
        @media(max-width:1200px){
            .stays-grid {grid-template-columns:repeat(3,minmax(0,1fr));}
        }
        @media(max-width:900px){
            .stays-grid {grid-template-columns:repeat(2,minmax(0,1fr));}
        }
        @media(max-width:600px){
            .stays-grid {grid-template-columns:1fr;}
        }

        .stay-card {
            border-radius:18px;
            overflow:hidden;
            background:#ffffff;
            border:1px solid rgba(229,231,235,0.9);
            box-shadow:0 18px 40px rgba(148,163,184,0.18);
            display:flex;
            flex-direction:column;
            cursor:pointer;
            transition:transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stay-card:hover {
            transform:translateY(-2px);
            box-shadow:0 22px 60px rgba(15,23,42,0.18);
        }

        .stay-image {
            position:relative;
            height:210px;
            overflow:hidden;
            background:#f3f4f6;
        }
        .stay-image img {
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
            transform:scale(1.02);
            transition:transform 0.25s ease;
        }
        .stay-card:hover .stay-image img {
            transform:scale(1.06);
        }
        .stay-tag {
            position:absolute;
            left:12px;
            top:12px;
            padding:4px 10px;
            border-radius:999px;
            font-size:11px;
            background:rgba(17,24,39,0.9);
            color:#f9fafb;
        }

        .stay-body {
            padding:10px 12px 12px;
            display:flex;
            flex-direction:column;
            gap:2px;
        }
        .stay-location {
            font-size:13px;
            font-weight:500;
        }
        .stay-meta-small {
            font-size:12px;
            color:#6b7280;
        }

        .stay-price-row {
            margin-top:4px;
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:6px;
            font-size:13px;
        }
        .stay-price-main {
            font-weight:600;
        }
        .stay-price-main span {
            font-weight:400;
            font-size:11px;
            color:#6b7280;
        }
        .stay-price-strike {
            font-size:11px;
            color:#9ca3af;
            text-decoration:line-through;
        }
        .stay-discount-badge {
            display:inline-flex;
            align-items:center;
            gap:4px;
            padding:2px 8px;
            border-radius:999px;
            background:#dbeafe;
            color:#1d4ed8;
            font-size:11px;
        }
        .stay-discount-badge img {
            width:14px;height:14px;object-fit:contain;display:block;
        }

        .stay-owner-note {
            margin-top:4px;
            font-size:11px;
            color:#9ca3af;
        }

        .stay-actions {
            margin-top:6px;
            display:flex;
            justify-content:flex-end;
        }
        .btn-edit {
            border-radius:999px;
            border:1px solid #111827;
            padding:4px 10px;
            font-size:11px;
            background:#111827;
            color:#f9fafb;
            cursor:pointer;
        }

        .empty-text {
            font-size:13px;
            color:var(--text-muted);
            margin-top:4px;
        }

        hr.section-divider {
            border:none;
            border-top:1px solid #e5e7eb;
            margin:26px 0 18px;
        }
    </style>
</head>
<body class="no-js">
<div class="page">

    <!-- NAV -->
    <header class="nav">
        <div class="nav-inner">
            <div class="nav-left">
                <div class="nav-logo-circle">OG</div>
                <div class="nav-brand-text">
                    <span class="nav-brand-title">OGOROOMS</span>
                    <span class="nav-brand-sub">HOMES · EXPERIENCES · SERVICES</span>
                </div>
            </div>

            <div class="nav-center">
                <button type="button" class="active">Homes</button>
                <button type="button">Experiences</button>
                <button type="button">Services</button>
            </div>

            <div class="nav-right">
                <a href="host-start.php" class="nav-link">Become a host</a>

                <?php if ($meId > 0): ?>
                    <a href="host-dashboard.php" class="nav-link">Switch to hosting</a>
                    <a href="logout.php" class="nav-link">Log out</a>
                    <button class="nav-pill" type="button">
                        <span style="font-size:16px;">🌐</span>
                        <span class="nav-pill-icon">
                            <?php echo strtoupper(substr($meEmail, 0, 1)); ?>
                        </span>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Log in</a>
                    <button class="nav-pill" type="button">
                        <span style="font-size:16px;">🌐</span>
                        <span class="nav-pill-icon">G</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="main-inner">

            <!-- HERO -->
            <section class="hero">
                <h1 class="hero-title">
                    Book stays, experiences, and services around the world.
                </h1>
                <p class="hero-sub">
                    Discover cozy homes, curated local experiences, and trusted services – all in one place.
                </p>

                <!-- SEARCH BAR -->
                <div class="search-bar-wrap">
                    <form method="get" action="search-results.php">
                        <div class="search-pill">
                            <div class="pill-section pill-section-location" data-popover-target="wherePopover">
                                <label class="pill-label" for="searchWhere">Lokasi</label>
                                <input
                                    type="text"
                                    name="where"
                                    id="searchWhere"
                                    class="pill-input"
                                    placeholder="Cari destinasi"
                                    value="<?php echo h($q); ?>"
                                    autocomplete="off"
                                >
                                <div class="pill-popover" id="wherePopover">
                                    <div class="suggestion-heading">Destinasi yang disarankan</div>
                                    <div class="suggestion-list">
                                        <?php foreach ($suggestedDestinations as $dest): ?>
                                            <button
                                                type="button"
                                                class="suggestion-item"
                                                data-value="<?php echo h($dest['value']); ?>"
                                            >
                                                <div class="suggestion-icon"><?php echo h($dest['emoji']); ?></div>
                                                <div class="suggestion-text">
                                                    <div class="suggestion-title"><?php echo h($dest['title']); ?></div>
                                                    <div class="suggestion-subtitle"><?php echo h($dest['subtitle']); ?></div>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pill-divider"></div>

                            <div class="pill-section pill-section-dates" data-popover-target="datePopover">
                                <div class="pill-label">Tanggal</div>
                                <div
                                    class="pill-value pill-value-compact"
                                    id="dateSummary"
                                    data-empty-text="Tambahkan tanggal"
                                >
                                    <?php
                                    $dateSummary = 'Tambahkan tanggal';
                                    if ($checkinDisplay !== '' && $checkoutDisplay !== '') {
                                        $dateSummary = $checkinDisplay . ' - ' . $checkoutDisplay;
                                    } elseif ($checkinDisplay !== '') {
                                        $dateSummary = $checkinDisplay;
                                    } elseif ($checkoutDisplay !== '') {
                                        $dateSummary = $checkoutDisplay;
                                    }
                                    echo h($dateSummary);
                                    ?>
                                </div>
                                <div class="pill-popover pill-popover-wide" id="datePopover">
                                    <div class="calendar-chip-row">
                                        <button type="button" class="calendar-chip" data-date-range="weekend">Akhir pekan ini</button>
                                        <button type="button" class="calendar-chip" data-date-range="next-week">Pekan depan</button>
                                        <button type="button" class="calendar-chip" data-date-range="next-month">Liburan bulan depan</button>
                                        <button type="button" class="calendar-chip" data-date-range="flex">Tanggal fleksibel</button>
                                    </div>
                                    <div class="calendar-preview" id="calendarPreview"></div>
                                    <div class="native-date-fields">
                                        <div>
                                            <label for="searchCheckin">Check in</label>
                                            <input
                                                type="date"
                                                name="checkin"
                                                id="searchCheckin"
                                                value="<?php echo h($checkin); ?>"
                                            >
                                        </div>
                                        <div>
                                            <label for="searchCheckout">Check out</label>
                                            <input
                                                type="date"
                                                name="checkout"
                                                id="searchCheckout"
                                                value="<?php echo h($checkout); ?>"
                                            >
                                        </div>
                                    </div>
                                    <div class="guest-hint">Setel tanggal manual atau gunakan tombol cepat untuk inspirasi perjalanan.</div>
                                </div>
                            </div>

                            <div class="pill-divider"></div>

                            <div class="pill-section pill-section-guests" data-popover-target="guestPopover">
                                <div class="pill-label">Tamu</div>
                                <div
                                    class="pill-value pill-value-compact <?php echo $guestCount === '' ? 'pill-placeholder' : ''; ?>"
                                    id="guestSummary"
                                    data-empty-text="Tambahkan tamu"
                                >
                                    <?php echo h($guestSummaryText); ?>
                                </div>
                                <input
                                    type="hidden"
                                    name="guests"
                                    id="searchGuests"
                                    value="<?php echo h($guestCount); ?>"
                                    data-initial-total="<?php echo (int) $initialGuestTotal; ?>"
                                >
                                <div class="pill-popover" id="guestPopover">
                                    <div class="guest-row" data-guest-type="adult">
                                        <div class="guest-info">
                                            <div class="guest-title">Dewasa</div>
                                            <div class="guest-sub">Usia 13 tahun ke atas</div>
                                        </div>
                                        <div class="guest-counter">
                                            <button type="button" class="counter-btn" data-action="minus">-</button>
                                            <span class="counter-value">1</span>
                                            <button type="button" class="counter-btn" data-action="plus">+</button>
                                        </div>
                                    </div>
                                    <div class="guest-row" data-guest-type="child">
                                        <div class="guest-info">
                                            <div class="guest-title">Anak-anak</div>
                                            <div class="guest-sub">Usia 2-12</div>
                                        </div>
                                        <div class="guest-counter">
                                            <button type="button" class="counter-btn" data-action="minus">-</button>
                                            <span class="counter-value">0</span>
                                            <button type="button" class="counter-btn" data-action="plus">+</button>
                                        </div>
                                    </div>
                                    <div class="guest-row" data-guest-type="toddler">
                                        <div class="guest-info">
                                            <div class="guest-title">Balita</div>
                                            <div class="guest-sub">Di bawah 2 tahun</div>
                                        </div>
                                        <div class="guest-counter">
                                            <button type="button" class="counter-btn" data-action="minus">-</button>
                                            <span class="counter-value">0</span>
                                            <button type="button" class="counter-btn" data-action="plus">+</button>
                                        </div>
                                    </div>
                                    <div class="guest-row" data-guest-type="pet">
                                        <div class="guest-info">
                                            <div class="guest-title">Hewan peliharaan</div>
                                            <div class="guest-sub">Membawa hewan pemandu?</div>
                                        </div>
                                        <div class="guest-counter">
                                            <button type="button" class="counter-btn" data-action="minus">-</button>
                                            <span class="counter-value">0</span>
                                            <button type="button" class="counter-btn" data-action="plus">+</button>
                                        </div>
                                    </div>
                                    <div class="guest-presets">
                                        <button type="button" class="guest-preset" data-guest-preset='{"adult":1}'>Solo trip</button>
                                        <button type="button" class="guest-preset" data-guest-preset='{"adult":2,"child":1}'>Keluarga kecil</button>
                                        <button type="button" class="guest-preset" data-guest-preset='{"adult":4,"child":2,"pet":1}'>Staycation grup</button>
                                    </div>
                                    <div class="guest-hint">Total tamu maksimum 32 orang. Hewan tidak dihitung sebagai tamu.</div>
                                </div>
                            </div>

                            <div class="pill-search-btn-wrap">
                                <button type="submit" class="pill-search-btn">
                                    <div class="pill-search-icon">🔍</div>
                                    <span>Cari</span>
                                </button>
                            </div>
                        </div>
                        <div class="search-overlay" id="searchOverlay"></div>
                    </form>
                </div>
            </section>

            <!-- PUBLIC LISTINGS -->
            <section>
                <div class="section-header">
                    <h2 class="section-title">
                        Popular stays near you
                    </h2>
                    <span class="section-link">View all</span>
                </div>

                <?php if (!empty($dbError)): ?>
                    <div class="empty-text">Unable to load listings: <?php echo h($dbError); ?></div>
                <?php elseif (empty($publicListings)): ?>
                    <div class="empty-text">
                        No published stays yet. Once hosts publish their listings, they will appear here.
                    </div>
                <?php else: ?>
                    <div class="stays-grid">
                        <?php foreach ($publicListings as $row): ?>
                            <?php
                            $id        = (int)$row['id'];
                            $title     = $row['title'] ?: 'Untitled listing';
                            $city      = $row['city'] ?: ($row['location_city'] ?? '');
                            $country   = $row['country'] ?: ($row['location_country'] ?? '');
                            $location  = trim($city . ', ' . $country, ', ');
                            $nightly   = $row['nightly_price'];
                            $nightlyS  = $row['nightly_price_strike'];
                            $hasDisc   = !empty($row['has_discount']);
                            $labelDisc = $row['discount_label'] ?: '';
                            ?>
                            <?php
                            $coverSrc = cover_image_for($row);
                            $coverAlt = $title . ' cover photo';
                            ?>
                            <a class="stay-card" href="listing-room.php?id=<?php echo $id; ?>">
                                <div class="stay-image">
                                    <img src="<?php echo h($coverSrc); ?>" alt="<?php echo h($coverAlt); ?>">
                                    <div class="stay-tag">GUEST FAVORITE</div>
                                </div>
                                <div class="stay-body">
                                    <div class="stay-location">
                                        <?php echo $location !== '' ? h($location) : 'Location not set'; ?>
                                    </div>
                                    <div class="stay-meta-small"><?php echo h($title); ?></div>

                                    <div class="stay-price-row">
                                        <?php if ($nightly !== null): ?>
                                            <span class="stay-price-main">
                                                Rp <?php echo number_format((float)$nightly, 0, ',', '.'); ?>
                                                <span>/ night</span>
                                            </span>
                                            <?php if ($nightlyS !== null && (float)$nightlyS > (float)$nightly): ?>
                                                <span class="stay-price-strike">
                                                    Rp <?php echo number_format((float)$nightlyS, 0, ',', '.'); ?>
                                                </span>
                                                <?php
                                                $discPct = round((($nightlyS - $nightly) / $nightlyS) * 100);
                                                ?>
                                                <span class="stay-discount-badge">
                                                    <img src="assets/icons/blue-fire.gif" alt="discount">
                                                    <?php echo $discPct; ?>% OFF
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="stay-price-main">
                                                <span>Price not set</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($meId > 0): ?>
                <!-- OWNER'S OWN LISTINGS (DRAFT / IN_REVIEW / REJECTED) -->
                <hr class="section-divider">

                <section>
                    <div class="section-header">
                        <h2 class="section-title">Your listings (only visible to you)</h2>
                    </div>

                    <?php if (empty($myListings)): ?>
                        <div class="empty-text">
                            You don't have any draft or in-review listings yet.
                        </div>
                    <?php else: ?>
                        <div class="stays-grid">
                            <?php foreach ($myListings as $row): ?>
                                <?php
                                $id        = (int)$row['id'];
                                $title     = $row['title'] ?: 'Untitled listing';
                                $city      = $row['city'] ?: ($row['location_city'] ?? '');
                                $country   = $row['country'] ?: ($row['location_country'] ?? '');
                                $location  = trim($city . ', ' . $country, ', ');
                                $nightly   = $row['nightly_price'];
                                $nightlyS  = $row['nightly_price_strike'];
                                $status    = $row['status'] ?? 'draft';

                                if ($status === 'in_review')      $tag = 'IN REVIEW · ID #' . $id;
                                elseif ($status === 'rejected')  $tag = 'REJECTED · ID #' . $id;
                                else                              $tag = 'DRAFT · ID #' . $id;
                                $coverSrc = cover_image_for($row);
                                $coverAlt = $title . ' cover photo';
                                ?>
                                <article class="stay-card">
                                    <div class="stay-image">
                                        <img src="<?php echo h($coverSrc); ?>" alt="<?php echo h($coverAlt); ?>">
                                        <div class="stay-tag"><?php echo h($tag); ?></div>
                                    </div>
                                    <div class="stay-body">
                                        <div class="stay-location">
                                            <?php echo $location !== '' ? h($location) : 'Location not set'; ?>
                                        </div>
                                        <div class="stay-meta-small"><?php echo h($title); ?></div>

                                        <div class="stay-price-row">
                                            <?php if ($nightly !== null): ?>
                                                <span class="stay-price-main">
                                                    Rp <?php echo number_format((float)$nightly, 0, ',', '.'); ?>
                                                    <span>/ night</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="stay-price-main">
                                                    <span>Set a nightly price</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="stay-owner-note">
                                            Only you can see this card here. Admin must approve before it becomes public.
                                        </div>

                                        <div class="stay-actions">
                                            <button
                                                type="button"
                                                class="btn-edit"
                                                onclick="window.location.href='host-listing-editor.php?id=<?php echo $id; ?>';"
                                            >
                                                Edit listing
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.remove('no-js');
    document.body.classList.add('enhanced-search');

    const overlay = document.getElementById('searchOverlay');
    const popovers = document.querySelectorAll('.pill-popover');
    const sections = document.querySelectorAll('[data-popover-target]');
    let activePopoverId = null;

    function closePopovers() {
        popovers.forEach(pop => pop.classList.remove('is-visible'));
        sections.forEach(section => section.classList.remove('is-active'));
        overlay?.classList.remove('is-visible');
        activePopoverId = null;
    }

    function openPopover(id) {
        const popover = document.getElementById(id);
        if (!popover) {
            return;
        }
        if (activePopoverId === id) {
            return;
        }
        closePopovers();
        popover.classList.add('is-visible');
        document.querySelectorAll(`[data-popover-target="${id}"]`).forEach(section => {
            section.classList.add('is-active');
        });
        overlay?.classList.add('is-visible');
        activePopoverId = id;
    }

    sections.forEach(section => {
        const targetId = section.dataset.popoverTarget;
        if (!targetId) {
            return;
        }
        section.addEventListener('click', () => openPopover(targetId));
        section.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', () => openPopover(targetId));
        });
    });

    overlay?.addEventListener('click', closePopovers);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePopovers();
        }
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('.pill-popover') || event.target.closest('[data-popover-target]')) {
            return;
        }
        closePopovers();
    });

    const whereInput = document.getElementById('searchWhere');
    document.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', () => {
            const value = item.dataset.value || '';
            if (whereInput) {
                whereInput.value = value;
                whereInput.focus();
            }
            closePopovers();
        });
    });

    const checkinInput = document.getElementById('searchCheckin');
    const checkoutInput = document.getElementById('searchCheckout');
    const dateSummary = document.getElementById('dateSummary');

    function formatDateLabel(value) {
        if (!value) {
            return '';
        }
        const dt = new Date(value);
        if (Number.isNaN(dt.getTime())) {
            return '';
        }
        return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }

    function updateDateSummary(customText = null) {
        if (!dateSummary) {
            return;
        }
        if (customText) {
            dateSummary.textContent = customText;
            dateSummary.classList.remove('pill-placeholder');
            return;
        }
        const checkinValue = checkinInput?.value || '';
        const checkoutValue = checkoutInput?.value || '';
        if (!checkinValue && !checkoutValue) {
            const emptyText = dateSummary.dataset.emptyText || 'Tambahkan tanggal';
            dateSummary.textContent = emptyText;
            dateSummary.classList.add('pill-placeholder');
            return;
        }
        let label = '';
        if (checkinValue && checkoutValue) {
            label = `${formatDateLabel(checkinValue)} - ${formatDateLabel(checkoutValue)}`;
        } else if (checkinValue) {
            label = formatDateLabel(checkinValue);
        } else if (checkoutValue) {
            label = formatDateLabel(checkoutValue);
        }
        dateSummary.textContent = label.trim();
        dateSummary.classList.remove('pill-placeholder');
    }

    function syncCheckoutMin() {
        if (!checkinInput || !checkoutInput) {
            return;
        }
        checkoutInput.min = checkinInput.value || '';
        if (
            checkinInput.value &&
            checkoutInput.value &&
            checkoutInput.value <= checkinInput.value
        ) {
            checkoutInput.value = '';
        }
    }

    function addDays(baseDate, days) {
        const next = new Date(baseDate);
        next.setDate(next.getDate() + days);
        return next;
    }

    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function setDateRange(startDate, endDate) {
        if (checkinInput) {
            checkinInput.value = formatDateForInput(startDate);
        }
        if (checkoutInput) {
            checkoutInput.value = formatDateForInput(endDate);
        }
    }

    function parseIsoDate(value) {
        if (!value || typeof value !== 'string') {
            return null;
        }
        const [year, month, day] = value.split('-').map(part => parseInt(part, 10));
        if (!year || !month || !day) {
            return null;
        }
        const date = new Date(year, month - 1, day);
        if (
            date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day
        ) {
            return null;
        }
        date.setHours(0, 0, 0, 0);
        return date;
    }

    function upcomingWeekendRange() {
        const today = new Date();
        const day = today.getDay();
        const daysUntilFriday = (5 - day + 7) % 7;
        const start = addDays(today, daysUntilFriday || 7);
        const end = addDays(start, 2);
        return [start, end];
    }

    const dateRangeHandlers = {
        weekend: () => {
            const [start, end] = upcomingWeekendRange();
            setDateRange(start, end);
        },
        'next-week': () => {
            const today = new Date();
            const day = today.getDay();
            const daysUntilMonday = (1 - day + 7) % 7 || 7;
            const start = addDays(today, daysUntilMonday);
            const end = addDays(start, 4);
            setDateRange(start, end);
        },
        'next-month': () => {
            const today = new Date();
            const start = new Date(today.getFullYear(), today.getMonth() + 1, 5);
            const end = addDays(start, 5);
            setDateRange(start, end);
        },
        flex: () => {
            if (checkinInput) checkinInput.value = '';
            if (checkoutInput) checkoutInput.value = '';
            updateDateSummary('Tanggal fleksibel');
        }
    };

    document.querySelectorAll('[data-date-range]').forEach(button => {
        button.addEventListener('click', () => {
            const handler = dateRangeHandlers[button.dataset.dateRange];
            if (handler) {
                handler();
                syncCheckoutMin();
                if (button.dataset.dateRange !== 'flex') {
                    updateDateSummary();
                }
                refreshCalendarPreview();
            }
        });
    });

    if (checkinInput && checkoutInput) {
        syncCheckoutMin();
        checkinInput.addEventListener('change', () => {
            syncCheckoutMin();
            updateDateSummary();
            refreshCalendarPreview();
        });
        checkoutInput.addEventListener('change', () => {
            updateDateSummary();
            refreshCalendarPreview();
        });
        updateDateSummary();
    }

    const calendarPreview = document.getElementById('calendarPreview');
    const calendarMonthsToShow = 2;

    function refreshCalendarPreview() {
        if (!calendarPreview) {
            return;
        }
        renderCalendarPreview(calendarPreview, calendarMonthsToShow);
    }

    function renderCalendarPreview(root, monthsToShow) {
        const dayLabels = ['Min', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const calendarStart = new Date();
        calendarStart.setDate(1);
        root.innerHTML = '';
        const checkinValue = checkinInput?.value || '';
        const checkoutValue = checkoutInput?.value || '';
        const checkinDate = parseIsoDate(checkinValue);
        const checkoutDate = parseIsoDate(checkoutValue);
        for (let i = 0; i < monthsToShow; i += 1) {
            const monthDate = new Date(calendarStart.getFullYear(), calendarStart.getMonth() + i, 1);
            const daysInMonth = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();
            const startDay = monthDate.getDay();
            let html = '<div class="calendar-month">';
            html += `<h4>${monthDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}</h4>`;
            html += '<div class="calendar-week">';
            html += dayLabels.map(label => `<span>${label}</span>`).join('');
            html += '</div><div class="calendar-days">';
            for (let blank = 0; blank < startDay; blank += 1) {
                html += '<span class="calendar-day is-muted">&nbsp;</span>';
            }
            for (let day = 1; day <= daysInMonth; day += 1) {
                const cellDate = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
                cellDate.setHours(0, 0, 0, 0);
                const isoValue = formatDateForInput(cellDate);
                const classes = ['calendar-day'];
                if (cellDate < today) {
                    classes.push('is-muted');
                } else {
                    classes.push('is-clickable');
                }
                if (checkinValue && isoValue === checkinValue) {
                    classes.push('is-selected');
                }
                if (checkoutValue && isoValue === checkoutValue) {
                    classes.push('is-selected');
                }
                if (
                    checkinDate &&
                    checkoutDate &&
                    cellDate > checkinDate &&
                    cellDate < checkoutDate
                ) {
                    classes.push('is-in-range');
                }
                html += `<span class="${classes.join(' ')}" data-date="${isoValue}">${day}</span>`;
            }
            html += '</div></div>';
            root.insertAdjacentHTML('beforeend', html);
        }

        root.querySelectorAll('.calendar-day[data-date]:not(.is-muted)').forEach(dayEl => {
            dayEl.addEventListener('click', () => handleCalendarClick(dayEl.dataset.date));
        });
    }

    function handleCalendarClick(dateValue) {
        if (!checkinInput || !checkoutInput || !dateValue) {
            return;
        }
        const currentCheckin = checkinInput.value;
        const currentCheckout = checkoutInput.value;
        if (!currentCheckin || (currentCheckin && currentCheckout)) {
            checkinInput.value = dateValue;
            checkoutInput.value = '';
        } else if (dateValue < currentCheckin) {
            checkinInput.value = dateValue;
            checkoutInput.value = '';
        } else if (dateValue === currentCheckin) {
            checkoutInput.value = '';
        } else {
            checkoutInput.value = dateValue;
        }
        updateDateSummary();
        syncCheckoutMin();
        refreshCalendarPreview();
    }

    refreshCalendarPreview();

    const guestInput = document.getElementById('searchGuests');
    const guestSummary = document.getElementById('guestSummary');
    const initialTotal = parseInt(guestInput?.dataset.initialTotal || '0', 10) || 0;
    const guestState = {
        adult: initialTotal > 0 ? initialTotal : 0,
        child: 0,
        toddler: 0,
        pet: 0,
    };

    function getTotalGuests() {
        return guestState.adult + guestState.child + guestState.toddler;
    }

    function updateGuestSummary() {
        if (!guestSummary || !guestInput) {
            return;
        }
        const totalGuests = getTotalGuests();
        guestInput.value = totalGuests > 0 ? String(totalGuests) : '';
        if (totalGuests === 0 && guestState.pet === 0) {
            const emptyText = guestSummary.dataset.emptyText || 'Tambahkan tamu';
            guestSummary.textContent = emptyText;
            guestSummary.classList.add('pill-placeholder');
            return;
        }
        const guestLabel = totalGuests > 0 ? `${totalGuests} tamu` : '';
        const petLabel = guestState.pet > 0 ? `${guestState.pet} hewan` : '';
        guestSummary.textContent = [guestLabel, petLabel].filter(Boolean).join(', ');
        guestSummary.classList.remove('pill-placeholder');
    }

    function updateGuestControls() {
        document.querySelectorAll('[data-guest-type]').forEach(row => {
            const type = row.dataset.guestType;
            const valueEl = row.querySelector('.counter-value');
            const minusBtn = row.querySelector('[data-action="minus"]');
            if (!type || !valueEl || !minusBtn) {
                return;
            }
            valueEl.textContent = guestState[type];
            minusBtn.disabled = guestState[type] === 0;
        });
        updateGuestSummary();
    }

    document.querySelectorAll('[data-guest-type]').forEach(row => {
        const type = row.dataset.guestType;
        if (!type) {
            return;
        }
        row.querySelectorAll('.counter-btn').forEach(button => {
            button.addEventListener('click', () => {
                const action = button.dataset.action;
                if (!action) {
                    return;
                }
                if (action === 'minus' && guestState[type] > 0) {
                    guestState[type] -= 1;
                }
                if (action === 'plus') {
                    if (type === 'pet') {
                        guestState.pet += 1;
                    } else if (getTotalGuests() < 32) {
                        guestState[type] += 1;
                    }
                }
                updateGuestControls();
            });
        });
    });

    document.querySelectorAll('[data-guest-preset]').forEach(button => {
        button.addEventListener('click', () => {
            try {
                const preset = JSON.parse(button.dataset.guestPreset || '{}');
                ['adult', 'child', 'toddler', 'pet'].forEach(key => {
                    guestState[key] = Math.max(0, preset[key] || 0);
                });
                const totalGuests = getTotalGuests();
                if (totalGuests > 32) {
                    const overflow = totalGuests - 32;
                    guestState.adult = Math.max(0, guestState.adult - overflow);
                }
                updateGuestControls();
            } catch (error) {
                console.error('Invalid guest preset', error);
            }
        });
    });

    updateGuestControls();
});
</script>
</body>
</html>
