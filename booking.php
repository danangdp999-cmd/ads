<?php
// booking.php — review and confirm stay

session_start();

require_once __DIR__ . '/ogo-api/config.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalize_local_path(string $value): string
{
    $normalized = str_replace('\\', '/', $value);
    $normalized = preg_replace('#/{2,}#', '/', $normalized);

    while (strpos($normalized, '../') === 0) {
        $normalized = substr($normalized, 3);
    }

    return ltrim($normalized, './');
}

function encode_path_segments(string $path): string
{
    $segments = explode('/', $path);
    $encoded  = [];

    foreach ($segments as $segment) {
        if ($segment === '') {
            $encoded[] = '';
            continue;
        }

        $decoded    = rawurldecode($segment);
        $encoded[]  = rawurlencode($decoded);
    }

    return implode('/', $encoded);
}

function public_path_from(string $value): string
{
    $trimmed = ltrim($value, '/');
    $encoded = encode_path_segments($trimmed);

    if ($encoded === '') {
        return '/';
    }

    return '/' . $encoded;
}

function cover_image_url(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $trimmed = trim((string) $value);
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
        return public_path_from($normalized);
    }

    if (strpos($normalized, 'listing-photos/') === 0) {
        return public_path_from($normalized);
    }

    return '';
}

function parse_date(?string $value): ?DateTimeImmutable
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Exception $e) {
        return null;
    }
}

function compute_nights(?DateTimeImmutable $checkin, ?DateTimeImmutable $checkout): int
{
    if (!$checkin || !$checkout) {
        return 0;
    }

    if ($checkout <= $checkin) {
        return 0;
    }

    return (int) $checkin->diff($checkout)->format('%a');
}

$listingId = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
$checkinRaw = $_GET['checkin'] ?? '';
$checkoutRaw = $_GET['checkout'] ?? '';
$guestCount = isset($_GET['guests']) ? (int) $_GET['guests'] : 1;
$guestCount = $guestCount > 0 ? $guestCount : 1;

$checkinDate = parse_date($checkinRaw);
$checkoutDate = parse_date($checkoutRaw);
$nights = compute_nights($checkinDate, $checkoutDate);

$errors = [];
if ($listingId <= 0) {
    $errors[] = 'Listing tidak ditemukan.';
}
if (!$checkinDate) {
    $errors[] = 'Tanggal check-in tidak valid.';
}
if (!$checkoutDate) {
    $errors[] = 'Tanggal check-out tidak valid.';
}
if ($checkinDate && $checkoutDate && $checkoutDate <= $checkinDate) {
    $errors[] = 'Check-out harus setelah check-in.';
}
if ($nights <= 0) {
    $errors[] = 'Silakan pilih minimal satu malam menginap.';
}

$listing = null;
$pricingNote = '';

if (empty($errors)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $stmt = $pdo->prepare('SELECT id, title, city, country, nightly_price, currency_code, guests, cover_photo_url FROM simple_listings WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $listingId]);
        $listing = $stmt->fetch();

        if (!$listing) {
            $errors[] = 'Listing tidak ditemukan atau sudah tidak tersedia.';
        }
    } catch (Exception $e) {
        $errors[] = 'Gagal memuat data listing. Silakan coba lagi.';
    }
}

$totalPrice = null;
$nightlyPrice = null;
$currencyCode = 'IDR';
$coverUrl = '';
$locationText = '';
$titleText = '—';
$guestCapacity = null;

if ($listing) {
    $titleText = $listing['title'] ?: 'Untitled listing';
    $city = $listing['city'] ?? '';
    $country = $listing['country'] ?? '';
    $locationText = trim($city . ', ' . $country, ', ');

    $nightlyPrice = isset($listing['nightly_price']) ? (float) $listing['nightly_price'] : null;
    $currencyCode = $listing['currency_code'] ?: 'IDR';
    $guestCapacity = isset($listing['guests']) ? (int) $listing['guests'] : null;
    $coverUrl = cover_image_url($listing['cover_photo_url']);

    if ($nightlyPrice !== null && $nights > 0) {
        $totalPrice = $nightlyPrice * $nights;
    } else {
        $pricingNote = 'Harga belum ditentukan. Hubungi host untuk mendapatkan penawaran.';
    }
}

