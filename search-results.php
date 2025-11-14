<?php
// search-results.php  — OGORooms search results

session_start();
$currentUserEmail = $_SESSION['user_email'] ?? null;

$rawWhere = isset($_GET['where']) ? trim((string)$_GET['where']) : '';
$displayWhere = $rawWhere !== '' ? htmlspecialchars($rawWhere, ENT_QUOTES, 'UTF-8') : 'anywhere';

function sr_parse_date(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($dt === false) {
        return null;
    }

    return $dt;
}

$checkinDate  = sr_parse_date($_GET['checkin'] ?? '');
$checkoutDate = sr_parse_date($_GET['checkout'] ?? '');
if ($checkinDate && $checkoutDate && $checkoutDate <= $checkinDate) {
    $checkoutDate = null;
}

$checkinValue  = $checkinDate ? $checkinDate->format('Y-m-d') : '';
$checkoutValue = $checkoutDate ? $checkoutDate->format('Y-m-d') : '';

function sr_dates_label(?DateTimeImmutable $start, ?DateTimeImmutable $end): string
{
    if ($start && $end) {
        return $start->format('d M Y') . ' – ' . $end->format('d M Y');
    }
    if ($start) {
        return 'From ' . $start->format('d M Y');
    }
    if ($end) {
        return 'Until ' . $end->format('d M Y');
    }
    return 'Flexible dates';
}

$datesDisplay = sr_dates_label($checkinDate, $checkoutDate);

$guestCount = null;
if (isset($_GET['guests']) && $_GET['guests'] !== '' && is_numeric($_GET['guests'])) {
    $guestValue = (int) $_GET['guests'];
    if ($guestValue > 0) {
        $guestCount = min($guestValue, 32);
    }
}

$guestLabel = $guestCount !== null
    ? $guestCount . ' guest' . ($guestCount === 1 ? '' : 's')
    : 'Any number of guests';

$pageSummaryParts = [];
$pageSummaryParts[] = $displayWhere === 'anywhere'
    ? 'Destination: Anywhere'
    : 'Destination: ' . $displayWhere;
