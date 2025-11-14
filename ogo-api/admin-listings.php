<?php
function h($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OGORooms · Admin listings review</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg-main:#020617;
      --bg-card:#0b1220;
      --border-subtle:#1f2937;
      --text-main:#f9fafb;
      --text-muted:#9ca3af;
      --accent:#c97a3f;
      --accent-soft:rgba(201,122,63,0.25);
      --shadow-soft:0 18px 50px rgba(15,23,42,0.85);
      --radius-xl:24px;
      --font-sans:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Inter",sans-serif;
    }
    *{box-sizing:border-box;}
    body{
      margin:0;min-height:100vh;
      font-family:var(--font-sans);
      background:radial-gradient(circle at top,#111827 0,#020617 55%);
      color:var(--text-main);
      display:flex;flex-direction:column;
    }
    a{color:inherit;text-decoration:none;}

    .top-nav{
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 32px;border-bottom:1px solid #111827;
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
    .nav-right{font-size:13px;color:var(--text-muted);}

    .page{
      flex:1;
      display:flex;
      padding:24px 16px 32px;
    }
    @media(min-width:1024px){.page{padding:28px 40px 40px;}}

    .content{
      width:100%;max-width:1100px;margin:0 auto;
      display:flex;flex-direction:column;gap:16px;
    }
    .header-line{
      display:flex;justify-content:space-between;align-items:flex-end;
      gap:12px;
    }
    .header-title{font-size:24px;font-weight:650;letter-spacing:-.03em;}
    .header-sub{font-size:13px;color:var(--text-muted);}

    .card{
      border-radius:var(--radius-xl);
      border:1px solid #111827;
      background:rgba(15,23,42,0.98);
      box-shadow:var(--shadow-soft);
      padding:18px 18px 14px;
    }
    .badge{
      border-radius:999px;border:1px solid var(--accent-soft);
      padding:3px 9px;font-size:11px;
      color:var(--text-muted);text-transform:uppercase;letter-spacing:.14em;
    }
    .list-header{
      display:flex;justify-content:space-between;align-items:center;
      margin:10px 0;font-size:12px;color:var(--text-muted);
    }
    .list-table{width:100%;border-collapse:collapse;font-size:13px;}
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
    .status-pill{
      padding:3px 9px;border-radius:999px;font-size:11px;
    }
    .status-draft{
      background:rgba(148,163,184,0.15);border:1px solid rgba(148,163,184,0.4);color:#e5e7eb;
    }
    .status-inreview{
      background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.4);color:#bfdbfe;
    }
    .status-published{
      background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.4);color:#bbf7d0;
    }
    .status-rejected{
      background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.4);color:#fecaca;
    }

    .btn{
      padding:5px 10px;border-radius:999px;border:1px solid #4b5563;
      background:transparent;color:var(--text-main);font-size:11px;cursor:pointer;
    }
    .btn-primary{
      border-color:var(--accent-soft);
      background:linear-gradient(135deg,#c97a3f,#b56524);
      color:#111827;
    }
    .btn-danger{
      border-color:rgba(248,113,113,0.4);
      background:rgba(127,29,29,0.7);
      color:#fecaca;
    }
    .empty{font-size:13px;color:var(--text-muted);padding:8px 2px;}
  </style>
</head>
<body>
<header class="top-nav">
  <div class="logo-wrap">
    <div class="logo-mark">OG</div>
    <div>
      <div class="logo-main">OGOROOMS</div>
      <div class="logo-sub">ADMIN · LISTINGS</div>
    </div>
  </div>
  <div class="nav-right">
    (prototype · no auth yet)
  </div>
</header>

<main class="page">
  <section class="content">
    <div class="header-line">
      <div>
        <div class="header-title">Listings review</div>
        <div class="header-sub">Approve, reject, or send listings back to draft.</div>
      </div>
    </div>

    <div class="card">
      <span class="badge">All listings</span>
      <div class="list-header">
        <div>Latest listings</div>
        <div id="countBadge">0 items</div>
      </div>
      <div id="listContainer">
        <div class="empty">Loading…</div>
      </div>
    </div>
  </section>
</main>

<script>
function statusLabel(status){
  if(status === 'in_review') return 'In review';
  if(status === 'published') return 'Published';
  if(status === 'rejected') return 'Rejected';
  return 'Draft';
}
function statusClass(status){
  if(status === 'in_review') return 'status-pill status-inreview';
  if(status === 'published') return 'status-pill status-published';
  if(status === 'rejected') return 'status-pill status-rejected';
  return 'status-pill status-draft';
}

async function loadAdminListings(){
  const container = document.getElementById('listContainer');
  const badge = document.getElementById('countBadge');
  try{
    const res = await fetch('/ogo-api/listings-list.php');
    const text = await res.text();
    let json;
    try{ json = JSON.parse(text); }catch(e){
      container.innerHTML = '<div class="empty">Server returned non-JSON.</div>';
      console.log(text);
      return;
    }
    if(json.status !== 'ok'){
      container.innerHTML = '<div class="empty">Failed to load listings.</div>';
      return;
    }
    const items = json.listings || [];
    badge.textContent = items.length + ' item' + (items.length!==1?'s':'');

    if(items.length === 0){
      container.innerHTML = '<div class="empty">No listings yet.</div>';
      return;
    }

    let html = '<table class="list-table"><thead><tr>' +
      '<th>ID</th><th>Title</th><th>Host type</th><th>Location</th><th>Status</th><th>Actions</th>' +
      '</tr></thead><tbody>';

    items.forEach(item => {
      const id = item.id;
      const title = item.title || 'Untitled';
      const loc = (item.city || '') + (item.country ? ', ' + item.country : '');
      const hostType = item.host_type || '-';
      const st = item.status || 'draft';

      html += '<tr>' +
        '<td>#' + id + '</td>' +
        '<td>' + title + '</td>' +
        '<td>' + hostType + '</td>' +
        '<td>' + loc + '</td>' +
        '<td><span class="' + statusClass(st) + '">' + statusLabel(st) + '</span></td>' +
        '<td>' +
          '<button class="btn" onclick="setStatus(' + id + ',\'draft\')">Draft</button> ' +
          '<button class="btn" onclick="setStatus(' + id + ',\'in_review\')">In review</button> ' +
          '<button class="btn btn-primary" onclick="setStatus(' + id + ',\'published\')">Publish</button> ' +
          '<button class="btn btn-danger" onclick="rejectListing(' + id + ')">Reject</button>' +
        '</td>' +
      '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
  }catch(err){
    container.innerHTML = '<div class="empty">Network error.</div>';
    console.error(err);
  }
}

async function setStatus(id, status, note){
  if(!id) return;
  const payload = {
    id: id,
    status: status,
    review_note: note || ''
  };
  try{
    const res = await fetch('/ogo-api/listings-status-update.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const text = await res.text();
    let json;
    try{ json = JSON.parse(text); }catch(e){
      alert('Server returned non-JSON:\n\n' + text);
      return;
    }
    if(json.status === 'ok'){
      loadAdminListings();
    }else{
      alert('Failed to update status: ' + (json.message || 'Unknown error'));
    }
  }catch(err){
    alert('Network error: ' + err);
  }
}

function rejectListing(id){
  const reason = prompt('Enter reason for rejection (will be shown to host):','');
  if(reason === null) return; // cancel
  setStatus(id, 'rejected', reason);
}

loadAdminListings();
</script>
</body>
</html>