function format_currency(?float $value, string $currency): string
{
    if ($value === null) {
        return '—';
    }

    if ($currency === 'IDR') {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    return number_format($value, 2) . ' ' . $currency;
}

function format_date(?DateTimeImmutable $date): string
{
    if (!$date) {
        return '—';
    }

    return $date->format('d M Y');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi pemesanan · OGORooms</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body:#f7f7f8;
            --bg-card:#ffffff;
            --text-main:#111827;
            --text-muted:#6b7280;
            --accent:#b2743b;
            --accent-soft:#f6e5d6;
            --border-subtle:#e5e7eb;
            --shadow-soft:0 18px 40px rgba(15,23,42,0.12);
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:"Plus Jakarta Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:var(--bg-body);
            color:var(--text-main);
        }
        a { color:inherit;text-decoration:none; }
        .page { min-height:100vh; display:flex; flex-direction:column; }
        .nav {
            position:sticky;top:0;z-index:30;
            backdrop-filter:blur(16px);
            background:rgba(255,255,255,0.96);
            border-bottom:1px solid rgba(229,231,235,0.85);
        }
        .nav-inner {
            max-width:1240px;margin:0 auto;padding:10px 24px;
            display:flex;align-items:center;justify-content:space-between;gap:16px;
        }
        .nav-left { display:flex;align-items:center;gap:10px; }
        .nav-logo-circle {
            width:34px;height:34px;border-radius:50%;background:var(--accent);
            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;letter-spacing:0.04em;font-size:14px;
        }
        .nav-brand-text { display:flex;flex-direction:column;line-height:1.1; }
        .nav-brand-title { font-size:17px;font-weight:700;letter-spacing:0.08em; }
        .nav-brand-sub { font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.18em; }
        .nav-right { display:flex;align-items:center;gap:12px;font-size:14px; }
        .nav-link { padding:6px 10px;border-radius:999px;cursor:pointer;color:#374151; }
        .nav-link:hover { background:#f3f4f6; }
        .main { flex:1; }
        .shell { max-width:1240px;margin:0 auto;padding:24px 24px 40px; }
        .page-header { display:flex;flex-direction:column;gap:6px;margin-bottom:20px; }
        .page-kicker { font-size:12px;text-transform:uppercase;letter-spacing:0.18em;color:var(--text-muted); }
        .page-title { font-size:26px;font-weight:700; }
        .page-sub { font-size:13px;color:var(--text-muted); }
        .layout { display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,1fr);gap:24px; }
        @media(max-width:900px){ .layout { grid-template-columns:1fr; } }
        .stay-card {
            background:var(--bg-card);
            border-radius:20px;
            border:1px solid rgba(229,231,235,0.9);
            box-shadow:var(--shadow-soft);
            overflow:hidden;
        }
        .stay-cover { position:relative;height:240px;background:#e5e7eb; }
        .stay-cover img { width:100%;height:100%;object-fit:cover;display:block; }
        .stay-body { padding:20px 22px;display:flex;flex-direction:column;gap:10px; }
        .stay-location { font-size:14px;color:var(--text-muted); }
        .stay-title { font-size:20px;font-weight:600; }
        .stay-meta { display:flex;flex-wrap:wrap;gap:10px;color:var(--text-muted);font-size:12px; }
        .summary-card {
            background:var(--bg-card);
            border-radius:18px;
            border:1px solid rgba(229,231,235,0.9);
            box-shadow:var(--shadow-soft);
            padding:20px 22px;
            display:flex;
            flex-direction:column;
            gap:16px;
        }
        .summary-row { display:flex;justify-content:space-between;gap:12px;font-size:14px; }
        .summary-label { color:var(--text-muted); }
        .divider { border:none;border-top:1px dashed #d1d5db; }
        .total-line { display:flex;justify-content:space-between;align-items:center;font-size:16px;font-weight:600; }
        .pill {
            display:inline-flex;align-items:center;gap:6px;
            border-radius:999px;background:var(--accent-soft);
            color:var(--accent);padding:6px 14px;font-size:12px;font-weight:500;
        }
        .form-card {
            background:var(--bg-card);
            border-radius:18px;
            border:1px solid rgba(229,231,235,0.9);
            box-shadow:var(--shadow-soft);
            padding:20px 22px;
            display:flex;
            flex-direction:column;
            gap:14px;
        }
        .form-field { display:flex;flex-direction:column;gap:6px; }
        .form-field label {
            font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);
        }
        .form-field input, .form-field textarea {
            border-radius:12px;border:1px solid var(--border-subtle);
            padding:10px 12px;font-size:14px;font-family:inherit;resize:vertical;
        }
        .btn-primary {
            border:none;border-radius:999px;background:var(--accent);color:#fff;
            padding:14px 20px;font-size:15px;font-weight:600;cursor:pointer;
            box-shadow:0 12px 32px rgba(178,116,59,0.45);
            transition:transform 0.15s ease;
        }
        .btn-primary:hover { transform:translateY(-1px); }
        .btn-primary:disabled { opacity:0.6;cursor:not-allowed;box-shadow:none;transform:none; }
        .alert {
            padding:12px 16px;border-radius:14px;font-size:13px;line-height:1.5;
        }
        .alert-error { background:#fee2e2;color:#b91c1c;border:1px solid #fecaca; }
        .alert-success { background:#dcfce7;color:#166534;border:1px solid #bbf7d0; }
    </style>
</head>
<body>
<div class="page">
    <header class="nav">
        <div class="nav-inner">
            <div class="nav-left">
                <div class="nav-logo-circle">OG</div>
                <div class="nav-brand-text">
                    <span class="nav-brand-title">OGOROOMS</span>
                    <span class="nav-brand-sub">BOOKING REVIEW</span>
                </div>
            </div>
            <div class="nav-right">
                <a href="index.php" class="nav-link">Beranda</a>
                <a href="search-results.php" class="nav-link">Cari lagi</a>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="shell">
            <div class="page-header">
                <span class="page-kicker">Langkah terakhir</span>
                <h1 class="page-title">Konfirmasi pemesanan Anda</h1>
                <p class="page-sub">Tinjau detail menginap sebelum mengirim permintaan ke host.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom:20px;">
                    <?php echo implode('<br>', array_map('h', $errors)); ?>
                </div>
            <?php endif; ?>

            <div class="layout">
                <div class="stay-card">
                    <div class="stay-cover">
                        <img src="<?php echo h($coverUrl !== '' ? $coverUrl : 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1600'); ?>" alt="<?php echo h($titleText); ?>">
                    </div>
                    <div class="stay-body">
                        <div class="stay-location"><?php echo $locationText !== '' ? h($locationText) : 'Lokasi belum diatur'; ?></div>
                        <div class="stay-title"><?php echo h($titleText); ?></div>
                        <div class="stay-meta">
                            <span>👥 <?php echo $guestCapacity ? h($guestCapacity . ' tamu') : 'Tamu fleksibel'; ?></span>
                            <span>🗓️ <?php echo h(max($nights, 0)) . ' malam'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="pill">Pesanan Anda</div>
                    <div class="summary-row">
                        <span class="summary-label">Check-in</span>
                        <span><?php echo h(format_date($checkinDate)); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Check-out</span>
                        <span><?php echo h(format_date($checkoutDate)); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Durasi</span>
                        <span><?php echo h($nights . ' malam'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Jumlah tamu</span>
                        <span><?php echo h($guestCount . ' orang'); ?></span>
                    </div>
                    <hr class="divider">
                    <div class="summary-row">
                        <span class="summary-label">Harga per malam</span>
                        <span><?php echo h(format_currency($nightlyPrice, $currencyCode)); ?></span>
                    </div>
                    <div class="total-line">
                        <span>Total estimasi</span>
                        <span><?php echo h(format_currency($totalPrice, $currencyCode)); ?></span>
                    </div>
                    <?php if ($pricingNote !== ''): ?>
                        <div class="alert alert-error" style="margin:0;">
                            <?php echo h($pricingNote); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top:24px;" class="form-card">
                <div class="pill">Detail tamu utama</div>
                <div id="bookingMessage" class="alert alert-success" style="display:none;">
                    Permintaan booking Anda sudah dikirim ke host. Mereka akan menghubungi Anda melalui email.
                </div>
                <form id="bookingForm">
                    <div class="form-field">
                        <label for="guestName">Nama lengkap</label>
                        <input type="text" id="guestName" name="guestName" placeholder="Nama Anda" required>
                    </div>
                    <div class="form-field">
                        <label for="guestEmail">Email</label>
                        <input type="email" id="guestEmail" name="guestEmail" placeholder="email@contoh.com" required>
                    </div>
                    <div class="form-field">
                        <label for="guestPhone">Nomor telepon</label>
                        <input type="tel" id="guestPhone" name="guestPhone" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="form-field">
                        <label for="guestNotes">Catatan untuk host (opsional)</label>
                        <textarea id="guestNotes" name="guestNotes" rows="4" placeholder="Beritahu host kebutuhan khusus Anda..."></textarea>
                    </div>
                    <button type="submit" class="btn-primary" <?php echo $totalPrice === null ? 'disabled' : ''; ?>>Kirim permintaan booking</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
  const form = document.getElementById('bookingForm');
  const message = document.getElementById('bookingMessage');
  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (message) {
        message.style.display = 'block';
        window.scrollTo({ top: message.offsetTop - 80, behavior: 'smooth' });
      }
    });
  }
</script>
</body>
</html>