if ($datesDisplay !== 'Flexible dates') {
    $pageSummaryParts[] = 'Dates: ' . $datesDisplay;
}
if ($guestCount !== null) {
    $pageSummaryParts[] = 'Guests: ' . $guestLabel;
}
$pageSummary = implode(' · ', $pageSummaryParts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>OGORooms – Stays in <?php echo $displayWhere; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

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
      padding:22px 20px 40px;
    }

    .page-header {
      display:flex;
      flex-direction:column;
      gap:6px;
      margin-bottom:18px;
    }
    .page-kicker {
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:0.18em;
      color:var(--text-muted);
    }
    .page-title {
      font-size:24px;
      font-weight:700;
    }
    .page-sub {
      font-size:13px;
      color:var(--text-muted);
    }

    .filter-bar {
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      margin-bottom:18px;
      align-items:flex-end;
    }
    .filter-group {
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .filter-label {
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:0.08em;
      color:var(--text-muted);
    }
    .filter-select,
    .filter-input {
      border-radius:12px;
      border:1px solid var(--border-subtle);
      padding:8px 12px;
      font-size:13px;
      background:#ffffff;
      min-width:140px;
    }
    .filter-input {
      width:120px;
    }
    .price-inputs {
      display:flex;
      align-items:center;
      gap:6px;
    }
    .filter-actions {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
    }
    .btn-filter {
      border-radius:999px;
      border:1px solid var(--accent);
      background:var(--accent);
      color:#fff;
      font-size:13px;
      font-weight:600;
      padding:9px 18px;
      cursor:pointer;
      box-shadow:0 12px 26px rgba(178,116,59,0.35);
    }
    .btn-filter.secondary {
      background:#ffffff;
      color:var(--accent);
      border-color:var(--accent);
      box-shadow:none;
    }
    .location-status {
      font-size:12px;
      color:var(--text-muted);
    }

    .layout {
      display:grid;
      grid-template-columns:minmax(0,2.5fr) minmax(260px,1.2fr);
      gap:24px;
    }
    @media(max-width:900px){
      .layout {
        grid-template-columns:minmax(0,1fr);
      }
      .nav-center{display:none;}
    }

    .results-list {
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
      gap:18px;
    }

    .stay-card {
      background:var(--bg-card);
      border-radius:20px;
      box-shadow:0 14px 28px rgba(15, 23, 42, 0.08);
      overflow:hidden;
      cursor:pointer;
      transition:transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stay-card:hover {
      transform:translateY(-3px);
      box-shadow:0 20px 40px rgba(15,23,42,0.15);
    }
    .stay-img-wrap {
      position:relative;
      padding-top:70%;
      overflow:hidden;
    }
    .stay-img {
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit:cover;
      transform:scale(1.02);
      transition:transform 0.25s ease;
    }
    .stay-card:hover .stay-img {
      transform:scale(1.06);
    }
    .stay-tag {
      position:absolute;
      top:10px;
      left:10px;
      padding:4px 9px;
      font-size:10px;
      border-radius:999px;
      background:rgba(17,24,39,0.76);
      color:#fff;
      text-transform:uppercase;
      letter-spacing:0.08em;
    }
    .stay-fav {
      position:absolute;
      top:10px;
      right:10px;
      width:30px;height:30px;border-radius:999px;
      background:rgba(255,255,255,0.95);
      display:flex;align-items:center;justify-content:center;
      font-size:15px;color:#9ca3af;
    }
    .stay-body {
      padding:10px 12px 12px;
    }
    .stay-row-top {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      margin-bottom:4px;
      gap:10px;
    }
    .stay-title {
      font-size:14px;
      font-weight:600;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }
    .stay-rating {
      font-size:12px;
      color:#4b5563;
      display:flex;
      align-items:center;
      gap:3px;
    }
    .stay-meta {
      font-size:12px;
      color:var(--text-muted);
      margin-bottom:4px;
    }
    .stay-badge {
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:11px;
      font-weight:600;
      background:#dbeafe;
      color:#1d4ed8;
      border-radius:999px;
      padding:4px 10px;
      margin-bottom:6px;
    }
    .stay-price {
      font-size:14px;
      font-weight:600;
    }
    .stay-price span {
      font-weight:400;
      color:var(--text-muted);
      font-size:12px;
    }
    .stay-price-strike {
      font-size:12px;
      color:#9ca3af;
      text-decoration:line-through;
      margin-left:8px;
    }

    .sidebar-card {
      background:var(--bg-card);
      border-radius:20px;
      border:1px solid rgba(229,231,235,0.8);
      padding:16px 16px 14px;
      box-shadow:0 12px 30px rgba(148,163,184,0.18);
      font-size:13px;
    }
    .sidebar-title {
      font-size:14px;
      font-weight:600;
      margin-bottom:4px;
    }
    .sidebar-sub {
      font-size:12px;
      color:var(--text-muted);
      margin-bottom:10px;
    }
    .sidebar-row {
      display:flex;
      justify-content:space-between;
      margin-bottom:6px;
    }
    .sidebar-row-label {
      color:var(--text-muted);
    }
    .sidebar-badge {
      display:inline-flex;
      align-items:center;
      gap:6px;
      border-radius:999px;
      padding:5px 9px;
      background:var(--pill-bg);
      font-size:11px;
    }
    .btn-primary {
      border-radius:999px;
      border:none;
      padding:8px 16px;
      background:var(--accent);
      color:#fff;
      font-size:13px;
      font-weight:600;
      cursor:pointer;
      box-shadow:0 12px 26px rgba(178,116,59,0.45);
    }
    .muted {
      font-size:12px;
      color:var(--text-muted);
    }

    .empty {
      padding:18px 4px;
      font-size:14px;
      color:var(--text-muted);
    }

    @media(max-width:640px){
      .shell{padding-inline:14px;}
      .page-title{font-size:20px;}
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
      <header class="page-header">
        <div class="page-kicker">Homes</div>
        <h1 class="page-title">Stays in <?php echo $displayWhere; ?></h1>
        <div class="page-sub" id="pageSub">
          <?php echo htmlspecialchars($pageSummary, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </header>

      <div class="filter-bar">
        <div class="filter-group">
          <label class="filter-label" for="sortSelect">Sort by</label>
          <select id="sortSelect" class="filter-select">
            <option value="recommended">Recommended</option>
            <option value="price_low">Lowest price</option>
            <option value="price_high">Highest price</option>
            <option value="best">Best deals</option>
            <option value="nearest">Nearest</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Price range (IDR)</label>
          <div class="price-inputs">
            <input type="number" id="minPriceInput" class="filter-input" placeholder="Min">
            <span style="color:#9ca3af;">–</span>
            <input type="number" id="maxPriceInput" class="filter-input" placeholder="Max">
          </div>
        </div>
        <div class="filter-actions">
          <button type="button" class="btn-filter" id="applyFiltersBtn">Apply filters</button>
          <button type="button" class="btn-filter secondary" id="useLocationBtn">Use my location</button>
          <span class="location-status" id="locationStatus"></span>
        </div>
      </div>

      <section class="layout">
        <!-- LEFT: results -->
        <div>
          <div id="resultsList" class="results-list"></div>
          <div id="emptyState" class="empty" style="display:none;">
            No published listings match your search yet. Try another destination or check again later.
          </div>
        </div>

        <!-- RIGHT: sidebar -->
        <aside class="sidebar-card">
          <div class="sidebar-title">OGORooms Guarantee</div>
          <div class="sidebar-sub">
            Book with hosts who have been verified and reviewed by guests.
          </div>
          <div class="sidebar-row">
            <div class="sidebar-row-label">Filters applied</div>
            <div class="sidebar-badge">
              <span>📍</span>
              <span id="badgeWhere">
                <?php echo $displayWhere === 'anywhere' ? 'Anywhere' : $displayWhere; ?>
              </span>
            </div>
          </div>
          <div class="sidebar-row">
            <div class="sidebar-row-label">Dates</div>
            <div class="sidebar-badge">
              <span>🗓</span>
              <span><?php echo htmlspecialchars($datesDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <div class="sidebar-row">
            <div class="sidebar-row-label">Guests</div>
            <div class="sidebar-badge">
              <span>👥</span>
              <span><?php echo htmlspecialchars($guestLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <div style="margin:10px 0 14px;">
            <button class="btn-primary" type="button" onclick="window.location.href='index.php';">
              Adjust search
            </button>
          </div>
          <div class="muted">
            Only listings that are <strong>published</strong> by the host and approved by admin will appear here.
          </div>
        </aside>
      </section>
    </div>
  </main>
</div>

<script>
  const queryWhere = <?php echo json_encode($rawWhere, JSON_UNESCAPED_UNICODE); ?> || "";
  const queryCheckin = <?php echo json_encode($checkinValue, JSON_UNESCAPED_UNICODE); ?> || "";
  const queryCheckout = <?php echo json_encode($checkoutValue, JSON_UNESCAPED_UNICODE); ?> || "";
  const queryGuests = <?php echo $guestCount !== null ? (int) $guestCount : 'null'; ?>;

  const resultsList = document.getElementById('resultsList');
  const emptyState  = document.getElementById('emptyState');
  const pageSub     = document.getElementById('pageSub');
  const sortSelect  = document.getElementById('sortSelect');
  const minPriceInput = document.getElementById('minPriceInput');
  const maxPriceInput = document.getElementById('maxPriceInput');
  const applyFiltersBtn = document.getElementById('applyFiltersBtn');
  const useLocationBtn  = document.getElementById('useLocationBtn');
  const locationStatus  = document.getElementById('locationStatus');

  let currentSort = sortSelect ? sortSelect.value : 'recommended';
  let currentMinPrice = '';
  let currentMaxPrice = '';
  let userLat = null;
  let userLng = null;
  let usingLocation = false;

  function placeholderImageFor(listing) {
    const city  = (listing.city || '').toLowerCase();
    const ptype = (listing.property_type || '').toLowerCase();

    if (city.includes('bali') || city.includes('beach')) {
      return 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1200';
    }
    if (ptype.includes('villa')) {
      return 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=1200';
    }
    if (ptype.includes('apartment') || ptype.includes('condo')) {
      return 'https://images.pexels.com/photos/2089698/pexels-photo-2089698.jpeg?auto=compress&cs=tinysrgb&w=1200';
    }
    if (city.includes('jakarta') || city.includes('city')) {
      return 'https://images.pexels.com/photos/325185/pexels-photo-325185.jpeg?auto=compress&cs=tinysrgb&w=1200';
    }
    return 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1200';
  }

  function encodePathSegments(path) {
    return path.split('/').map(segment => {
      if (!segment) {
        return segment;
      }
      try {
        return encodeURIComponent(decodeURIComponent(segment));
      } catch (err) {
        return encodeURIComponent(segment);
      }
    }).join('/');
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
    const cleaned = trimmed.replace(/^\/+/, '');
    const safePath = encodePathSegments(cleaned);
    return '/' + safePath;
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function escapeAttr(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
  }

  function coverImageFor(listing) {
    const normalized = normalizeCoverUrl(listing.cover_photo_url);
    if (normalized) {
      return normalized;
    }
    return placeholderImageFor(listing);
  }

  const summaryDateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric'
  });

  function formatDateLabel(value) {
    if (!value) {
      return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }
    return summaryDateFormatter.format(date);
  }

  function buildDateSummary(start, end) {
    const startLabel = formatDateLabel(start);
    const endLabel = formatDateLabel(end);
    if (startLabel && endLabel) {
      return startLabel + ' – ' + endLabel;
    }
    if (startLabel) {
      return 'from ' + startLabel;
    }
    if (endLabel) {
      return 'until ' + endLabel;
    }
    return '';
  }

  function sortLabel(value) {
    switch (value) {
      case 'price_low':
        return 'lowest price';
      case 'price_high':
        return 'highest price';
      case 'best':
        return 'best deals';
      case 'nearest':
        return 'nearest';
      default:
        return 'recommended';
    }
  }

  function buildSummary(count) {
    const parts = [];
    parts.push(count + ' stay' + (count !== 1 ? 's' : '') + ' found');
    if (queryWhere) {
      parts.push('destination ' + queryWhere);
    } else {
      parts.push('destination anywhere');
    }
    parts.push('sorted by ' + sortLabel(currentSort));
    const dateSummary = buildDateSummary(queryCheckin, queryCheckout);
    if (dateSummary) {
      parts.push('dates ' + dateSummary);
    }
    if (Number.isFinite(queryGuests) && queryGuests > 0) {
      parts.push(queryGuests + ' guest' + (queryGuests > 1 ? 's' : ''));
    }
    if (currentMinPrice || currentMaxPrice) {
      const minNumber = Number(currentMinPrice);
      const maxNumber = Number(currentMaxPrice);
      const minText = currentMinPrice && Number.isFinite(minNumber)
        ? 'Rp ' + minNumber.toLocaleString('id-ID')
        : '';
      const maxText = currentMaxPrice && Number.isFinite(maxNumber)
        ? 'Rp ' + maxNumber.toLocaleString('id-ID')
        : '';
      parts.push('price ' + (minText || 'any') + ' – ' + (maxText || 'any'));
    }
    if (usingLocation && userLat !== null && userLng !== null) {
      parts.push('near your location');
    }
    return parts.join(' · ');
  }

  function updateLocationStatus(message) {
    if (locationStatus) {
      locationStatus.textContent = message || '';
    }
    if (useLocationBtn) {
      useLocationBtn.textContent = usingLocation ? 'Clear location' : 'Use my location';
    }
  }

  function applyFilters() {
    currentMinPrice = minPriceInput ? minPriceInput.value.trim() : '';
    currentMaxPrice = maxPriceInput ? maxPriceInput.value.trim() : '';
    loadResults();
  }

  function renderResults(listings) {
    resultsList.innerHTML = "";
    if (!Array.isArray(listings) || listings.length === 0) {
      resultsList.style.display = 'none';
      emptyState.style.display  = 'block';
      pageSub.textContent       = 'No published stays found yet. Try changing the location or filters.';
      return;
    }

    resultsList.style.display = 'grid';
    emptyState.style.display  = 'none';
    pageSub.textContent = buildSummary(listings.length);

    listings.forEach(l => {
      const card = document.createElement('article');
      card.className = 'stay-card';

      const cityCountry = ((l.city || '') + (l.country ? ', ' + l.country : '')).trim();
      const title       = l.title || 'Untitled listing';
      const metaParts  = [];
      if (l.property_type) metaParts.push(l.property_type);
      if (l.room_type) metaParts.push(l.room_type);
      if (l.guests) metaParts.push(l.guests + ' guests');
      const meta = metaParts.join(' · ');

      const priceValue = (l.price_nightly !== null && l.price_nightly !== undefined)
        ? Number(l.price_nightly) : null;
      const strikeValue = (l.nightly_price_strike !== null && l.nightly_price_strike !== undefined)
        ? Number(l.nightly_price_strike) : null;
      const hasPrice = Number.isFinite(priceValue);
      const priceBlock = hasPrice
        ? 'Rp ' + priceValue.toLocaleString('id-ID') + ' <span>night</span>'
        : '<span>Price on request</span>';
      const strikeHtml = (hasPrice && Number.isFinite(strikeValue) && strikeValue > priceValue)
        ? `<span class="stay-price-strike">Rp ${strikeValue.toLocaleString('id-ID')}</span>`
        : '';

      const distanceValue = (l.distance_km !== null && l.distance_km !== undefined)
        ? Number(l.distance_km) : null;
      const distanceLabel = Number.isFinite(distanceValue)
        ? `📍 ${distanceValue.toLocaleString('id-ID', { maximumFractionDigits: 2 })} km`
        : '★ 4.9';

      const discountBadge = l.has_discount ? (l.discount_label || 'Special offer') : null;
      const imgUrl = coverImageFor(l);
      const safeTitle = escapeHtml(title);
      const safeTitleAttr = escapeAttr(title);
      const safeProperty = escapeHtml(l.property_type ? l.property_type : 'Stay');
      const safeCity = cityCountry ? escapeHtml(cityCountry) : 'Location to be announced';
      const safeMeta = meta ? escapeHtml(meta) : '&nbsp;';
      const safeDiscount = discountBadge ? escapeHtml(discountBadge) : '';
      const safeDistance = escapeHtml(distanceLabel);
      const safeImg = escapeAttr(imgUrl);

      card.innerHTML = `
        <div class="stay-img-wrap">
          <img class="stay-img" src="${safeImg}" alt="${safeTitle}">
          <div class="stay-tag">${safeProperty}</div>
          <div class="stay-fav">♡</div>
        </div>
        <div class="stay-body">
          <div class="stay-row-top">
            <div class="stay-title" title="${safeTitleAttr}">${safeTitle}</div>
            <div class="stay-rating">${safeDistance}</div>
          </div>
          <div class="stay-meta">
            ${safeCity}
          </div>
          <div class="stay-meta">
            ${safeMeta}
          </div>
          ${discountBadge ? `<div class="stay-badge">${safeDiscount}</div>` : ''}
          <div class="stay-price">
            ${priceBlock}
            ${strikeHtml}
          </div>
        </div>
      `;

      card.addEventListener('click', () => {
        const params = new URLSearchParams({ id: String(l.id) });
        if (queryCheckin) {
          params.set('checkin', queryCheckin);
        }
        if (queryCheckout) {
          params.set('checkout', queryCheckout);
        }
        if (Number.isFinite(queryGuests) && queryGuests > 0) {
          params.set('guests', String(queryGuests));
        }
        window.location.href = 'listing-room.php?' + params.toString();
      });

      resultsList.appendChild(card);
    });
  }

  async function loadResults() {
    try {
      const params = new URLSearchParams();
      params.set('where', queryWhere || '');
      params.set('sort', currentSort || 'recommended');
      if (queryCheckin) {
        params.set('checkin', queryCheckin);
      }
      if (queryCheckout) {
        params.set('checkout', queryCheckout);
      }
      if (Number.isFinite(queryGuests) && queryGuests > 0) {
        params.set('guests', String(queryGuests));
      }
      if (currentMinPrice) {
        params.set('min_price', currentMinPrice);
      }
      if (currentMaxPrice) {
        params.set('max_price', currentMaxPrice);
      }
      if (usingLocation && userLat !== null && userLng !== null) {
        params.set('lat', String(userLat));
        params.set('lng', String(userLng));
      }

      if (pageSub) {
        pageSub.textContent = 'Loading stays…';
      }

      const res = await fetch('/ogo-api/listings-search.php?' + params.toString());
      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        console.error('Non-JSON from listings-search:', text);
        pageSub.textContent = 'There was a problem loading the results. Please try again.';
        emptyState.style.display = 'block';
        return;
      }
      if (json.status !== 'ok') {
        pageSub.textContent = 'Unable to load listings: ' + (json.message || 'Unknown error');
        emptyState.style.display = 'block';
        return;
      }
      renderResults(json.listings || []);
    } catch (err) {
      console.error(err);
      pageSub.textContent = 'Network error while loading results.';
      emptyState.style.display = 'block';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    updateLocationStatus('');
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        currentSort = sortSelect.value || 'recommended';
        loadResults();
      });
    }

    if (applyFiltersBtn) {
      applyFiltersBtn.addEventListener('click', () => {
        applyFilters();
      });
    }

    if (minPriceInput) {
      minPriceInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          applyFilters();
        }
      });
    }

    if (maxPriceInput) {
      maxPriceInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          applyFilters();
        }
      });
    }

    if (useLocationBtn) {
      useLocationBtn.addEventListener('click', () => {
        if (usingLocation) {
          usingLocation = false;
          userLat = null;
          userLng = null;
          updateLocationStatus('Location filter cleared.');
          loadResults();
          return;
        }
        if (!navigator.geolocation) {
          updateLocationStatus('Geolocation not supported on this device.');
          return;
        }
        updateLocationStatus('Detecting your location…');
        useLocationBtn.disabled = true;
        navigator.geolocation.getCurrentPosition(
          position => {
            useLocationBtn.disabled = false;
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;
            usingLocation = true;
            updateLocationStatus('Showing stays nearest to you.');
            loadResults();
          },
          () => {
            useLocationBtn.disabled = false;
            usingLocation = false;
            updateLocationStatus('Unable to access location.');
          },
          { enableHighAccuracy: false, timeout: 8000 }
        );
      });
    }

    loadResults();
  });
</script>
</body>
</html>
