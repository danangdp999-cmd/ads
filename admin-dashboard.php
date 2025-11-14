<?php
// admin-dashboard.php — OGORooms admin / super admin console

session_start();
require_once __DIR__ . '/ogo-api/config.php';

$userId   = $_SESSION['user_id']   ?? 0;
$userRole = $_SESSION['user_role'] ?? '';

if (!$userId || !in_array($userRole, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo "Access denied. Admin only.";
    exit;
}

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
} catch (Exception $e) {
    http_response_code(500);
    echo "DB error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

// --- stats users by role ---
$statsUsers = [];
$stm = $pdo->query("SELECT role, COUNT(*) AS c FROM ogo_users GROUP BY role");
foreach ($stm as $row) {
    $statsUsers[$row['role']] = (int)$row['c'];
}

// --- stats listings by status (kalau tabel ada) ---
$statsListings = [];
if ($pdo->query("SHOW TABLES LIKE 'simple_listings'")->rowCount() > 0) {
    $stm = $pdo->query("SELECT status, COUNT(*) AS c FROM simple_listings GROUP BY status");
    foreach ($stm as $row) {
        $statsListings[$row['status']] = (int)$row['c'];
    }
}

// --- users per role (untuk tabel terpisah) ---
function fetchUsersByRole(PDO $pdo, string $role): array {
    $sql = "SELECT id, name, email, role, created_at
            FROM ogo_users
            WHERE role = :r
            ORDER BY created_at DESC";
    $stm = $pdo->prepare($sql);
    $stm->execute([':r' => $role]);
    return $stm->fetchAll();
}

$superAdmins = fetchUsersByRole($pdo, 'super_admin');
$admins      = fetchUsersByRole($pdo, 'admin');

// regular users = guest + host
$sql = "SELECT id, name, email, role, created_at
        FROM ogo_users
        WHERE role IN ('guest','host')
        ORDER BY created_at DESC
        LIMIT 100";
$stm = $pdo->query($sql);
$regularUsers = $stm->fetchAll();

$pendingListings = [];
$recentPublished = [];
if ($pdo->query("SHOW TABLES LIKE 'simple_listings'")->rowCount() > 0) {
    $sqlPending = "SELECT l.id, l.title, l.city, l.country, l.nightly_price, l.created_at, u.email AS host_email
                   FROM simple_listings l
                   LEFT JOIN ogo_users u ON u.id = l.host_user_id
                   WHERE l.status = 'in_review'
                   ORDER BY l.created_at ASC
                   LIMIT 6";
    $pendingListings = $pdo->query($sqlPending)->fetchAll();

    $sqlPublished = "SELECT l.id, l.title, l.city, l.country, l.nightly_price, l.approved_at
                     FROM simple_listings l
                     WHERE l.status = 'published'
                     ORDER BY COALESCE(l.approved_at, l.updated_at, l.created_at) DESC
                     LIMIT 6";
    $recentPublished = $pdo->query($sqlPublished)->fetchAll();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OGORooms – Admin console</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-body:#f3f4f6;
            --bg-card:#ffffff;
            --text-main:#0f172a;
            --text-muted:#64748b;
            --accent:#b2743b;
            --accent-strong:#7a461a;
            --border-subtle:#e2e8f0;
            --shadow-soft:0 18px 40px rgba(15,23,42,0.15);
            --radius-xl:999px;
            --radius-lg:18px;
        }
        *{box-sizing:border-box;}
        body{
            margin:0;
            font-family:"Plus Jakarta Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:var(--bg-body);
            color:var(--text-main);
        }
        a{text-decoration:none;color:inherit;}

        .nav{
            position:sticky;top:0;z-index:40;
            backdrop-filter:blur(18px);
            background:linear-gradient(90deg,#020617 0%,#111827 45%,#1f2933 100%);
            color:#e5e7eb;
            border-bottom:1px solid rgba(15,23,42,0.8);
        }
        .nav-inner{
            max-width:1240px;margin:0 auto;
            padding:10px 24px;
            display:flex;align-items:center;justify-content:space-between;gap:24px;
        }
        .nav-left{display:flex;align-items:center;gap:10px;}
        .nav-logo-circle{
            width:34px;height:34px;border-radius:50%;
            background:#f97316;
            display:flex;align-items:center;justify-content:center;
            color:#111827;font-weight:800;font-size:14px;letter-spacing:0.12em;
        }
        .nav-brand-text{display:flex;flex-direction:column;line-height:1.1;}
        .nav-brand-title{
            font-size:16px;font-weight:700;letter-spacing:0.14em;
        }
        .nav-brand-sub{
            font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.2em;
        }
        .nav-chip-role{
            border-radius:999px;
            border:1px solid rgba(148,163,184,0.7);
            padding:4px 10px;
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:0.16em;
            background:rgba(15,23,42,0.8);
        }
        .nav-right{display:flex;align-items:center;gap:10px;font-size:13px;}
        .nav-link{
            padding:6px 10px;border-radius:999px;cursor:pointer;
            color:#e5e7eb;border:1px solid transparent;
        }
        .nav-link:hover{
            background:rgba(15,23,42,0.6);
            border-color:rgba(148,163,184,0.6);
        }

        .page{
            max-width:1240px;margin:0 auto;
            padding:20px 24px 32px;
        }
        .page-title{
            font-size:22px;font-weight:700;margin-bottom:4px;
        }
        .page-sub{
            font-size:13px;color:var(--text-muted);margin-bottom:20px;
        }

        .grid-3{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:14px;
            margin-bottom:22px;
        }
        @media(max-width:900px){
            .grid-3{grid-template-columns:repeat(1,minmax(0,1fr));}
        }
        .stat-card{
            background:var(--bg-card);
            border-radius:var(--radius-lg);
            border:1px solid var(--border-subtle);
            box-shadow:var(--shadow-soft);
            padding:12px 14px;
        }
        .stat-label{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.12em;margin-bottom:4px;}
        .stat-value{font-size:18px;font-weight:700;}
        .stat-sub{font-size:11px;color:var(--text-muted);}

        .section-title{
            font-size:15px;font-weight:600;margin:18px 0 8px;
            display:flex;align-items:center;justify-content:space-between;gap:8px;
        }
        .section-title small{font-size:11px;color:var(--text-muted);font-weight:400;}

        .card{
            background:var(--bg-card);
            border-radius:var(--radius-lg);
            border:1px solid var(--border-subtle);
            box-shadow:var(--shadow-soft);
            padding:10px 14px 14px;
            margin-bottom:18px;
        }

        table{
            width:100%;border-collapse:collapse;font-size:12px;
        }
        th,td{
            padding:6px 6px;
            border-bottom:1px solid #e5e7eb;
            vertical-align:top;
        }
        th{
            text-align:left;
            font-weight:600;
            color:#4b5563;
            background:#f9fafb;
        }
        tr:last-child td{border-bottom:none;}

        .small-mono{
            font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
            font-size:11px;color:#6b7280;
        }
        .role-pill{
            display:inline-flex;align-items:center;
            padding:2px 7px;border-radius:999px;
            font-size:10px;font-weight:500;
        }
        .role-super_admin{background:#fef3c7;color:#92400e;}
        .role-admin{background:#dbeafe;color:#1d4ed8;}
        .role-host{background:#dcfce7;color:#15803d;}
        .role-guest{background:#f3f4f6;color:#4b5563;}

        .link-soft{
            font-size:11px;color:#2563eb;
            text-decoration:underline;
            text-underline-offset:2px;
        }

        .moderation-table th,
        .moderation-table td {
            font-size:12px;
        }

        .listing-actions{
            display:flex;
            gap:6px;
            justify-content:flex-end;
            flex-wrap:wrap;
        }

        .btn{
            border:none;
            border-radius:8px;
            padding:6px 10px;
            font-size:12px;
            cursor:pointer;
            font-weight:500;
        }

        .btn-approve{background:#16a34a;color:#fff;}
        .btn-reject{background:#ef4444;color:#fff;}
        .btn-view{background:#e5e7eb;color:#111827;}

        .pill-status{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:4px 8px;
            border-radius:999px;
            font-size:11px;
            background:#f3f4f6;
            color:#4b5563;
        }
    </style>
</head>
<body>
<header class="nav">
    <div class="nav-inner">
        <div class="nav-left">
            <div class="nav-logo-circle">OG</div>
            <div class="nav-brand-text">
                <span class="nav-brand-title">OGOROOMS CONSOLE</span>
                <span class="nav-brand-sub">ADMINISTRATOR AREA</span>
            </div>
        </div>
        <div class="nav-right">
            <span class="nav-chip-role">
                <?php echo $userRole === 'super_admin' ? 'SUPER ADMIN' : 'ADMIN'; ?>
            </span>
            <a href="index.php" class="nav-link">View site</a>
            <a href="admin-listings.php" class="nav-link">Listings review</a>
            <a href="logout.php" class="nav-link">Log out</a>
        </div>
    </div>
</header>

<main class="page">
    <h1 class="page-title">Overview</h1>
    <p class="page-sub">
        High-level view of users and listings. Admin &amp; Super admin only.
    </p>

    <!-- STAT CARDS -->
    <div class="grid-3">
        <div class="stat-card">
            <div class="stat-label">Users</div>
            <div class="stat-value">
                <?php
                $totalUsers = array_sum($statsUsers);
                echo $totalUsers;
                ?>
            </div>
            <div class="stat-sub">
                Super admin: <?php echo $statsUsers['super_admin'] ?? 0; ?> ·
                Admin: <?php echo $statsUsers['admin'] ?? 0; ?> ·
                Host/Guest: <?php echo ($totalUsers - ($statsUsers['super_admin'] ?? 0) - ($statsUsers['admin'] ?? 0)); ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Listings</div>
            <div class="stat-value">
                <?php echo array_sum($statsListings); ?>
            </div>
            <div class="stat-sub">
                Published: <?php echo $statsListings['published'] ?? 0; ?> ·
                In review: <?php echo $statsListings['in_review'] ?? 0; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Moderation</div>
            <div class="stat-value">
                <?php echo $statsListings['rejected'] ?? 0; ?>
            </div>
            <div class="stat-sub">
                rejected listings total.
                <a href="admin-listings.php" class="link-soft">Open review panel</a>
            </div>
        </div>
    </div>

    <!-- LISTING MODERATION -->
    <div class="section-title">
        <span>Listings moderation</span>
        <small><?php echo count($pendingListings); ?> awaiting review</small>
    </div>
    <div class="card">
        <?php if (empty($pendingListings)): ?>
            <p class="page-sub">No listings are waiting for approval. 🎉</p>
        <?php else: ?>
            <table class="moderation-table">
                <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Listing</th>
                    <th>Host</th>
                    <th>Nightly price</th>
                    <th>Submitted</th>
                    <th style="width:220px;text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingListings as $listing): ?>
                    <tr>
                        <td class="small-mono">#<?php echo (int)$listing['id']; ?></td>
                        <td>
                            <div style="font-weight:600;font-size:13px;">
                                <?php echo h($listing['title'] ?: 'Untitled'); ?>
                            </div>
                            <div class="small-mono" style="color:var(--text-muted);">
                                <?php echo h(trim(($listing['city'] ?: '') . ($listing['country'] ? ', ' . $listing['country'] : '')) ?: '—'); ?>
                            </div>
                        </td>
                        <td>
                            <div class="small-mono"><?php echo h($listing['host_email'] ?: '—'); ?></div>
                        </td>
                        <td>
                            <?php
                            $price = $listing['nightly_price'] !== null ? (float)$listing['nightly_price'] : 0;
                            echo $price > 0 ? 'Rp' . number_format($price, 0, ',', '.') : '—';
                            ?>
                        </td>
                        <td class="small-mono"><?php echo h($listing['created_at']); ?></td>
                        <td>
                            <div class="listing-actions">
                                <a class="btn btn-view" href="host-listing-editor.php?id=<?php echo (int)$listing['id']; ?>" target="_blank">Open</a>
                                <form method="post" action="admin-listing-action.php" style="display:inline" onsubmit="return confirm('Approve listing #<?php echo (int)$listing['id']; ?>?');">
                                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                    <input type="hidden" name="listing_id" value="<?php echo (int)$listing['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve">Approve</button>
                                </form>
                                <form method="post" action="admin-listing-action.php" style="display:inline" onsubmit="return confirm('Reject listing #<?php echo (int)$listing['id']; ?>?');">
                                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                                    <input type="hidden" name="listing_id" value="<?php echo (int)$listing['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="reason" value="Rejected from dashboard">
                                    <button type="submit" class="btn btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="section-title">
        <span>Recently published</span>
        <small><?php echo count($recentPublished); ?> latest approvals</small>
    </div>
    <div class="card">
        <?php if (empty($recentPublished)): ?>
            <p class="page-sub">No published listings yet.</p>
        <?php else: ?>
            <table class="moderation-table">
                <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Listing</th>
                    <th>Nightly price</th>
                    <th>Approved at</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentPublished as $listing): ?>
                    <tr>
                        <td class="small-mono">#<?php echo (int)$listing['id']; ?></td>
                        <td>
                            <div style="font-weight:600;font-size:13px;">
                                <a href="listing-detail.php?id=<?php echo (int)$listing['id']; ?>" target="_blank" class="link-soft">
                                    <?php echo h($listing['title'] ?: 'Untitled'); ?>
                                </a>
                            </div>
                            <div class="small-mono" style="color:var(--text-muted);">
                                <?php echo h(trim(($listing['city'] ?: '') . ($listing['country'] ? ', ' . $listing['country'] : '')) ?: '—'); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $price = $listing['nightly_price'] !== null ? (float)$listing['nightly_price'] : 0;
                            echo $price > 0 ? 'Rp' . number_format($price, 0, ',', '.') : '—';
                            ?>
                        </td>
                        <td class="small-mono"><?php echo h($listing['approved_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- SUPER ADMINS -->
    <div class="section-title">
        <span>Super admins</span>
        <small>Total: <?php echo count($superAdmins); ?></small>
    </div>
    <div class="card">
        <?php if (empty($superAdmins)): ?>
            <p class="page-sub">No super admin accounts yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name &amp; email</th>
                    <th>Role</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($superAdmins as $u): ?>
                    <tr>
                        <td class="small-mono">#<?php echo (int)$u['id']; ?></td>
                        <td>
                            <div style="font-size:13px;font-weight:500;"><?php echo h($u['name'] ?: 'Unnamed'); ?></div>
                            <div class="small-mono"><?php echo h($u['email']); ?></div>
                        </td>
                        <td>
                            <span class="role-pill role-super_admin">SUPER ADMIN</span>
                        </td>
                        <td class="small-mono"><?php echo h($u['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ADMINS -->
    <div class="section-title">
        <span>Admins</span>
        <small>Total: <?php echo count($admins); ?></small>
    </div>
    <div class="card">
        <?php if (empty($admins)): ?>
            <p class="page-sub">No admin accounts yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name &amp; email</th>
                    <th>Role</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($admins as $u): ?>
                    <tr>
                        <td class="small-mono">#<?php echo (int)$u['id']; ?></td>
                        <td>
                            <div style="font-size:13px;font-weight:500;"><?php echo h($u['name'] ?: 'Unnamed'); ?></div>
                            <div class="small-mono"><?php echo h($u['email']); ?></div>
                        </td>
                        <td>
                            <span class="role-pill role-admin">ADMIN</span>
                        </td>
                        <td class="small-mono"><?php echo h($u['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- REGULAR USERS -->
    <div class="section-title">
        <span>Regular users (hosts &amp; guests)</span>
        <small>Showing latest <?php echo count($regularUsers); ?> users</small>
    </div>
    <div class="card">
        <?php if (empty($regularUsers)): ?>
            <p class="page-sub">No regular users yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name &amp; email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($regularUsers as $u): ?>
                    <tr>
                        <td class="small-mono">#<?php echo (int)$u['id']; ?></td>
                        <td>
                            <div style="font-size:13px;font-weight:500;"><?php echo h($u['name'] ?: 'Unnamed'); ?></div>
                            <div class="small-mono"><?php echo h($u['email']); ?></div>
                        </td>
                        <td>
                            <?php
                            $role = $u['role'];
                            $class = $role === 'host' ? 'role-host' : 'role-guest';
                            ?>
                            <span class="role-pill <?php echo $class; ?>">
                                <?php echo strtoupper($role); ?>
                            </span>
                        </td>
                        <td class="small-mono"><?php echo h($u['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
