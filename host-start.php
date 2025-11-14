<?php
// host-start.php — OGORooms Become a host entry (versi dengan form submit)

require_once __DIR__ . '/auth-check.php'; // wajib login

$currentUserEmail = $currentUserEmail ?? ($_SESSION['user_email'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OGORooms – Become a host</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

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
    *{box-sizing:border-box;}
    body{
      margin:0;
      font-family:"Plus Jakarta Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--bg-body);
      color:var(--text-main);
    }
    a{color:inherit;text-decoration:none;}
    .page{min-height:100vh;display:flex;flex-direction:column;}

    /* NAV */
    .nav{
      position:sticky;top:0;z-index:40;
      backdrop-filter:blur(16px);
      background:rgba(255,255,255,0.95);
      border-bottom:1px solid rgba(229,231,235,0.8);
    }
    .nav-inner{
      max-width:1240px;margin:0 auto;
      padding:10px 20px;
      display:flex;align-items:center;justify-content:space-between;gap:24px;
    }
    .nav-left{display:flex;align-items:center;gap:10px;}
    .nav-logo-circle{
      width:34px;height:34px;border-radius:50%;
      background:var(--accent);
      display:flex;align-items:center;justify-content:center;
      color:#fff;font-weight:700;letter-spacing:0.04em;font-size:14px;
    }
    .nav-brand-text{display:flex;flex-direction:column;line-height:1.1;}
    .nav-brand-title{font-size:17px;font-weight:700;letter-spacing:0.08em;}
    .nav-brand-sub{
      font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.18em;
    }
    .nav-center{
      display:flex;gap:18px;align-items:center;font-size:14px;font-weight:500;
    }
    .nav-center button{
      border:none;background:transparent;padding:8px 0;cursor:pointer;position:relative;color:#4b5563;
    }
    .nav-center button.active{color:var(--text-main);}
    .nav-center button.active::after{
      content:"";position:absolute;left:0;right:0;bottom:-6px;margin-inline:auto;
      width:18px;height:3px;border-radius:999px;background:var(--accent);
    }
    .nav-right{display:flex;align-items:center;gap:10px;font-size:14px;}
    .nav-link{
      padding:6px 10px;border-radius:var(--radius-xl);cursor:pointer;color:#374151;
    }
    .nav-link:hover{background:#f3f4f6;}
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

    /* MAIN */
    .main{flex:1;}
    .shell{
      max-width:1240px;margin:0 auto;
      padding:28px 20px 40px;
      display:grid;
      grid-template-columns:minmax(0,1.4fr) minmax(0,1.3fr);
      gap:28px;
      align-items:center;
    }
    @media(max-width:900px){
      .shell{grid-template-columns:minmax(0,1fr);padding-inline:16px;}
      .nav-center{display:none;}
    }

    .hero-kicker{
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:0.18em;
      color:var(--text-muted);
      margin-bottom:6px;
    }
    .hero-title{
      font-size:32px;
      font-weight:700;
      margin-bottom:10px;
    }
    .hero-sub{
      font-size:14px;
      color:var(--text-muted);
      margin-bottom:18px;
    }
    .hero-list{
      font-size:13px;
      color:#374151;
      list-style:none;
      padding:0;margin:0 0 18px;
    }
    .hero-list li{
      margin-bottom:6px;
      display:flex;
      gap:8px;
      align-items:flex-start;
    }

    .pill-small{
      display:inline-flex;
      align-items:center;
      gap:6px;
      border-radius:999px;
      padding:5px 9px;
      background:var(--pill-bg);
      font-size:11px;
      color:#4b5563;
      margin-bottom:12px;
    }

    .btn-primary{
      border-radius:999px;
      border:none;
      padding:9px 16px;
      background:var(--accent);
      color:#fff;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      box-shadow:0 12px 26px rgba(178,116,59,0.45);
    }

    .muted{
      font-size:12px;
      color:var(--text-muted);
      margin-top:8px;
    }

    .card-grid{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:12px;
    }
    @media(max-width:700px){
      .card-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    }
    @media(max-width:480px){
      .card-grid{grid-template-columns:minmax(0,1fr);}
    }

    .type-card{
      border-radius:18px;
      background:var(--bg-card);
      border:1px solid rgba(229,231,235,0.95);
      padding:12px 12px 10px;
      box-shadow:0 14px 32px rgba(148,163,184,0.2);
      cursor:pointer;
      display:flex;
      flex-direction:column;
      gap:8px;
      transition:transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .type-card:hover{
      transform:translateY(-2px);
      box-shadow:0 20px 40px rgba(15,23,42,0.15);
    }
    .type-card.selected{
      border-color:var(--accent);
      box-shadow:0 20px 45px rgba(178,116,59,0.25);
    }

    .type-icon{
      width:34px;height:34px;border-radius:12px;
      display:flex;align-items:center;justify-content:center;
      background:var(--pill-bg);
      font-size:18px;
    }
    .type-title{
      font-size:14px;font-weight:600;
    }
    .type-sub{
      font-size:12px;color:var(--text-muted);
    }

    .badge-coming{
      display:inline-flex;
      align-items:center;
      padding:3px 7px;
      border-radius:999px;
      background:#fef3c7;
      color:#92400e;
      font-size:10px;
      text-transform:uppercase;
      letter-spacing:0.08em;
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
          <span class="nav-brand-sub">HOSTING</span>
        </div>
      </div>

      <div class="nav-center">
        <button type="button" class="active" onclick="window.location.href='host-dashboard.php';">Hosting</button>
        <button type="button" onclick="window.location.href='index.php';">Traveling</button>
      </div>

      <div class="nav-right">
        <a href="index.php" class="nav-link">Switch to traveling</a>
        <a href="logout.php" class="nav-link">Log out</a>
        <button class="nav-pill" type="button">
          <span style="font-size:16px;">👤</span>
          <span class="nav-pill-icon">
            <?php echo strtoupper(substr($currentUserEmail ?? 'U', 0, 1)); ?>
          </span>
        </button>
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">
    <div class="shell">

      <!-- KIRI: teks -->
      <section>
        <div class="hero-kicker">Become a host</div>
        <h1 class="hero-title">Share your space, experiences, or services with guests worldwide.</h1>
        <div class="hero-sub">
          Start with a simple listing – you can fine-tune photos, amenities, and pricing later in your hosting dashboard.
        </div>

        <div class="pill-small">
          <span>✅</span>
          <span>You're logged in as <?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <ul class="hero-list">
          <li><span>•</span><span>Set up your listing in a few guided steps.</span></li>
          <li><span>•</span><span>Control your calendar, house rules, and nightly price.</span></li>
          <li><span>•</span><span>Only verified and approved listings appear in public search.</span></li>
        </ul>

        <!-- FORM: submit ke wizard basic -->
        <form method="get" action="host-wizard-basic.php" id="typeForm">
          <input type="hidden" name="type" id="typeField" value="home" />
          <button class="btn-primary" type="submit">
            Get started with a home
          </button>
        </form>

        <div class="muted">
          You can add more listings or switch to experiences and services later from your hosting dashboard.
        </div>
      </section>

      <!-- KANAN: kartu jenis listing -->
      <section>
        <div style="margin-bottom:10px;font-size:13px;color:var(--text-muted);">
          Choose what you want to list first:
        </div>
        <div class="card-grid" id="typeGrid">
          <div class="type-card selected" data-type="home">
            <div class="type-icon">🏠</div>
            <div class="type-title">Home</div>
            <div class="type-sub">
              Apartments, villas, private rooms, entire homes, and more.
            </div>
          </div>
          <div class="type-card" data-type="experience">
            <div class="type-icon">🎭</div>
            <div class="type-title">Experience</div>
            <div class="type-sub">
              Local tours, classes, food tastings, and unique activities.
            </div>
            <div class="badge-coming" style="margin-top:6px;">Coming soon</div>
          </div>
          <div class="type-card" data-type="service">
            <div class="type-icon">🧹</div>
            <div class="type-title">Service</div>
            <div class="type-sub">
              Cleaning, airport transfer, personal chef, and more add-ons.
            </div>
            <div class="badge-coming" style="margin-top:6px;">Coming soon</div>
          </div>
        </div>
      </section>

    </div>
  </main>
</div>

<script>
  const typeCards = document.querySelectorAll('.type-card');
  const typeField = document.getElementById('typeField');

  typeCards.forEach(card => {
    card.addEventListener('click', () => {
      const type = card.getAttribute('data-type');

      // Sekarang yang bener2 kita dukung cuma "home"
      if (type !== 'home') {
        alert('Experiences and services will be available soon. For now, start with a home listing.');
        return;
      }

      typeCards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      typeField.value = type; // isi hidden input, ikut terkirim saat submit form
    });
  });
</script>
</body>
</html>
