<?php
// listing-detail.php — OGORooms listing detail page (public)

session_start();
$currentUserEmail = $_SESSION['user_email'] ?? null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $id = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OGORooms – Listing detail</title>
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

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: var(--bg-body);
      color: var(--text-main);
    }

    a { color: inherit; text-decoration: none; }

    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAVBAR */
    .nav {
      position: sticky;
      top: 0;
      z-index: 40;
      backdrop-filter: blur(16px);
      background: rgba(255,255,255,0.95);
      border-bottom: 1px solid rgba(229,231,235,0.8);
    }
    .nav-inner {
      max-width: 1240px;
      margin: 0 auto;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }
    .nav-left { display:flex;align-items:center;gap:10px; }
    .nav-logo-circle {
      width: 34px;height:34px;border-radius:50%;
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

    /* MAIN */
    .main { flex:1; }

    .shell {
      max-width:1240px;
      margin:0 auto;
      padding:18px 20px 40px;
    }

    .heading-row {
      display:flex;
      justify-content:space-between;
      gap:12px;
      align-items:flex-start;
      margin-bottom:14px;
    }
    .heading-main {
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    #listingTitle {
      font-size:24px;
      font-weight:700;
    }
    #listingLocationText {
      font-size:13px;
      color:var(--text-muted);
    }
    .heading-right {
      display:flex;
      gap:10px;
      align-items:center;
      font-size:13px;
      color:#4b5563;
    }
    .heading-right button{
      border:none;background:transparent;
      padding:6px 10px;border-radius:999px;
      cursor:pointer;
    }
    .heading-right button:hover {
      background:#f3f4f6;
    }

    .hero-img-shell {
      border-radius:24px;
      overflow:hidden;
      background:#e5e7eb;
      margin-bottom:18px;
    }
    .hero-img-main {
      position:relative;
      padding-top:52%;
      overflow:hidden;
    }
    .hero-img-main img {
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit:cover;
      transform:scale(1.03);
      transition:transform 0.35s ease;
    }
    .hero-img-main::after{
      content:"";
      position:absolute;
      inset:0;
      box-shadow:inset 0 -80px 120px rgba(0,0,0,0.35);
      pointer-events:none;
    }
    .hero-img-shell:hover .hero-img-main img{
      transform:scale(1.06);
    }

    .layout {
      display:grid;
      grid-template-columns:minmax(0,2.1fr) minmax(280px,1.35fr);
      gap:24px;
    }
    @media(max-width:900px){
      .layout{grid-template-columns:minmax(0,1fr);}
      .nav-center{display:none;}
    }

    .card {
      background:var(--bg-card);
      border-radius:20px;
      border:1px solid rgba(229,231,235,0.9);
      padding:16px 18px 14px;
      box-shadow:0 14px 30px rgba(148,163,184,0.18);
    }

    .card-section-title {
      font-size:15px;
      font-weight:600;
      margin-bottom:6px;
    }
    .card-section-sub {
      font-size:13px;
      color:var(--text-muted);
      margin-bottom:12px;
    }

    .info-row {
      display:flex;
      gap:14px;
      margin-bottom:10px;
      font-size:13px;
      color:#4b5563;
    }
    .info-chip {
      display:inline-flex;
      align-items:center;
      gap:6px;
      border-radius:999px;
      padding:6px 11px;
      background:#f3f4f6;
      font-size:12px;
    }

    .description {
      font-size:14px;
      color:#374151;
      line-height:1.5;
      white-space:pre-wrap;
      margin-bottom:12px;
    }

    .subheadline {
      font-size:13px;
      font-weight:600;
      margin-top:10px;
      margin-bottom:4px;
    }
    .subtext {
      font-size:13px;
      color:var(--text-muted);
    }

    .chip-row {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin:6px 0 16px;
    }

    .chip-row .chip {
      display:inline-flex;
      align-items:center;
      padding:6px 10px;
      border-radius:999px;
      background:#f3f4f6;
      color:#374151;
      font-size:12px;
      font-weight:500;
    }

    .chip-row .chip.empty {
      color:#6b7280;
    }

    .price-row {
      display:flex;
      align-items:baseline;
      gap:6px;
      margin-bottom:6px;
    }
    .price-main {
      font-size:20px;
      font-weight:700;
    }
    .price-unit {
      font-size:13px;
      color:var(--text-muted);
    }

    .booking-grid {
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      border-radius:12px;
      border:1px solid var(--border-subtle);
      overflow:hidden;
      margin-bottom:8px;
    }
    .booking-cell {
      padding:8px 10px;
      border-bottom:1px solid var(--border-subtle);
    }
    .booking-cell + .booking-cell{
      border-left:1px solid var(--border-subtle);
    }
    .booking-label {
      font-size:10px;
      text-transform:uppercase;
      letter-spacing:0.14em;
      color:var(--text-muted);
      margin-bottom:2px;
    }
    .booking-value {
      font-size:13px;
    }
    .booking-cell-full {
      grid-column:1 / span 2;
      padding:8px 10px;
    }

    .btn-primary {
      border-radius:999px;
      border:none;
      padding:9px 16px;
      background:var(--accent);
      color:#fff;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      width:100%;
      margin-top:10px;
      box-shadow:0 10px 24px rgba(178,116,59,0.45);
    }

    .muted {
      font-size:12px;
      color:var(--text-muted);
    }

    .status-banner {
      margin-top:10px;
      border-radius:12px;
      background:var(--pill-bg);
      padding:8px 10px;
      font-size:12px;
      display:flex;
      gap:8px;
      align-items:flex-start;
    }

    .status-dot {
      width:10px;height:10px;border-radius:999px;
    }
    .status-dot-unpub {background:#f97316;}
    .status-dot-rej {background:#ef4444;}
    .status-dot-pub {background:#16a34a;}

    .pill-status {
      display:inline-flex;
      align-items:center;
      gap:6px;
      border-radius:999px;
      padding:4px 9px;
      background:var(--pill-bg);
      font-size:11px;
      color:#4b5563;
    }

    .loading {
      font-size:13px;
      color:var(--text-muted);
      margin-top:4px;
    }
  </style>
</head>
<body>
<div class="page">

  <!-- NAVBAR -->
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
        <button type="button" class="active" onclick="window.location.href='index.php';">Homes</button>
        <button type="button">Experiences</button>
        <button type="button">Services</button>
      </div>

      <div class="nav-right">
        <?php if ($currentUserEmail): ?>
          <a href="host-dashboard.php" class="nav-link">Switch to hosting</a>
          <a href="logout.php" class="nav-link">Log out</a>
          <button class="nav-pill" type="button">
            <span style="font-size:16px;">👤</span>
            <span class="nav-pill-icon">
              <?php echo strtoupper(substr($currentUserEmail, 0, 1)); ?>
            </span>
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

  <!-- MAIN -->
  <main class="main">
    <div class="shell">
      <div class="heading-row">
        <div class="heading-main">
          <h1 id="listingTitle">Loading listing...</h1>
          <div id="listingLocationText">Please wait</div>
        </div>
        <div class="heading-right">
          <button type="button" onclick="window.history.back();">← Back</button>
          <button type="button">Share</button>
          <button type="button">Save</button>
        </div>
      </div>

      <div class="hero-img-shell">
        <div class="hero-img-main">
          <img id="heroImage"
               src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1600"
               alt="Listing cover">
        </div>
      </div>

      <section class="layout">
        <!-- LEFT -->
        <article class="card">
          <div class="card-section-title">Listing details</div>
          <div class="card-section-sub" id="listingSubtitle">—</div>

          <div class="info-row" id="infoRow"></div>

          <div class="description" id="listingDescription">
            Loading description...
          </div>

          <div class="subheadline">What to expect</div>
          <div class="subtext" id="listingWhatToExpect">
            This host will provide more details about amenities, house rules, and check-in after you request to book.
          </div>

          <div class="subheadline">Highlights</div>
          <div class="chip-row" id="listingHighlights">
            <span class="chip empty">Loading highlights...</span>
          </div>

          <div class="subheadline">Arrival details</div>
          <div class="subtext" id="listingArrivalDetails">Check-in details will appear here once the host adds them.</div>

          <div class="subheadline">House rules</div>
          <div class="subtext" id="listingHouseRules">No special house rules have been shared yet.</div>

          <div class="subheadline">Cancellation policy</div>
          <div class="subtext" id="listingCancellation">Flexible · Full refund 1 day prior.</div>

          <div style="margin-top:14px;">
            <span id="statusPill" class="pill-status" style="display:none;"></span>
          </div>
        </article>

        <!-- RIGHT -->
        <aside class="card">
          <div class="price-row">
            <div class="price-main" id="priceMain">Rp0</div>
            <div class="price-unit">night</div>
          </div>
          <div class="muted" id="priceInfo">
            Price set by host. Service fees and taxes may apply.
          </div>

          <div class="booking-grid" style="margin-top:10px;">
            <div class="booking-cell">
              <div class="booking-label">Check in</div>
              <div class="booking-value">Add date</div>
            </div>
            <div class="booking-cell">
              <div class="booking-label">Check out</div>
              <div class="booking-value">Add date</div>
            </div>
            <div class="booking-cell-full">
              <div class="booking-label">Guests</div>
              <div class="booking-value" id="bookingGuests">1 guest</div>
            </div>
          </div>

          <button class="btn-primary" type="button">
            Request to book
          </button>

          <div class="muted" style="margin-top:8px;">
            You won’t be charged yet. We’ll only process the payment after the host accepts your request.
          </div>

          <div id="statusBannerContainer"></div>

        </aside>
      </section>

      <div id="loadingStatus" class="loading">
        Loading listing from server...
      </div>
    </div>
  </main>
</div>

<script>
  const listingId = <?php echo $id; ?> || 0;

  const titleEl       = document.getElementById('listingTitle');
  const locTextEl     = document.getElementById('listingLocationText');
  const subtitleEl    = document.getElementById('listingSubtitle');
  const descEl        = document.getElementById('listingDescription');
  const infoRowEl     = document.getElementById('infoRow');
  const heroImageEl   = document.getElementById('heroImage');
  const priceMainEl   = document.getElementById('priceMain');
  const priceInfoEl   = document.getElementById('priceInfo');
  const bookingGuests = document.getElementById('bookingGuests');
  const statusBannerContainer = document.getElementById('statusBannerContainer');
  const statusPillEl  = document.getElementById('statusPill');
  const loadingStatus = document.getElementById('loadingStatus');
  const whatToExpectEl= document.getElementById('listingWhatToExpect');
  const highlightRow  = document.getElementById('listingHighlights');
  const arrivalDetailsEl = document.getElementById('listingArrivalDetails');
  const houseRulesEl  = document.getElementById('listingHouseRules');
  const cancellationEl= document.getElementById('listingCancellation');

  const cancellationCopy = {
    flexible: 'Flexible · Full refund 1 day prior',
    moderate: 'Moderate · Full refund 5 days prior',
    strict: 'Strict · 50% refund up to 7 days prior'
  };

  function placeholderImageFor(listing) {
    if (listing.cover_photo_url) {
      return listing.cover_photo_url;
    }
    const city  = (listing.city || '').toLowerCase();
    const ptype = (listing.property_type || '').toLowerCase();

    if (city.includes('bali') || city.includes('beach')) {
      return 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600';
    }
    if (ptype.includes('villa')) {
      return 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=1600';
    }
    if (ptype.includes('apartment') || ptype.includes('condo')) {
      return 'https://images.pexels.com/photos/2089698/pexels-photo-2089698.jpeg?auto=compress&cs=tinysrgb&w=1600';
    }
    if (city.includes('jakarta') || city.includes('city')) {
      return 'https://images.pexels.com/photos/325185/pexels-photo-325185.jpeg?auto=compress&cs=tinysrgb&w=1600';
    }
    return 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1600';
  }

  function formatTimeValue(value) {
    if (!value) return '';
    if (value === 'flexible') return 'Flexible – message host';
    const parts = value.split(':');
    const hourRaw = parseInt(parts[0], 10);
    if (!Number.isFinite(hourRaw)) {
      return value;
    }
    const minutes = parts[1] ?? '00';
    const suffix = hourRaw >= 12 ? 'PM' : 'AM';
    const hour = ((hourRaw % 12) || 12);
    return hour + ':' + minutes + ' ' + suffix;
  }

  function formatWindow(value) {
    if (!value) return '';
    if (value === 'flexible') return 'Flexible – message host';
    const parts = value.split('-');
    if (parts.length === 2) {
      return formatTimeValue(parts[0]) + ' – ' + formatTimeValue(parts[1]);
    }
    return value;
  }

  function formatCheckout(value) {
    if (!value) return '';
    return formatTimeValue(value);
  }

  function renderStatusBanner(status, reviewNote) {
    statusBannerContainer.innerHTML = '';
    statusPillEl.style.display = 'none';

    if (!status) return;

    let text = '';
    let note = reviewNote || '';
    let dotClass = '';
    let pillText = '';

    if (status === 'published') {
      text = 'This listing is live and ready for guests to book.';
      dotClass = 'status-dot-pub';
      pillText = 'Published';
    } else if (status === 'in_review') {
      text = 'This listing is currently in review by OGORooms and may not be visible in all searches yet.';
      dotClass = 'status-dot-unpub';
      pillText = 'In review';
    } else if (status === 'draft') {
      text = 'This listing is still in draft. Guests will not be able to book it yet.';
      dotClass = 'status-dot-unpub';
      pillText = 'Draft';
    } else if (status === 'rejected') {
      text = 'This listing was rejected by the admin.';
      if (note) {
        text += ' Reason: ' + note;
      }
      dotClass = 'status-dot-rej';
      pillText = 'Rejected';
    }

    if (pillText) {
      statusPillEl.textContent = pillText;
      statusPillEl.style.display = 'inline-flex';
    }

    if (!text) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'status-banner';
    wrapper.innerHTML = `
      <div class="status-dot ${dotClass}"></div>
      <div>${text}</div>
    `;
    statusBannerContainer.appendChild(wrapper);
  }

  async function loadListing() {
    if (!listingId) {
      titleEl.textContent = 'Listing not found';
      locTextEl.textContent = 'Invalid listing ID';
      if (loadingStatus) loadingStatus.textContent = '';
      return;
    }

    try {
      const res  = await fetch('/ogo-api/listings-get.php?id=' + listingId);
      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        console.error('Non-JSON from listings-get:', text);
        titleEl.textContent = 'Error loading listing';
        locTextEl.textContent = 'Please try again later.';
        if (loadingStatus) loadingStatus.textContent = '';
        return;
      }

      if (json.status !== 'ok') {
        titleEl.textContent = 'Listing unavailable';
        locTextEl.textContent = json.message || 'Unable to load listing.';
        if (loadingStatus) loadingStatus.textContent = '';
        return;
      }

      const l = json.listing || {};
      const st = l.status || 'draft';

      const cityCountry = [l.city, l.country].filter(Boolean).join(', ');
      titleEl.textContent = l.title || l.headline || 'Untitled listing';
      locTextEl.textContent = cityCountry || 'Location not set';

      const propPieces = [];
      if (l.property_type) propPieces.push(l.property_type);
      if (l.room_type)     propPieces.push(l.room_type);
      subtitleEl.textContent = propPieces.join(' · ') || '—';

      infoRowEl.innerHTML = '';
      const guestCount    = Number(l.guests || l.max_guests || 0);
      const bedroomCount  = Number(l.bedrooms || 0);
      const bathroomCount = Number(l.bathrooms || 0);
      const chips = [];
      if (guestCount)    chips.push(guestCount + ' guest' + (guestCount > 1 ? 's' : ''));
      if (bedroomCount)  chips.push(bedroomCount + ' bedroom' + (bedroomCount > 1 ? 's' : ''));
      if (bathroomCount) chips.push(bathroomCount + ' bathroom' + (bathroomCount > 1 ? 's' : ''));
      if (!chips.length) chips.push('Details coming soon');
      chips.forEach(text => {
        const span = document.createElement('span');
        span.className = 'info-chip';
        span.textContent = text;
        infoRowEl.appendChild(span);
      });

      const storyText = l.story || l.description;
      descEl.textContent = storyText || 'This host has not added a full description yet.';

      const price = l.nightly_price !== null && l.nightly_price !== undefined
        ? Number(l.nightly_price)
        : (l.price_nightly ? Number(l.price_nightly) : 0);
      if (price > 0) {
        priceMainEl.textContent = 'Rp ' + price.toLocaleString('id-ID');
        priceInfoEl.textContent = 'Nightly rate set by host. Service fee and taxes will be shown at checkout.';
      } else {
        priceMainEl.textContent = 'Contact host';
        priceInfoEl.textContent = 'This host has not finalized a nightly rate yet.';
      }

      if (guestCount) {
        bookingGuests.textContent = guestCount + ' guest' + (guestCount > 1 ? 's' : '');
      } else {
        bookingGuests.textContent = 'Add guests';
      }

      heroImageEl.src = placeholderImageFor(l);

      if (highlightRow) {
        highlightRow.innerHTML = '';
        if (Array.isArray(l.highlights) && l.highlights.length) {
          l.highlights.forEach(text => {
            const span = document.createElement('span');
            span.className = 'chip';
            span.textContent = text;
            highlightRow.appendChild(span);
          });
        } else {
          const span = document.createElement('span');
          span.className = 'chip empty';
          span.textContent = 'No highlights added yet.';
          highlightRow.appendChild(span);
        }
      }

      const checkinWindowText = formatWindow(l.checkin_window);
      const checkoutText = formatCheckout(l.checkout_time);

      if (arrivalDetailsEl) {
        if (checkinWindowText || checkoutText) {
          const arrivalPieces = [];
          if (checkinWindowText) arrivalPieces.push('Check-in: ' + checkinWindowText);
          if (checkoutText) arrivalPieces.push('Check-out: ' + checkoutText);
          arrivalDetailsEl.textContent = arrivalPieces.join(' · ');
        } else {
          arrivalDetailsEl.textContent = 'The host will share arrival details after you book.';
        }
      }

      if (whatToExpectEl) {
        const expectPieces = [];
        if (l.welcome_message) expectPieces.push(l.welcome_message);
        if (checkinWindowText || checkoutText) {
          const arrivalCopy = [];
          if (checkinWindowText) arrivalCopy.push('Check-in ' + checkinWindowText);
          if (checkoutText) arrivalCopy.push('Check-out ' + checkoutText);
          expectPieces.push(arrivalCopy.join(' · '));
        }
        if (!expectPieces.length) {
          const basePieces = [];
          if (propPieces.length) basePieces.push('a ' + propPieces.join(' · '));
          if (cityCountry) basePieces.push('in ' + cityCountry);
          expectPieces.push('This host will provide more details about amenities and check-in after you request to book' + (basePieces.length ? (', including ' + basePieces.join(' ')) : '') + '.');
        }
        whatToExpectEl.textContent = expectPieces.join(' ');
      }

      if (houseRulesEl) {
        const rules = Array.isArray(l.house_rules) ? l.house_rules.slice() : [];
        if (l.custom_rules) {
          rules.push(l.custom_rules);
        }
        houseRulesEl.textContent = rules.length ? rules.join(' · ') : 'No special house rules have been shared yet.';
      }

      if (cancellationEl) {
        const policyKey = (l.cancellation_policy || '').toLowerCase();
        cancellationEl.textContent = cancellationCopy[policyKey] || cancellationCopy.flexible;
      }

      renderStatusBanner(st, l.rejected_reason || '');

      if (loadingStatus) loadingStatus.textContent = '';

    } catch (err) {
      console.error(err);
      titleEl.textContent = 'Error loading listing';
      locTextEl.textContent = 'Network error. Please try again.';
      if (loadingStatus) loadingStatus.textContent = '';
    }
  }

  document.addEventListener('DOMContentLoaded', loadListing);
</script>
</body>
</html>
