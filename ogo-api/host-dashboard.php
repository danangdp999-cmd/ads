<?php
function h($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
require_once __DIR__ . '/auth-check.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OGORooms · Hosting dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg-main:#020617;
      --bg-card:#0b1220;
      --border-subtle:#1f2937;
      --text-main:#f9fafb;
      --text-muted:#9ca3af;
      --accent:#c97a3f;
      --accent-soft:rgba(201,122,63,0.2);
      --shadow-soft:0 18px 50px rgba(15,23,42,0.85);
      --radius-xl:24px;
      --font-sans:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Inter",sans-serif;
    }
    *{box-sizing:border-box;}
    body{
      margin:0;
      min-height:100vh;
      font-family:var(--font-sans);
      background:radial-gradient(circle at top,#111827 0,#020617 55%);
      color:var(--text-main);
      display:flex;
      flex-direction:column;
    }
    a{color:inherit;text-decoration:none;}
    .top-nav{
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 32px;border-bottom:1px solid #111827;
      backdrop-filter:blur(18px);
    }
    .logo-wrap{display:flex;align-items:center;gap:10px;}
    .logo-mark{
      width:32px;height:32px;border-radius:999px;
      background:#111827;border:1px solid #4b5563;
      display:flex;align-items:center;justify-content:center;
      font-weight:700;font-size:14px;
    }
    .logo-main{font-weight:650;letter-spacing:.14em;font-size:13px;}
    .logo-sub{font-size:11px;text-transform:uppercase;letter-spacing:.16em;color:var(--text-muted);}
    .nav-right{display:flex;align-items:center;gap:14px;font-size:13px;}
    .pill{
      padding:6px 13px;border-radius:999px;
      border:1px solid #4b5563;background:rgba(15,23,42,0.9);
      font-size:12px;color:var(--text-muted);
    }

    .page{
      flex:1;
      display:flex;
      padding:24px 16px 32px;
      gap:20px;
    }
    @media(min-width:1024px){.page{padding:28px 40px 40px;}}
    .side-nav{
      width:220px;
      border-radius:20px;
      border:1px solid #111827;
      background:rgba(15,23,42,0.95);
      box-shadow:var(--shadow-soft);
      padding:14px 14px 16px;
      font-size:13px;
      display:none;
    }
    @media(min-width:900px){.side-nav{display:block;}}
    .side-title{font-size:12px;text-transform:uppercase;letter-spacing:.14em;color:var(--text-muted);margin-bottom:6px;}
    .side-item{
      padding:8px 10px;border-radius:12px;cursor:pointer;
      color:var(--text-muted);
    }
    .side-item--active{
      background:radial-gradient(circle at top,#111827 0,#020617 70%);
      border:1px solid var(--accent-soft);
      color:var(--text-main);
    }

    .content{
      flex:1;
      max-width:980px;
      margin:0 auto;
      display:flex;
      flex-direction:column;
      gap:16px;
    }
    .content-header{
      display:flex;justify-content:space-between;align-items:flex-end;
      gap:12px;
    }
    .content-title{
      font-size:24px;font-weight:650;letter-spacing:-.03em;
    }
    .content-sub{font-size:13px;color:var(--text-muted);}
    .btn-primary{
      padding:8px 15px;
      border-radius:999px;border:none;cursor:pointer;
      background:linear-gradient(135deg,#c97a3f,#b56524);
      color:#111827;font-size:13px;font-weight:550;
      box-shadow:0 10px 24px rgba(0,0,0,0.7);
    }

    .card{
      border-radius:var(--radius-xl);
      border:1px solid #111827;
      background:rgba(15,23,42,0.98);
      box-shadow:var(--shadow-soft);
      padding:16px 16px 10px;
    }

    .list-header{
      display:flex;align-items:center;justify-content:space-between;
      margin-bottom:10px;font-size:12px;color:var(--text-muted);
    }
    .badge{
      border-radius:999px;
      border:1px solid var(--accent-soft);
      padding:3px 9px;
      font-size:11px;
    }
    .list-empty{font-size:13px;color:var(--text-muted);padding:8px 2px;}
    .list-table{width:100%;border-collapse:collapse;margin-top:4px;font-size:13px;}
    .list-table th,
    .list-table td{
      padding:8px 6px;
      border-bottom:1px solid #111827;
      text-align:left;
    }
    .list-table th{
      font-size:11px;text-transform:uppercase;letter-spacing:.12em;
      color:var(--text-muted);
    }
    .chip{
      padding:3px 8px;border-radius:999px;
      border:1px solid #374151;font-size:11px;color:var(--text-muted);
    }
    .price{font-weight:550;}
    .status-pill{
      padding:3px 9px;border-radius:999px;
      background:rgba(34,197,94,0.1);
      border:1px solid rgba(34,197,94,0.3);
      color:#bbf7d0;font-size:11px;
    }
  </style>
</head>
<body>

<header class="top-nav">
  <div class="logo-wrap">
    <a href="index.php" style="display:flex;align-items:center;gap:10px;">
      <div class="logo-mark">OG</div>
      <div>
        <div class="logo-main">OGOROOMS</div>
        <div class="logo-sub">HOSTING</div>
      </div>
    </a>
  </div>
  <div class="nav-right">
    <span class="pill">Hosting dashboard</span>
    <a href="index.php" style="font-size:13px;color:var(--text-muted);">Switch to traveling</a>
  </div>
</header>

<main class="page">
  <aside class="side-nav">
    <div class="side-title">Menu</div>
    <div class="side-item side-item--active">Listings</div>
    <div class="side-item">Calendar</div>
    <div class="side-item">Messages</div>
    <div class="side-item">Earnings</div>
  </aside>

  <section class="content">
    <div class="content-header">
      <div>
        <div class="content-title">Your listings</div>
        <div class="content-sub">Manage the stays, experiences, and services you host with OGORooms.</div>
      </div>
      <button class="btn-primary" onclick="window.location.href='host-start.php'">
        + Create new listing
      </button>
    </div>

    <div class="card">
      <div class="list-header">
        <div>Listings overview</div>
        <div id="listingCountBadge" class="badge">0 items</div>
      </div>
      <div id="listContainer">
        <div class="list-empty">Loading listings…</div>
      </div>
    </div>
  </section>
</main>

<script>
async function loadListings(){
  const container = document.getElementById('listContainer');
  const badge = document.getElementById('listingCountBadge');
  try{
    const res = await fetch('/ogo-api/listings-list.php');
    const text = await res.text();
    let json;
    try{
      json = JSON.parse(text);
    }catch(e){
      container.innerHTML = '<div class="list-empty">Server returned non-JSON.</div>';
      console.log(text);
      return;
    }

    if(json.status !== 'ok'){
      container.innerHTML = '<div class="list-empty">Failed to load listings.</div>';
      return;
    }

    const items = json.listings || [];
    badge.textContent = items.length + ' item' + (items.length !== 1 ? 's' : '');

    if(items.length === 0){
      container.innerHTML = '<div class="list-empty">You don’t have any listings yet. Click “Create new listing” to get started.</div>';
      return;
    }

    let html = '<table class="list-table"><thead><tr>' +
      '<th>Listing</th>' +
      '<th>Location</th>' +
      '<th>Type</th>' +
      '<th>Price / night</th>' +
      '<th>Status</th>' +
      '</tr></thead><tbody>';

    items.forEach(item => {
  const id    = item.id;
  const title = item.title || 'Untitled';
  const loc   = (item.city || '') + (item.country ? ', ' + item.country : '');
  const type  = (item.property_type || '') + ' · ' + (item.place_type || '');
  const price = item.price_nightly ? 'Rp ' + Number(item.price_nightly).toLocaleString('id-ID') : '—';
  const status = item.status || 'draft';

  let statusLabel = 'Draft';
  if (status === 'in_review')  statusLabel = 'In review';
  if (status === 'published')  statusLabel = 'Published';
  if (status === 'rejected')   statusLabel = 'Rejected';

  html += '<tr style="cursor:pointer;" onclick="window.location.href=\'host-listing-editor.php?id=' + id + '\'">' +
    '<td>' + title + '</td>' +
    '<td>' + loc + '</td>' +
    '<td><span class="chip">' + type + '</span></td>' +
    '<td class="price">' + price + '</td>' +
    '<td><span class="status-pill">' + statusLabel + '</span></td>' +
    '</tr>';
});



    html += '</tbody></table>';
    container.innerHTML = html;

  }catch(err){
    container.innerHTML = '<div class="list-empty">Network error loading listings.</div>';
    console.error(err);
  }
}

loadListings();
</script>
</body>
</html>
