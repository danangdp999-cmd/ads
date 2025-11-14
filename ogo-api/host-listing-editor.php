<?php
// host-listing-editor.php
// Simple host listing editor (2 kolom: form & summary)
require_once __DIR__ . '/auth-check.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>OGORooms Hosting · Edit listing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* --- basic dark theme, bisa kamu ganti ke CSS globalmu --- */
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
            background: #050814;
            color: #f5f5f5;
        }
        a { color: #f5b66a; text-decoration: none; }

        .page-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 32px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: radial-gradient(circle at top left, #1b1f35 0, #050814 55%);
        }
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-dot {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #f5b66a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .logo-text {
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-size: 11px;
            color: #f1f1f1;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .top-link {
            font-size: 13px;
            opacity: 0.8;
        }
        .pill-button {
            border-radius: 999px;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(10,13,30,0.9);
            color: #f5f5f5;
            font-size: 13px;
            cursor: pointer;
        }

        .page-main {
            flex: 1;
            padding: 32px;
        }

        .page-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .page-title-row h1 {
            margin: 0;
            font-size: 24px;
        }
        .page-subtitle {
            font-size: 13px;
            opacity: 0.7;
            margin-top: 4px;
        }

        .editor-grid {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(280px, 1.4fr);
            gap: 24px;
        }

        .card {
            background: radial-gradient(circle at top left, #171b2b 0, #080b17 55%);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.06);
            padding: 20px 22px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
        }
        .card-header {
            font-size: 13px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 18px;
        }

        .field-group {
            margin-bottom: 14px;
        }
        .field-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .input, .textarea, .select {
            width: 100%;
            box-sizing: border-box;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(7,9,20,0.9);
            color: #f5f5f5;
            padding: 9px 14px;
            font-size: 13px;
            outline: none;
        }
        .input:focus, .textarea:focus, .select:focus {
            border-color: #f5b66a;
            box-shadow: 0 0 0 1px rgba(245,182,106,0.4);
        }
        .textarea {
            border-radius: 18px;
            resize: vertical;
            min-height: 90px;
        }
        .input-row {
            display: flex;
            gap: 10px;
        }
        .input-row .field-group {
            flex: 1;
        }

        .editor-footer {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-primary {
            border-radius: 999px;
            border: none;
            padding: 9px 22px;
            background: #f5b66a;
            color: #1a130a;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary {
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 9px 18px;
            background: transparent;
            color: #f5f5f5;
            font-size: 13px;
            cursor: pointer;
        }

        .summary-title {
            font-size: 14px;
            font-weight: 500;
            margin: 0 0 4px 0;
        }
        .summary-subtitle {
            font-size: 12px;
            opacity: 0.7;
            margin-bottom: 14px;
        }
        .summary-row {
            font-size: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .summary-label {
            opacity: 0.7;
        }
        .summary-value {
            text-align: right;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.18);
        }
        .status-pill.published {
            background: rgba(68,196,133,0.18);
            border-color: rgba(68,196,133,0.6);
            color: #7df5b0;
        }
        .status-pill.in_review {
            background: rgba(246,203,99,0.18);
            border-color: rgba(246,203,99,0.6);
            color: #fbd48a;
        }
        .status-pill.rejected {
            background: rgba(244,108,117,0.18);
            border-color: rgba(244,108,117,0.6);
            color: #ffb3ba;
        }

        #loadingLabel {
            font-size: 12px;
            opacity: 0.6;
            margin-top: 4px;
        }

        @media (max-width: 900px) {
            .page-main { padding: 20px; }
            .editor-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <header class="top-bar">
        <div class="top-bar-left">
            <div class="logo-dot">OG</div>
            <div class="logo-text">OGOROOMS · HOSTING</div>
        </div>
        <div class="top-bar-right">
            <a href="host-dashboard.php" class="top-link">Back to dashboard</a>
        </div>
    </header>

    <main class="page-main">
        <div class="page-title-row">
            <div>
                <h1>Edit listing</h1>
                <div id="loadingLabel" class="page-subtitle">
                    Loading...
                </div>
            </div>
            <div>
                <span style="font-size:12px;opacity:0.6;">Listing ID&nbsp;</span>
                <span style="font-size:13px;font-weight:500;" id="summaryId">#<?php echo $id ?: '-'; ?></span>
            </div>
        </div>

        <div class="editor-grid">
            <!-- LEFT: FORM -->
            <section class="card">
                <div class="card-header">Basics</div>

                <div class="field-group">
                    <div class="field-label">Title</div>
                    <input id="title" class="input" type="text" placeholder="Listing title" />
                </div>

                <div class="field-group">
                    <div class="field-label">Description</div>
                    <textarea id="description" class="textarea"
                              placeholder="Describe your place, vibe, and what guests will love."></textarea>
                </div>

                <div class="field-group">
                    <div class="field-label">Guests, bedrooms, bathrooms</div>
                    <div class="input-row">
                        <div class="field-group">
                            <input id="guests" class="input" type="number" min="1" value="1" placeholder="Guests" />
                        </div>
                        <div class="field-group">
                            <input id="bedrooms" class="input" type="number" min="0" value="0" placeholder="Bedrooms" />
                        </div>
                        <div class="field-group">
                            <input id="bathrooms" class="input" type="number" min="1" value="1" placeholder="Bathrooms" />
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">Nightly price</div>
                    <input id="price" class="input" type="number" min="0" step="1" placeholder="Price per night" />
                </div>

                <div class="field-group">
                    <div class="field-label">Status</div>
                    <select id="statusHost" class="select">
                        <option value="draft">Draft (not visible to guests)</option>
                        <option value="request_publish">Publish (send for review)</option>
                    </select>
                </div>

                <div class="editor-footer">
                    <button class="btn-secondary" type="button"
                            onclick="window.location.href='host-dashboard.php';">
                        Cancel
                    </button>
                    <button class="btn-primary" type="button" id="saveBtn">
                        Save changes
                    </button>
                </div>
            </section>

            <!-- RIGHT: SUMMARY -->
            <aside class="card">
                <div class="card-header">Listing overview</div>
                <h3 class="summary-title" id="listingSubtitle">—</h3>
                <div class="summary-subtitle">
                    This summary is for you only. Guests will see the public listing page.
                </div>

                <div class="summary-row">
                    <div class="summary-label">Property</div>
                    <div class="summary-value" id="summaryProperty">—</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Location</div>
                    <div class="summary-value" id="summaryLocation">—</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Created</div>
                    <div class="summary-value" id="summaryCreatedAt">—</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Price preview</div>
                    <div class="summary-value" id="summaryPricePreview">—</div>
                </div>

                <hr style="border:none;border-top:1px solid rgba(255,255,255,0.06);margin:14px 0;" />

                <div class="summary-row" style="align-items:center;">
                    <div class="summary-label">Status</div>
                    <div class="summary-value">
                        <span id="statusPill" class="status-pill">Draft</span>
                        <button type="button" class="btn-secondary"
                                id="viewReasonBtn"
                                style="margin-left:8px;display:none;padding:4px 10px;font-size:11px;">
                            View reject reason
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<script>
  const listingId = <?php echo (int)$id; ?> || 0;

  let currentDbStatus   = 'draft';   // draft | in_review | published | rejected
  let currentReviewNote = '';

  async function loadListing() {
    const loadingLabel = document.getElementById('loadingLabel');
    if (!listingId) {
      if (loadingLabel) loadingLabel.textContent = 'Invalid listing ID.';
      alert('Listing ID tidak valid.');
      return;
    }

    if (loadingLabel) loadingLabel.textContent = 'Loading...';

    try {
      const res  = await fetch('listings-get.php?id=' + listingId);
      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        if (loadingLabel) loadingLabel.textContent = '';
        alert('Server mengirim non-JSON saat load listing. Lihat console untuk detail.');
        console.log('Response from listings-get.php:', text);
        return;
      }

      if (json.status !== 'ok') {
        if (loadingLabel) loadingLabel.textContent = '';
        alert('Gagal memuat listing: ' + (json.message || 'Unknown error'));
        return;
      }

      const l = json.listing || {};

      // Isi form
      const titleEl = document.getElementById('title');
      if (titleEl) titleEl.value = l.title || '';

      const descEl = document.getElementById('description');
      if (descEl) descEl.value = l.description || '';

      const guestsEl = document.getElementById('guests');
      if (guestsEl) guestsEl.value = l.guests || 1;

      const bedEl = document.getElementById('bedrooms');
      if (bedEl) bedEl.value = l.bedrooms || 0;

      const bathEl = document.getElementById('bathrooms');
      if (bathEl) bathEl.value = l.bathrooms || 1;

      const priceEl = document.getElementById('price');
      if (priceEl) priceEl.value = l.nightly_price || '';

      currentDbStatus   = l.status      || 'draft';
      currentReviewNote = l.review_note || '';

      // Dropdown host
      const statusHostEl = document.getElementById('statusHost');
      if (statusHostEl) {
        let hostChoice = 'draft';
        if (currentDbStatus === 'in_review' || currentDbStatus === 'published') {
          hostChoice = 'request_publish';
          statusHostEl.disabled = true; // tidak bisa ubah lagi setelah dikirim
        } else {
          hostChoice = 'draft';
          statusHostEl.disabled = false;
        }
        statusHostEl.value = hostChoice;
      }

      // Summary kanan
      const subtitleEl = document.getElementById('listingSubtitle');
      if (subtitleEl) {
        const loc = ((l.city || '') + (l.country ? ', ' + l.country : '')).trim();
        subtitleEl.textContent = loc || 'No location set';
      }

      const propEl = document.getElementById('summaryProperty');
      if (propEl) {
        const prop =
          (l.property_type || '') && (l.place_type || '')
            ? (l.property_type + ' · ' + l.place_type)
            : (l.property_type || l.place_type || '—');
        propEl.textContent = prop;
      }

      const locEl = document.getElementById('summaryLocation');
      if (locEl) {
        const fullLoc =
          (l.street || '') +
          (l.city ? ', ' + l.city : '') +
          (l.country ? ', ' + l.country : '');
        locEl.textContent = fullLoc || '—';
      }

      const createdEl = document.getElementById('summaryCreatedAt');
      if (createdEl) createdEl.textContent = l.created_at || '—';

      const idEl = document.getElementById('summaryId');
      if (idEl && l.id) idEl.textContent = '#' + l.id;

      updatePricePreview();
      updateStatusPillFromDb();
      refreshReasonButton();

      if (loadingLabel) loadingLabel.textContent = '';
    } catch (err) {
      if (loadingLabel) loadingLabel.textContent = '';
      alert('Network error saat load listing: ' + err);
    }
  }

  function updatePricePreview() {
    const priceEl = document.getElementById('price');
    const target  = document.getElementById('summaryPricePreview');
    if (!priceEl || !target) return;

    const val = priceEl.value.trim();
    target.textContent = val ? ('Rp ' + val + ' / night') : '—';
  }

  function updateStatusPillFromDb() {
    const pill = document.getElementById('statusPill');
    if (!pill) return;

    pill.classList.remove('published', 'in_review', 'rejected');
    let label = 'Draft';

    if (currentDbStatus === 'in_review') {
      label = 'In review (waiting admin)';
      pill.classList.add('in_review');
    } else if (currentDbStatus === 'published') {
      label = 'Published';
      pill.classList.add('published');
    } else if (currentDbStatus === 'rejected') {
      label = 'Rejected';
      pill.classList.add('rejected');
    }

    pill.textContent = label;
  }

  function refreshReasonButton() {
    const btn = document.getElementById('viewReasonBtn');
    if (!btn) return;

    if (currentDbStatus === 'rejected' && currentReviewNote) {
      btn.style.display = 'inline-flex';
    } else {
      btn.style.display = 'none';
    }
  }

  // event: view reject reason
  const viewReasonBtn = document.getElementById('viewReasonBtn');
  if (viewReasonBtn) {
    viewReasonBtn.addEventListener('click', () => {
      if (currentReviewNote) {
        alert(currentReviewNote);
      } else {
        alert('No reject note from admin yet.');
      }
    });
  }

  const priceEl = document.getElementById('price');
  if (priceEl) {
    priceEl.addEventListener('input', updatePricePreview);
  }

  const saveBtn = document.getElementById('saveBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      if (!listingId) {
        alert('Listing ID tidak valid.');
        return;
      }

      const titleEl = document.getElementById('title');
      const descEl  = document.getElementById('description');
      const guestsEl= document.getElementById('guests');
      const bedEl   = document.getElementById('bedrooms');
      const bathEl  = document.getElementById('bathrooms');
      const priceEl = document.getElementById('price');
      const statusHostEl = document.getElementById('statusHost');

      const title = titleEl ? titleEl.value.trim() : '';
      const desc  = descEl  ? descEl.value.trim()  : '';
      const price = priceEl ? priceEl.value.trim() : '';

      if (!title || !desc || !price) {
        alert('Please fill title, description, and price.');
        return;
      }

      const guests    = guestsEl ? (guestsEl.value || 1) : 1;
      const bedrooms  = bedEl   ? (bedEl.value   || 0) : 0;
      const bathrooms = bathEl  ? (bathEl.value  || 1) : 1;
      const hostChoice= statusHostEl ? statusHostEl.value : 'draft';

      // mapping pilihan host -> status DB
      let statusToSend = currentDbStatus || 'draft';
      if (statusToSend === 'draft' || statusToSend === 'rejected') {
        if (hostChoice === 'request_publish') {
          statusToSend = 'in_review';
        } else {
          statusToSend = 'draft';
        }
      }

      const payload = {
        id: listingId,
        title: title,
        description: desc,
        guests: guests,
        bedrooms: bedrooms,
        bathrooms: bathrooms,
        nightly_price: price,
        status: statusToSend
      };

      try {
        const res  = await fetch('listings-update.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const text = await res.text();
        let json;
        try {
          json = JSON.parse(text);
        } catch (e) {
          alert('Server mengirim non-JSON saat save.\n\n' + text);
          return;
        }

        if (json.status === 'ok') {
          alert('Listing updated.');
          currentDbStatus = statusToSend;
          updateStatusPillFromDb();
          refreshReasonButton();

          if ((statusToSend === 'in_review' || statusToSend === 'published') && statusHostEl) {
            statusHostEl.disabled = true;
          }
        } else {
          alert('Failed to save: ' + (json.message || 'Unknown error'));
        }
      } catch (err) {
        alert('Network error saat save: ' + err);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', loadListing);
</script>
</body>
</html>
