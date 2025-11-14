<?php
session_start();
require_once __DIR__.'/ogo-api/config.php';

$uid   = $_SESSION['user_id']   ?? 0;
$role  = $_SESSION['user_role'] ?? '';
if (!$uid || !in_array($role, ['admin','super_admin'], true)) { http_response_code(403); echo 'Access denied'; exit; }

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  ]);
} catch(Exception $e){ http_response_code(500); echo 'DB error'; exit; }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$allowed = ['in_review','published','rejected','draft','all'];
$status  = $_GET['status'] ?? 'in_review';
if (!in_array($status, $allowed, true)) $status = 'in_review';

$where  = '1=1';
$params = [];
if ($status !== 'all') { $where = 'l.status = :s'; $params[':s'] = $status; }

$sql = "SELECT l.id,l.title,l.location_city,l.nightly_price,l.status,l.host_id,
               l.created_at,l.approved_at,l.rejected_reason,
               u.email AS host_email
        FROM simple_listings l
        LEFT JOIN ogo_users u ON u.id = l.host_id
        WHERE $where
        ORDER BY l.created_at DESC
        LIMIT 200";
$stm = $pdo->prepare($sql); $stm->execute($params);
$list = $stm->fetchAll();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>OGORooms – Listings review</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 :root{--bg:#f3f4f6;--card:#fff;--txt:#0f172a;--muted:#64748b;--accent:#b2743b;--border:#e5e7eb}
 *{box-sizing:border-box} body{margin:0;font-family:"Plus Jakarta Sans",system-ui;background:var(--bg);color:var(--txt)}
 .nav{background:#111827;color:#e5e7eb;padding:10px 16px;display:flex;justify-content:space-between;align-items:center}
 .wrap{max-width:1200px;margin:16px auto;padding:0 16px}
 .h1{font-size:22px;font-weight:700;margin:6px 0}
 .tabs a{display:inline-block;padding:6px 10px;border:1px solid var(--border);border-radius:999px;margin-right:6px;background:#fff;color:#111}
 .tabs a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
 table{width:100%;border-collapse:collapse;margin-top:12px;background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
 th,td{padding:10px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:top}
 th{background:#fafafa;text-align:left}
 .pill{padding:2px 8px;border-radius:999px;font-size:11px}
 .s-in_review{background:#fff7ed;color:#b45309}
 .s-published{background:#dcfce7;color:#166534}
 .s-rejected{background:#fee2e2;color:#991b1b}
 .s-draft{background:#f3f4f6;color:#374151}
 .btn{border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:12px}
 .btn-approve{background:#16a34a;color:#fff}
 .btn-reject{background:#ef4444;color:#fff}
 .btn-view{background:#e5e7eb}
 .muted{color:var(--muted);font-size:12px}
 .right{display:flex;gap:8px;justify-content:flex-end}
 dialog{border:none;border-radius:12px;padding:16px;max-width:420px;width:92%}
 dialog::backdrop{background:rgba(0,0,0,.4)}
</style></head><body>
<div class="nav">
  <div><strong>OGORooms Console</strong> · Listings review</div>
  <div class="muted"><a href="admin-dashboard.php" style="color:#e5e7eb">Dashboard</a> · <a href="logout.php" style="color:#e5e7eb">Log out</a></div>
</div>

<div class="wrap">
  <div class="h1">Moderation queue</div>
  <div class="tabs">
    <?php foreach(['in_review','published','rejected','draft','all'] as $tab){
      $cls = $status===$tab?'active':'';
      echo '<a class="'.$cls.'" href="?status='.urlencode($tab).'">'.str_replace('_',' ',$tab).'</a>';
    } ?>
  </div>

  <table>
    <thead>
      <tr><th>ID</th><th>Title / location</th><th>Host</th><th>Price</th><th>Status</th><th style="text-align:right">Actions</th></tr>
    </thead>
    <tbody>
    <?php if(!$list): ?>
      <tr><td colspan="6" class="muted">No data</td></tr>
    <?php else: foreach($list as $r): ?>
      <tr>
        <td>#<?= (int)$r['id'] ?></td>
        <td>
          <div style="font-weight:600"><?= h($r['title'] ?: '(untitled)') ?></div>
          <div class="muted"><?= h($r['location_city'] ?: '—') ?></div>
          <?php if ($r['status']==='rejected' && $r['rejected_reason']): ?>
            <div class="muted" style="margin-top:4px"><strong>Reason:</strong> <?= h($r['rejected_reason']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <div class="muted"><?= h($r['host_email'] ?: '—') ?></div>
          <div class="muted">Host ID: <?= (int)$r['host_id'] ?></div>
        </td>
        <td>Rp<?= number_format((float)$r['nightly_price'] ?: 0,0,',','.') ?></td>
        <td>
          <span class="pill s-<?= h($r['status']) ?>"><?= strtoupper(str_replace('_',' ',$r['status'])) ?></span><br>
          <span class="muted"><?= $r['approved_at'] ? 'Approved: '.h($r['approved_at']) : 'Created: '.h($r['created_at']) ?></span>
        </td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn btn-view" href="host-listing-editor.php?id=<?= (int)$r['id'] ?>" target="_blank">View</a>
          <?php if ($r['status']==='in_review' || $r['status']==='rejected'): ?>
            <form method="post" action="admin-listing-action.php" style="display:inline">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="listing_id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button class="btn btn-approve" type="submit">Approve</button>
            </form>
          <?php endif; ?>
          <?php if ($r['status']!=='rejected'): ?>
            <button class="btn btn-reject" onclick="openReject(<?= (int)$r['id'] ?>)">Reject</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<dialog id="rej">
  <form method="post" action="admin-listing-action.php">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="listing_id" id="rej_id" value="">
    <input type="hidden" name="action" value="reject">
    <h3>Reject listing</h3>
    <p class="muted">Tuliskan alasan singkat agar host paham.</p>
    <textarea name="reason" rows="4" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px" required></textarea>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px">
      <button type="button" onclick="document.getElementById('rej').close()">Cancel</button>
      <button class="btn btn-reject" type="submit">Reject</button>
    </div>
  </form>
</dialog>

<script>
function openReject(id){ document.getElementById('rej_id').value = id; document.getElementById('rej').showModal(); }
</script>
</body></html>
