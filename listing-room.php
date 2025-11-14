<?php
// listing-room.php — detailed listing page with booking widget

session_start();
$currentUserEmail = $_SESSION['user_email'] ?? null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $id = 0;
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

$appBasePath   = app_base_uri();
$isPreviewMode = isset($_GET['preview']);

function lr_normalize_date(string $value): string
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

$prefillCheckin  = lr_normalize_date($_GET['checkin'] ?? '');
$prefillCheckout = lr_normalize_date($_GET['checkout'] ?? '');
if ($prefillCheckin !== '' && $prefillCheckout !== '' && $prefillCheckout <= $prefillCheckin) {
    $prefillCheckout = '';
}

$prefillGuests = 1;
if (isset($_GET['guests']) && $_GET['guests'] !== '' && is_numeric($_GET['guests'])) {
    $guestValue = (int) $_GET['guests'];
    if ($guestValue > 0) {
        $prefillGuests = min($guestValue, 32);
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-base-path="<?php echo htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="UTF-8" />
  <title>OGORooms – Room details</title>
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
      --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
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

    .page { min-height:100vh; display:flex; flex-direction:column; }

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
    .nav-center { display:flex;gap:18px;align-items:center;font-size:14px;font-weight:500; }
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

    .main { flex:1; }
    .shell {
      max-width:1240px;
      margin:0 auto;
      padding:18px 20px 40px;
    }

    .heading-row {
      display:flex;
      flex-direction:column;
      gap:6px;
      margin-bottom:16px;
    }
    #roomTitle { font-size:28px;font-weight:700;margin:0; }
    .heading-meta { display:flex;flex-wrap:wrap;gap:12px;color:var(--text-muted);font-size:13px; }
    .heading-meta span { display:inline-flex;align-items:center;gap:6px; }

    .hero-img-shell {
      border-radius:24px;
      overflow:hidden;
      background:#e5e7eb;
      margin-bottom:22px;
    }
    .hero-img-shell img {
      width:100%;
      height:100%;
      display:block;
      object-fit:cover;
    }

    .layout {
      display:grid;
      grid-template-columns:minmax(0,2fr) minmax(300px,1.1fr);
      gap:24px;
    }
    @media(max-width:900px){
      .layout{grid-template-columns:minmax(0,1fr);}
      .nav-center{display:none;}
    }

    .card {
      background:var(--bg-card);
      border-radius:20px;
      border:1px solid rgba(229,231,235,0.8);
      padding:20px 22px;
      box-shadow:0 12px 30px rgba(148,163,184,0.18);
      margin-bottom:18px;
    }
    .card h2 {
      margin:0 0 8px 0;
      font-size:18px;
    }
    .card p { font-size:14px;line-height:1.6;color:var(--text-main); }

    .pill-list {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:10px;
    }
    .pill-item {
      border-radius:999px;
      border:1px solid var(--border-subtle);
      padding:6px 12px;
      font-size:12px;
      background:#f9fafb;
    }

    .booking-card {
      background:var(--bg-card);
      border-radius:20px;
      border:1px solid rgba(229,231,235,0.9);
      box-shadow:0 18px 40px rgba(148,163,184,0.18);
      padding:18px 20px;
      display:flex;
      flex-direction:column;
      gap:14px;
      position:sticky;
      top:90px;
      height:fit-content;
    }
    .price-line {
      display:flex;
      align-items:baseline;
      gap:8px;
    }
    #bookingPrice {
      font-size:22px;
      font-weight:700;
    }
    #bookingPrice span {
      font-size:13px;
      color:var(--text-muted);
      font-weight:500;
    }
    .booking-form {
      display:flex;
      flex-direction:column;
      gap:12px;
    }
    .booking-form label {
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:0.08em;
      color:var(--text-muted);
    }
    .booking-form input, .booking-form select {
      border-radius:12px;
      border:1px solid var(--border-subtle);
      padding:9px 12px;
      font-size:13px;
    }
    .btn-primary {
      border-radius:999px;
      border:none;
      padding:12px 18px;
      background:var(--accent);
      color:#fff;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      box-shadow:0 12px 26px rgba(178,116,59,0.45);
    }
    .btn-primary:disabled {
      opacity:0.6;
      cursor:not-allowed;
      box-shadow:none;
    }
    .btn-secondary {
      border-radius:999px;
      border:1px solid var(--accent);
      padding:12px 18px;
      background:#ffffff;
      color:var(--accent);
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      transition:box-shadow 0.15s ease, transform 0.15s ease;
      box-shadow:0 0 0 rgba(0,0,0,0);
    }
    .btn-secondary:hover:not(:disabled) {
      box-shadow:0 12px 26px rgba(178,116,59,0.25);
      transform:translateY(-1px);
    }
    .btn-secondary:disabled {
      opacity:0.6;
      cursor:not-allowed;
      box-shadow:none;
      transform:none;
    }
    .preview-banner {
      margin-bottom:18px;
      padding:12px 16px;
      border-radius:18px;
      background:#fef3c7;
      border:1px solid #fbbf24;
      color:#92400e;
      font-size:13px;
      box-shadow:0 12px 24px rgba(250,204,21,0.28);
    }
    #bookingSummary {
      font-size:13px;
      color:var(--text-muted);
      line-height:1.5;
      min-height:40px;
    }
    .error-text { color:#b91c1c; }
    .success-text { color:#15803d; }
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
          <span class="nav-brand-sub">HOMES · EXPERIENCES · SERVICES</span>
        </div>
      </div>
      <div class="nav-center">
        <button type="button" onclick="window.location.href='index.php';">Homes</button>
        <button type="button">Experiences</button>
        <button type="button">Services</button>
      </div>
      <div class="nav-right">
        <?php if ($currentUserEmail): ?>
          <a href="host-dashboard.php" class="nav-link">Switch to hosting</a>
          <a href="logout.php" class="nav-link">Log out</a>
          <button class="nav-pill" type="button">
            <span style="font-size:16px;">👤</span>
            <span class="nav-pill-icon"><?php echo strtoupper(substr($currentUserEmail, 0, 1)); ?></span>
          </button>
        <?php else: ?>
          <a href="login.php" class="nav-link">Become a host</a>
          <a href="login.php" class="nav-link">Log in</a>
          <button class="nav-pill" type="button">
            <span style="font-size:16px;">🌐</span>
            <span class="nav-pill-icon">H</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="main">
    <div class="shell">
      <?php if ($isPreviewMode): ?>
        <div class="preview-banner">
          Preview mode: only you and admins can view this listing until it is published. Booking actions are disabled for guests.
        </div>
      <?php endif; ?>
      <div class="heading-row">
        <h1 id="roomTitle">Loading listing...</h1>
        <div class="heading-meta">
          <span id="roomLocation">&nbsp;</span>
          <span id="roomGuests">&nbsp;</span>
        </div>
      </div>

      <div class="hero-img-shell">
        <img id="roomHero" src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1600" alt="Listing hero image">
      </div>

      <div class="layout">
        <section>
          <div class="card">
            <h2>About this stay</h2>
            <p id="roomDescription">We’re fetching the details of this listing for you.</p>
          </div>

          <div class="card" id="highlightsCard" style="display:none;">
            <h2>Highlights</h2>
            <div class="pill-list" id="highlightsList"></div>
          </div>

          <div class="card" id="amenitiesCard" style="display:none;">
            <h2>Amenities</h2>
            <div class="pill-list" id="amenitiesList"></div>
          </div>

          <div class="card" id="storyCard" style="display:none;">
            <h2>Story</h2>
            <p id="roomStory"></p>
          </div>
        </section>

        <aside class="booking-card">
          <div class="price-line">
            <div id="bookingPrice">—</div>
            <div style="font-size:12px;color:var(--text-muted);">per night</div>
          </div>
          <div id="bookingNote" style="font-size:13px;color:var(--text-muted);">Select dates to see availability.</div>
          <div class="booking-form">
            <label for="checkinDate">Check-in</label>
            <input type="date" id="checkinDate" value="<?php echo htmlspecialchars($prefillCheckin, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="checkoutDate">Check-out</label>
            <input type="date" id="checkoutDate" value="<?php echo htmlspecialchars($prefillCheckout, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="guestCount">Guests</label>
            <input type="number" id="guestCount" min="1" max="32" value="<?php echo (int) $prefillGuests; ?>">

            <button type="button" class="btn-primary" id="checkAvailabilityBtn">Check availability</button>
          </div>
          <div id="bookingSummary"></div>
          <button type="button" class="btn-secondary" id="bookNowBtn" disabled>Continue to booking</button>
        </aside>
      </div>
    </div>
  </main>
</div>

<script>
  const listingId = <?php echo (int)$id; ?>;
  const appBasePath = document.documentElement.dataset.basePath || '';
  const isPreviewMode = <?php echo $isPreviewMode ? 'true' : 'false'; ?>;
  const heroImg = document.getElementById('roomHero');
  const titleEl = document.getElementById('roomTitle');
  const locationEl = document.getElementById('roomLocation');
  const guestsEl = document.getElementById('roomGuests');
  const descriptionEl = document.getElementById('roomDescription');
  const storyCard = document.getElementById('storyCard');
  const storyEl = document.getElementById('roomStory');
  const highlightsCard = document.getElementById('highlightsCard');
  const highlightsList = document.getElementById('highlightsList');
  const amenitiesCard = document.getElementById('amenitiesCard');
  const amenitiesList = document.getElementById('amenitiesList');
  const priceEl = document.getElementById('bookingPrice');
  const bookingNote = document.getElementById('bookingNote');
  const summaryEl = document.getElementById('bookingSummary');
  const checkinInput = document.getElementById('checkinDate');
  const checkoutInput = document.getElementById('checkoutDate');
  const guestInput = document.getElementById('guestCount');
  const checkBtn = document.getElementById('checkAvailabilityBtn');
  const bookBtn = document.getElementById('bookNowBtn');

  let nightlyPrice = null;
  let listingCurrency = 'IDR';

  if (isPreviewMode && bookBtn) {
    bookBtn.textContent = 'Preview – publish to enable booking';
    bookBtn.disabled = true;
  }

  function buildAppUrl(path) {
    if (!path) {
      return appBasePath || '';
    }

    if (/^(https?:)?\/\//i.test(path)) {
      return path;
    }

    const trimmed = String(path).replace(/^\/+/, '');
    if (!trimmed) {
      return appBasePath || '';
    }

    if (!appBasePath) {
      return '/' + trimmed;
    }

    return appBasePath + '/' + trimmed;
  }

  function formatCurrency(value) {
    if (!Number.isFinite(value)) {
      return 'Rp —';
    }
    return 'Rp ' + Math.round(value).toLocaleString('id-ID');
  }

  function normalizeCoverUrl(value) {
    if (!value) {
      return '';
    }
    const trimmed = String(value).trim();
    if (!trimmed) {
      return '';
    }
    if (/^(https?:)?\/\//i.test(trimmed)) {
      return trimmed;
    }
    return buildAppUrl(trimmed);
  }

  function placeholderCover(listing) {
    const city = (listing.city || '').toLowerCase();
    if (city.includes('bali') || city.includes('beach')) {
      return 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600';
    }
    return 'https://images.pexels.com/photos/15714503/pexels-photo-15714503.jpeg?auto=compress&cs=tinysrgb&w=1600';
  }

  const listingApiUrl = buildAppUrl('ogo-api/listings-get.php');

  function renderList(container, items) {
    container.innerHTML = '';
    items.forEach(text => {
      const pill = document.createElement('span');
      pill.className = 'pill-item';
      pill.textContent = text;
      container.appendChild(pill);
    });
  }

  function computeNights(checkin, checkout) {
    const start = new Date(checkin);
    const end = new Date(checkout);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
      return 0;
    }
    const diff = end.getTime() - start.getTime();
    return Math.round(diff / (1000 * 60 * 60 * 24));
  }

  function updateSummary() {
    const checkin = checkinInput.value;
    const checkout = checkoutInput.value;
    const guests = parseInt(guestInput.value, 10) || 1;

    if (bookBtn) {
      bookBtn.disabled = true;
      bookBtn.removeAttribute('data-href');
      if (isPreviewMode) {
        bookBtn.title = 'Publish this listing to enable booking.';
      }
    }

    if (!checkin || !checkout) {
      summaryEl.className = '';
      summaryEl.innerHTML = 'Select your check-in and check-out dates to calculate the stay.';
      return;
    }

    const nights = computeNights(checkin, checkout);
    if (nights <= 0) {
      summaryEl.className = 'error-text';
      summaryEl.textContent = 'Check-out must be after check-in.';
      return;
    }

    if (!Number.isFinite(nightlyPrice)) {
      summaryEl.className = '';
      summaryEl.innerHTML = 'Contact the host to confirm pricing for these dates.';
      return;
    }

    const subtotal = nightlyPrice * nights;
    summaryEl.className = 'success-text';
    const summaryCta = isPreviewMode
      ? '<span style="font-size:12px;">Preview only – publish to enable booking.</span>'
      : '<span style="font-size:12px;">Continue to booking to review your reservation.</span>';
    summaryEl.innerHTML = `${nights} night${nights > 1 ? 's' : ''} · ${guests} guest${guests > 1 ? 's' : ''}<br>` +
      `Estimated total: <strong>${formatCurrency(subtotal)}</strong><br>` +
      summaryCta;

    if (bookBtn && !isPreviewMode) {
      const params = new URLSearchParams({
        listing_id: String(listingId),
        checkin,
        checkout,
        guests: String(guests),
      });
      bookBtn.dataset.href = 'booking.php?' + params.toString();
      bookBtn.disabled = false;
    }
  }

  async function loadListing() {
    if (!listingId) {
      titleEl.textContent = 'Listing not found';
      descriptionEl.textContent = 'Please go back and pick another stay.';
      bookingNote.textContent = 'Unable to fetch this listing.';
      checkBtn.disabled = true;
      return;
    }

    try {
      const res = await fetch(listingApiUrl + '?id=' + encodeURIComponent(listingId));
      if (!res.ok) {
        throw new Error('Request failed with status ' + res.status);
      }
      const data = await res.json();
      if (!data || data.status !== 'ok') {
        titleEl.textContent = 'Listing unavailable';
        descriptionEl.textContent = data && data.message ? data.message : 'Please try again later.';
        bookingNote.textContent = 'Unable to fetch this listing.';
        checkBtn.disabled = true;
        return;
      }

      const listing = data.listing;
      const title = listing.title || 'Untitled listing';
      titleEl.textContent = title;

      const locationParts = [];
      if (listing.city) locationParts.push(listing.city);
      if (listing.country) locationParts.push(listing.country);
      locationEl.textContent = locationParts.join(', ') || 'Location to be announced';

      if (listing.guests) {
        guestsEl.textContent = `${listing.guests} guest${listing.guests > 1 ? 's' : ''}`;
      }

      if (listing.description) {
        descriptionEl.textContent = listing.description;
      }

      if (listing.story) {
        storyEl.textContent = listing.story;
        storyCard.style.display = 'block';
      }

      if (Array.isArray(listing.highlights) && listing.highlights.length) {
        renderList(highlightsList, listing.highlights);
        highlightsCard.style.display = 'block';
      }

      if (Array.isArray(listing.amenities) && listing.amenities.length) {
        renderList(amenitiesList, listing.amenities);
        amenitiesCard.style.display = 'block';
      }

      nightlyPrice = listing.nightly_price !== null ? Number(listing.nightly_price) : null;
      listingCurrency = listing.currency_code || 'IDR';

      if (Number.isFinite(nightlyPrice)) {
        priceEl.innerHTML = formatCurrency(nightlyPrice) + ' <span>/ night</span>';
      } else {
        priceEl.innerHTML = 'Price on request';
      }

      bookingNote.textContent = isPreviewMode
        ? 'Preview mode: guests cannot book this stay until it is published.'
        : 'Choose your dates to check availability instantly.';

      const cover = normalizeCoverUrl(listing.cover_photo_url);
      if (cover) {
        heroImg.src = cover;
      } else {
        heroImg.src = placeholderCover(listing);
      }

      updateSummary();
    } catch (err) {
      console.error(err);
      titleEl.textContent = 'Listing unavailable';
      descriptionEl.textContent = 'There was a problem loading this listing. Please try again later.';
      bookingNote.textContent = 'Unable to fetch this listing.';
      checkBtn.disabled = true;
    }
  }

  checkBtn.addEventListener('click', updateSummary);
  checkinInput.addEventListener('change', updateSummary);
  checkoutInput.addEventListener('change', updateSummary);
  guestInput.addEventListener('change', updateSummary);
  if (bookBtn) {
    bookBtn.addEventListener('click', () => {
      const href = bookBtn.dataset.href;
      if (href) {
        window.location.href = href;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', loadListing);
</script>
</body>
</html>
