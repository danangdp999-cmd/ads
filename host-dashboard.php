<?php
// host-dashboard.php — OGORooms Hosting Dashboard

session_start();

// Wajib login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$hostId    = (int)$_SESSION['user_id'];
$hostEmail = $_SESSION['user_email'] ?? '';

// pakai config DB
require_once __DIR__ . '/ogo-api/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $stmt = $pdo->prepare(
        'SELECT id,
                host_type,
                title,
                city,
                country,
                nightly_price,
                nightly_price_strike,
                weekend_price,
                weekend_price_strike,
                has_discount,
                discount_label,
                status,
                created_at
         FROM simple_listings
         WHERE host_user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$hostId]);
    $listings = $stmt->fetchAll();

} catch (Exception $e) {
    $listings = [];
    $loadError = $e->getMessage();
}

// hitung summary status
$statusCounts = [
    'draft'      => 0,
    'in_review'  => 0,
    'published'  => 0,
    'rejected'   => 0,
];
foreach ($listings as $row) {
    $s = $row['status'] ?? 'draft';
    if (isset($statusCounts[$s])) {
        $statusCounts[$s]++;
    }
}
$totalListings = count($listings);

function status_label_class(string $status): array {
    switch ($status) {
        case 'published':
            return ['Published', 'status-pill-published'];
        case 'in_review':
            return ['In review', 'status-pill-review'];
        case 'rejected':
            return ['Rejected', 'status-pill-rejected'];
        default:
            return ['Draft', 'status-pill-draft'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OGORooms – Hosting dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f7f7f8;
            --bg-card: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #b2743b;
            --accent-soft: #f6e5d6;
            --border-subtle: #e5e7eb;
            --pill-bg: #f3f4f6;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-xl: 999px;
            --radius-lg: 24px;
        }

        * { box-sizing:border-box; }

        body {
            margin:0;
            font-family:"Plus Jakarta Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:var(--bg-body);
            color:var(--text-main);
        }

        a { color:inherit; text-decoration:none; }

        .page {
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }

        /* NAVBAR (sama gaya dengan signup/login) */
        .nav {
            position:sticky;
            top:0;
            z-index:40;
            backdrop-filter:blur(16px);
            background:rgba(255,255,255,0.95);
            border-bottom:1px solid rgba(229,231,235,0.8);
        }
        .nav-inner {
            max-width:1240px;
            margin:0 auto;
            padding:10px 20px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }
        .nav-left { display:flex;align-items:center;gap:10px; }
        .nav-logo-circle {
            width:34px;height:34px;border-radius:50%;
            background:var(--accent);
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:700;letter-spacing:0.04em;font-size:14px;
        }
        .nav-brand-text { display:flex;flex-direction:column;line-height:1.1; }
        .nav-brand-title { font-size:17px;font-weight:700;letter-spacing:0.08em; }
        .nav-brand-sub {
            font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.18em;
        }
        .nav-center {
            display:flex;gap:18px;align-items:center;font-size:14px;font-weight:500;
        }
        .nav-center button {
            border:none;background:transparent;padding:8px 0;cursor:pointer;position:relative;color:#4b5563;
        }
        .nav-center button.active { color:var(--text-main); }
        .nav-center button.active::after{
            content:"";position:absolute;left:0;right:0;bottom:-6px;margin-inline:auto;
            width:18px;height:3px;border-radius:999px;background:var(--accent);
        }
        .nav-right {display:flex;align-items:center;gap:10px;font-size:14px;}
        .nav-link {
            padding:6px 10px;border-radius:var(--radius-xl);cursor:pointer;color:#374151;
        }
        .nav-link:hover {background:#f3f4f6;}
        .nav-pill{
            border-radius:var(--radius-xl);
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

        /* MAIN LAYOUT */
        .main {
            flex:1;
            padding:22px 16px 32px;
        }
        .main-inner {
            max-width:1240px;
            margin:0 auto;
            display:flex;
            flex-direction:column;
            gap:18px;
        }

        .dash-header {
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
        }
        .dash-title-block {
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .dash-title {
            font-size:22px;
            font-weight:700;
        }
        .dash-sub {
            font-size:13px;
            color:var(--text-muted);
        }
        .dash-sub b {
            color:#111827;
        }

        .btn-main {
            border-radius:999px;
            border:none;
            padding:9px 16px;
            font-size:13px;
            font-weight:600;
            cursor:pointer;
            background:var(--accent);
            color:#fff;
            display:flex;
            align-items:center;
            gap:6px;
            box-shadow:0 12px 30px rgba(178,116,59,0.45);
        }
        .btn-main span.icon {
            width:18px;height:18px;border-radius:999px;
            background:#fff2e7;color:#b45309;
            display:flex;align-items:center;justify-content:center;
            font-size:13px;
        }

        /* SUMMARY CARDS */
        .summary-row {
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:12px;
        }
        @media(max-width:900px){
            .summary-row { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media(max-width:600px){
            .summary-row { grid-template-columns:repeat(1,minmax(0,1fr)); }
        }
        .summary-card {
            background:var(--bg-card);
            border-radius:18px;
            border:1px solid rgba(229,231,235,0.9);
            padding:10px 12px;
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .summary-label {
            font-size:11px;
            color:var(--text-muted);
        }
        .summary-value {
            font-size:17px;
            font-weight:700;
        }
        .summary-pill {
            align-self:flex-start;
            margin-top:2px;
            font-size:11px;
            padding:3px 8px;
            border-radius:999px;
            background:#f3f4f6;
            color:#4b5563;
        }

        /* TABLE LISTINGS */
        .card-listings {
            margin-top:4px;
            background:var(--bg-card);
            border-radius:24px;
            border:1px solid rgba(229,231,235,0.95);
            box-shadow:0 18px 40px rgba(148,163,184,0.25);
            padding:16px 18px 12px;
        }
        .card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:10px;
        }
        .card-title {
            font-size:15px;
            font-weight:600;
        }
        .card-sub {
            font-size:12px;
            color:var(--text-muted);
        }

        .status-filters {
            display:flex;
            gap:6px;
            flex-wrap:wrap;
        }
        .status-chip {
            border-radius:999px;
            border:1px solid #e5e7eb;
            padding:4px 10px;
            font-size:11px;
            color:#4b5563;
            cursor:pointer;
            background:#f9fafb;
        }
        .status-chip.active {
            background:#111827;
            color:#f9fafb;
            border-color:#111827;
        }

        table.listings-table {
            width:100%;
            border-collapse:collapse;
            margin-top:6px;
            font-size:13px;
        }
        table.listings-table thead {
            background:#f9fafb;
        }
        table.listings-table th,
        table.listings-table td {
            padding:8px 6px;
            text-align:left;
            border-bottom:1px solid #f1f5f9;
        }
        table.listings-table th {
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:0.04em;
            color:#6b7280;
        }
        table.listings-table tbody tr:hover {
            background:#f9fafb;
        }

        .title-main {
            font-weight:600;
            color:#111827;
        }
        .title-sub {
            font-size:11px;
            color:#6b7280;
        }

        .price-main {
            font-weight:600;
        }
        .price-strike {
            font-size:11px;
            color:#9ca3af;
            text-decoration:line-through;
            margin-left:4px;
        }
        .discount-badge {
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-left:6px;
            font-size:11px;
            padding:2px 8px;
            border-radius:999px;
            background:#dbeafe;
            color:#1d4ed8;
        }
        .discount-badge img {
            width:14px;
            height:14px;
            object-fit:contain;
            display:block;
        }

        .status-pill {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:72px;
            padding:4px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:500;
        }
        .status-pill-draft {
            background:#f9fafb;
            color:#4b5563;
            border:1px dashed #d1d5db;
        }
        .status-pill-review {
            background:#fef3c7;
            color:#92400e;
            border:1px solid #fbbf24;
        }
        .status-pill-published {
            background:#dcfce7;
            color:#166534;
            border:1px solid #22c55e;
        }
        .status-pill-rejected {
            background:#fee2e2;
            color:#b91c1c;
            border:1px solid #f97373;
        }

        .actions-cell {
            display:flex;
            gap:6px;
        }
        .btn-sm {
            border-radius:999px;
            border:1px solid #e5e7eb;
            padding:4px 10px;
            font-size:11px;
            background:#fff;
            cursor:pointer;
        }
        .btn-sm-primary {
            border-color:#111827;
            background:#111827;
            color:#f9fafb;
        }
        .empty-state {
            padding:14px 8px 4px;
            font-size:13px;
            color:#6b7280;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- NAV -->
    <header class="nav">
        <div class="nav-inner">
            <div class="nav-left">
                <div class="nav-logo-circle">OG</div>
                <div class="nav-brand-text">
                    <span class="nav-brand-title">OGOROOMS</span>
                    <span class="nav-brand-sub">HOSTING DASHBOARD</span>
                </div>
            </div>

            <div class="nav-center">
                <button type="button" onclick="window.location.href='index.php';">Travel</button>
                <button type="button" class="active">Hosting</button>
            </div>

            <div class="nav-right">
                <span class="nav-link">
                    <?php echo htmlspecialchars($hostEmail ?: 'Host', ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <button class="nav-pill" type="button" onclick="window.location.href='account-settings.php';">
                    <span style="font-size:16px;">🌐</span>
                    <span class="nav-pill-icon">H</span>
                </button>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="main-inner">

            <div class="dash-header">
                <div class="dash-title-block">
                    <div class="dash-title">Hosting overview</div>
                    <div class="dash-sub">
                        You have <b><?php echo $totalListings; ?></b> listing(s) across OGORooms.
                    </div>
                </div>

                <button class="btn-main" type="button" onclick="window.location.href='host-start.php';">
                    <span class="icon">+</span>
                    <span>New listing</span>
                </button>
            </div>

            <!-- SUMMARY SMALL CARDS -->
            <div class="summary-row">
                <div class="summary-card">
                    <div class="summary-label">Total listings</div>
                    <div class="summary-value"><?php echo $totalListings; ?></div>
                    <div class="summary-pill">All statuses</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Draft</div>
                    <div class="summary-value"><?php echo $statusCounts['draft']; ?></div>
                    <div class="summary-pill">Finish & submit</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">In review</div>
                    <div class="summary-value"><?php echo $statusCounts['in_review']; ?></div>
                    <div class="summary-pill">Awaiting approval</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Published</div>
                    <div class="summary-value"><?php echo $statusCounts['published']; ?></div>
                    <div class="summary-pill">Visible to guests</div>
                </div>
            </div>

            <!-- LISTINGS TABLE -->
            <div class="card-listings">
                <div class="card-header">
                    <div>
                        <div class="card-title">Your listings</div>
                        <div class="card-sub">Manage availability, pricing, and status.</div>
                    </div>
                    <div class="status-filters">
                        <button class="status-chip active" data-filter="all">All</button>
                        <button class="status-chip" data-filter="draft">Draft</button>
                        <button class="status-chip" data-filter="in_review">In review</button>
                        <button class="status-chip" data-filter="published">Published</button>
                        <button class="status-chip" data-filter="rejected">Rejected</button>
                    </div>
                </div>

                <?php if (!empty($loadError)): ?>
                    <div class="empty-state">
                        Error loading listings: <?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php elseif (empty($listings)): ?>
                    <div class="empty-state">
                        You don’t have any listings yet. Click <b>New listing</b> to get started.
                    </div>
                <?php else: ?>
                    <table class="listings-table" id="listingsTable">
                        <thead>
                        <tr>
                            <th>Listing</th>
                            <th>Location</th>
                            <th>Price (night)</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listings as $row): ?>
                            <?php
                            $id        = (int)$row['id'];
                            $title     = $row['title'] ?: 'Untitled listing';
                            $city      = $row['city'] ?: '';
                            $country   = $row['country'] ?: '';
                            $status    = $row['status'] ?: 'draft';
                            [$statusLabel, $statusClass] = status_label_class($status);

                            $nightly       = $row['nightly_price'];
                            $nightlyStrike = $row['nightly_price_strike'];

                            $hasDiscount   = !empty($row['has_discount']);
                            $discountLabel = $row['discount_label'] ?: '';

                            $locationText = trim($city . ', ' . $country, ', ');
                            ?>
                            <tr data-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                                <td>
                                    <div class="title-main">
                                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="title-sub">
                                        ID #<?php echo $id; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $locationText !== ''
                                        ? htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8')
                                        : '<span class="title-sub">Location not set</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($nightly !== null): ?>
                                        <span class="price-main">
                                            Rp <?php echo number_format((float)$nightly, 0, ',', '.'); ?>
                                        </span>
                                        <?php if ($nightlyStrike !== null && (float)$nightlyStrike > (float)$nightly): ?>
                                            <span class="price-strike">
                                                Rp <?php echo number_format((float)$nightlyStrike, 0, ',', '.'); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($hasDiscount && $nightlyStrike > $nightly): ?>
                                            <?php
                                                $discPct = round(
                                                    (($nightlyStrike - $nightly) / $nightlyStrike) * 100
                                                );
                                            ?>
                                            <span class="discount-badge">
                                                <img src="assets/icons/blue-fire.gif" alt="discount">
                                                <?php echo $discPct; ?>% OFF
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="title-sub">Set a price</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="actions-cell">
                                        <button class="btn-sm btn-sm-primary"
                                                type="button"
                                                onclick="window.location.href='host-listing-editor.php?id=<?php echo $id; ?>';">
                                            Edit
                                        </button>
                                        <button class="btn-sm" type="button" disabled>
                                            Preview
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    // Filter by status (client-side)
    const chips = document.querySelectorAll('.status-chip');
    const rows  = document.querySelectorAll('#listingsTable tbody tr');

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            const filter = chip.getAttribute('data-filter');

            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>
</body>
</html>
