<?php
session_start();

// Wajib login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$hostId = (int)$_SESSION['user_id'];

// host_type dari query ?type=home|experience|service
$type = $_GET['type'] ?? 'home';
$allowedTypes = ['home', 'experience', 'service'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'home';
}

$hostTypeLabels = [
    'home'        => 'You’re hosting a place to stay.',
    'experience'  => 'You’re hosting an experience or activity.',
    'service'     => 'You’re offering a travel service.',
];

$hostTypeNice = $hostTypeLabels[$type] ?? 'Tell us about what you want to host.';
$stageLeadBasics = $hostTypeNice . ' We’ll ask a few simple questions one by one. Your answers will help guests understand what you’re offering.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Become a host · OGORooms</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --og-bg: #ffffff;
            --og-bg-soft: #f7f7f8;
            --og-border-subtle: #e4e5ea;
            --og-text-main: #111827;
            --og-text-muted: #6b7280;
            --og-accent: #c57a44;
            --og-accent-dark: #a15f2e;
            --og-radius-xl: 20px;
            --og-radius-full: 999px;
            --og-shadow-soft: 0 18px 50px rgba(15, 23, 42, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--og-bg);
            color: var(--og-text-main);
        }

        a { text-decoration: none; color: inherit; }

        /* HEADER */
        .og-header {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(228,229,234,0.9);
        }
        .og-header-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .og-logo-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .og-logo-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fbbf77;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.05em;
        }
        .og-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .og-logo-main {
            font-weight: 700;
            font-size: 17px;
        }
        .og-logo-sub {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--og-text-muted);
        }
        .og-nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 14px;
        }
        .og-nav-link {
            position: relative;
            padding: 6px 0;
            color: #4b5563;
        }
        .og-nav-link-active {
            color: #111827;
            font-weight: 600;
        }
        .og-nav-link-active::after {
            content: "";
            position: absolute;
            left: 0; right: 0;
            bottom: -4px;
            margin: 0 auto;
            width: 24px;
            height: 3px;
            border-radius: 999px;
            background: var(--og-accent);
        }
        .og-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .header-text-link {
            padding: 6px 10px;
            border-radius: 999px;
            color: #4b5563;
        }
        .header-pill {
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .header-avatar {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #111827;
            color: #f9fafb;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* PAGE SHELL */
        .wizard-shell {
            max-width: 900px;
            margin: 32px auto 64px;
            padding: 0 20px;
        }

        .wizard-breadcrumb {
            font-size: 13px;
            color: var(--og-text-muted);
            margin-bottom: 10px;
        }

        .wizard-main-steps {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 24px;
        }
        .wizard-title-wrap {
            max-width: 520px;
        }
        .wizard-big-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.04em;
            margin-bottom: 6px;
        }
        .wizard-subtitle {
            font-size: 14px;
            color: var(--og-text-muted);
        }
        .wizard-step-strip {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12px;
            color: var(--og-text-muted);
            min-width: 220px;
        }
        .strip-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .strip-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--og-border-subtle);
        }
        .strip-text strong {
            font-weight: 600;
            color: #111827;
        }
        .strip-row.active .strip-dot {
            background: var(--og-accent);
        }

        /* WIZARD FRAME */
        .wizard-frame {
            background: var(--og-bg-soft);
            border-radius: 26px;
            border: 1px solid var(--og-border-subtle);
            box-shadow: var(--og-shadow-soft);
            padding: 26px 24px 20px;
        }

        .pill-host-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: var(--og-accent-dark);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 18px;
        }

        .mini-steps {
            font-size: 12px;
            color: var(--og-text-muted);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .mini-steps span strong {
            color: #111827;
        }

        .wizard-slide {
            display: none;
        }
        .wizard-slide.active {
            display: block;
        }

        .wizard-stage {
            display: none;
        }
        .wizard-stage.active {
            display: block;
        }

        .stage-header {
            margin-bottom: 20px;
        }
        .stage-title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .stage-subtitle {
            font-size: 14px;
            color: var(--og-text-muted);
            max-width: 560px;
        }

        .stage-section {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid var(--og-border-subtle);
            padding: 18px 18px 20px;
            margin-bottom: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .stage-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px;
        }
        .stage-section p {
            font-size: 13px;
            color: var(--og-text-muted);
            margin: 0 0 12px;
        }

        .photo-drop {
            display: block;
            text-align: center;
            border: 2px dashed var(--og-border-subtle);
            border-radius: 18px;
            padding: 28px 18px;
            background: #ffffff;
            color: var(--og-text-muted);
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .photo-drop strong {
            display: block;
            font-size: 14px;
            color: #111827;
            margin-bottom: 6px;
        }
        .photo-drop input {
            display: none;
        }
        .photo-drop:hover {
            border-color: var(--og-accent);
            box-shadow: 0 10px 28px rgba(197, 122, 68, 0.16);
        }

        .photo-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }
        .photo-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }
        .photo-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }
        .photo-item button {
            position: absolute;
            top: 6px;
            right: 6px;
            border: none;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            background: rgba(17, 24, 39, 0.78);
            color: #ffffff;
            cursor: pointer;
        }
        .photo-cover-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,255,255,0.92);
            color: #111827;
        }

        .highlight-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .highlight-chip {
            border-radius: 999px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            padding: 6px 13px;
            font-size: 12px;
            color: #374151;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .highlight-chip.active {
            background: var(--og-accent);
            border-color: var(--og-accent);
            color: #ffffff;
            box-shadow: 0 12px 26px rgba(197, 122, 68, 0.35);
        }

        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            border-radius: 14px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            padding: 9px 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .amenity-item input {
            width: 16px;
            height: 16px;
        }

        .stage-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .stage-footer-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .preview-card {
            border-radius: 18px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
        }
        .preview-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .preview-line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 13px;
            color: #374151;
            margin-bottom: 6px;
        }
        .preview-line span.value {
            font-weight: 600;
            color: #111827;
        }

        .rule-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
        }
        .rule-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .rule-item input {
            width: 16px;
            height: 16px;
        }

        .chip-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .chip-preview span {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #312e81;
            font-size: 11px;
        }

        .strip-row.active {
            color: #111827;
            font-weight: 500;
        }
        .strip-row.completed .strip-dot {
            background: #9ca3af;
        }

        .stage-divider {
            height: 1px;
            background: var(--og-border-subtle);
            margin: 18px 0;
        }

        .slide-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .slide-subtitle {
            font-size: 14px;
            color: var(--og-text-muted);
            margin-bottom: 18px;
            max-width: 520px;
        }

        .field-label {
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: #374151;
        }
        .input-text,
        .input-number,
        .input-textarea {
            width: 100%;
            border-radius: 14px;
            border: 1px solid var(--og-border-subtle);
            padding: 10px 12px;
            font-size: 14px;
            background: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .input-textarea {
            min-height: 90px;
            resize: vertical;
        }
        .input-number {
            -moz-appearance: textfield;
        }
        .input-number::-webkit-outer-spin-button,
        .input-number::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .input-text:focus,
        .input-number:focus,
        .input-textarea:focus {
            outline: none;
            border-color: var(--og-accent);
            box-shadow: 0 0 0 1px rgba(197,122,68,0.18);
        }

        .inline-two {
            display: flex;
            gap: 12px;
        }
        .inline-two > div { flex: 1; }

        .slide-helper {
            font-size: 12px;
            color: var(--og-text-muted);
            margin-top: 6px;
        }

        /* CARD OPTIONS (Property type, Place type) */
        .card-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }
        .card-option {
            border-radius: 16px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            padding: 10px 12px;
            text-align: left;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
        }
        .card-option span.main {
            font-weight: 500;
            color: #111827;
        }
        .card-option span.sub {
            font-size: 12px;
            color: var(--og-text-muted);
        }
        .card-option.selected {
            border-color: var(--og-accent);
            box-shadow: 0 0 0 1px rgba(197,122,68,0.25);
            transform: translateY(-1px);
        }

        /* RADIO ROWS (who else, bathroom type) */
        .radio-row {
            margin-top: 6px;
        }
        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            cursor: pointer;
        }
        .radio-item input {
            width: 16px;
            height: 16px;
        }

        .select-simple {
            width: 100%;
            border-radius: 14px;
            border: 1px solid var(--og-border-subtle);
            padding: 9px 12px;
            font-size: 14px;
            background: #ffffff;
        }

        /* BEDROOM DETAILS */
        .bedroom-list {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .bedroom-card {
            border-radius: 14px;
            border: 1px solid var(--og-border-subtle);
            background: #ffffff;
            padding: 10px 12px;
            font-size: 13px;
        }
        .bedroom-card-title {
            font-weight: 500;
            margin-bottom: 6px;
        }

        /* PRICE PREVIEW */
        .price-preview {
            margin-top: 10px;
            font-size: 13px;
        }
        .price-preview-line {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
        }
        .price-current {
            font-weight: 600;
        }
        .price-current span {
            font-weight: 400;
            color: #6b7280;
        }
        .price-original {
            font-size: 12px;
            color: #9ca3af;
            text-decoration: line-through;
        }
        .price-badge {
            font-size: 11px;
            font-weight: 600;
            background: #dbeafe;      /* biru muda */
            color: #1d4ed8;           /* biru */
            border-radius: 999px;
            padding: 3px 10px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .price-badge img {
            width: 14px;
            height: 14px;
            object-fit: contain;
            display: block;
}


        /* FOOTER */
        .wizard-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid var(--og-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .btn-secondary {
            border-radius: var(--og-radius-full);
            border: 1px solid var(--og-border-subtle);
            padding: 9px 18px;
            background: #ffffff;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary {
            border: none;
            border-radius: var(--og-radius-full);
            background: var(--og-accent);
            color: #ffffff;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 12px 30px rgba(197,122,68,0.45);
            transition: background 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
        }
        .btn-primary:hover {
            background: var(--og-accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 16px 34px rgba(197,122,68,0.55);
        }
        .btn-primary[disabled] {
            opacity: 0.6;
            cursor: wait;
            box-shadow: none;
        }
        .footer-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--og-text-muted);
        }

        .msg-error {
            font-size: 13px;
            color: #b91c1c;
            margin-top: 6px;
        }
        .msg-success {
            font-size: 13px;
            color: #15803d;
            margin-top: 6px;
        }

        @media (max-width: 720px) {
            .og-header-inner {
                padding-inline: 12px;
            }
            .wizard-shell {
                padding: 0 16px;
            }
            .wizard-main-steps {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<header class="og-header">
    <div class="og-header-inner">
        <a href="index.php" class="og-logo-wrap">
            <div class="og-logo-circle">OG</div>
            <div class="og-logo-text">
                <span class="og-logo-main">OGOROOMS</span>
                <span class="og-logo-sub">HOMES · EXPERIENCES · SERVICES</span>
            </div>
        </a>

        <nav class="og-nav-links">
            <a href="index.php" class="og-nav-link og-nav-link-active">Homes</a>
            <a href="#" class="og-nav-link">Experiences</a>
            <a href="#" class="og-nav-link">Services</a>
        </nav>

        <div class="og-header-right">
            <a href="host-dashboard.php" class="header-text-link">Switch to hosting</a>
            <a href="logout.php" class="header-text-link">Log out</a>
            <button class="header-pill" type="button">
                <span class="header-avatar">D</span>
            </button>
        </div>
    </div>
</header>

<main class="wizard-shell">
    <div class="wizard-breadcrumb">Step <strong id="stageNumber">1</strong> of 3 · <span id="stageTitle">Basics</span></div>

    <div class="wizard-main-steps">
        <div class="wizard-title-wrap">
            <h1 class="wizard-big-title" id="stageHeading">Let’s get the basics of your place.</h1>
            <p class="wizard-subtitle" id="stageLead">
                <?php echo htmlspecialchars($stageLeadBasics); ?>
            </p>
        </div>
        <div class="wizard-step-strip">
            <div class="strip-row active" data-stage="1">
                <div class="strip-dot"></div>
                <div class="strip-text"><strong>1. Basics</strong> · Type, capacity, price</div>
            </div>
            <div class="strip-row" data-stage="2">
                <div class="strip-dot"></div>
                <div class="strip-text"><strong>2. Make it stand out</strong> · Photos, amenities, details</div>
            </div>
            <div class="strip-row" data-stage="3">
                <div class="strip-dot"></div>
                <div class="strip-text"><strong>3. Finish & publish</strong> · Rules, calendar, review</div>
            </div>
        </div>
    </div>

    <section class="wizard-frame">
        <div class="wizard-stage wizard-stage-basics active" data-stage="1">
            <div class="pill-host-type">
                <?php echo strtoupper($hostType); ?> · BASIC SETUP
            </div>

            <div class="mini-steps">
                <span>Question <strong><span id="miniStepIndex">1</span> of 9</strong></span>
            </div>

            <form id="wizardForm">
            <input type="hidden" id="host_type" value="<?php echo htmlspecialchars($hostType); ?>">
            <input type="hidden" id="property_type" value="">
            <input type="hidden" id="place_type" value="">

            <!-- 1: TITLE -->
            <div class="wizard-slide active" data-step="1">
                <div class="slide-title">Give your place a title</div>
                <div class="slide-subtitle">
                    This is the first thing guests will see. Make it short, clear, and memorable.
                </div>
                <label class="field-label" for="title">Listing title</label>
                <input type="text" class="input-text" id="title" placeholder="Cozy studio near Jakarta city center">
                <div class="slide-helper">
                    Example: “Warm, minimalist apartment with city skyline view”.
                </div>
            </div>

            <!-- 2: DESCRIPTION -->
            <div class="wizard-slide" data-step="2">
                <div class="slide-title">Describe your place</div>
                <div class="slide-subtitle">
                    Help guests imagine staying there. Mention the vibe, what’s nearby, and what makes it special.
                </div>
                <label class="field-label" for="description">Description</label>
                <textarea class="input-textarea" id="description"
                          placeholder="Share what guests will love about your place, the neighborhood, and any highlights."></textarea>
                <div class="slide-helper">
                    You can always refine this later in the listing editor.
                </div>
            </div>

            <!-- 3: PROPERTY TYPE -->
            <div class="wizard-slide" data-step="3">
                <div class="slide-title">What type of place are you listing?</div>
                <div class="slide-subtitle">
                    Choose the option that best describes your property.
                </div>
                <div class="card-options" id="propertyTypeOptions">
                    <button type="button" class="card-option" data-value="apartment">
                        <span class="main">Apartment</span>
                        <span class="sub">A self-contained unit in a building or complex.</span>
                    </button>
                    <button type="button" class="card-option" data-value="house">
                        <span class="main">House</span>
                        <span class="sub">A standalone residential home.</span>
                    </button>
                    <button type="button" class="card-option" data-value="villa">
                        <span class="main">Villa</span>
                        <span class="sub">Spacious property, often with outdoor space and pool.</span>
                    </button>
                    <button type="button" class="card-option" data-value="guesthouse">
                        <span class="main">Guesthouse</span>
                        <span class="sub">A smaller place on the same property as a main home.</span>
                    </button>
                    <button type="button" class="card-option" data-value="hotel">
                        <span class="main">Hotel / Boutique stay</span>
                        <span class="sub">Rooms or suites in a hotel-style property.</span>
                    </button>
                    <button type="button" class="card-option" data-value="unique">
                        <span class="main">Unique stay</span>
                        <span class="sub">Cabin, treehouse, tiny home, farm stay, and more.</span>
                    </button>
                </div>
                <div class="slide-helper">
                    This helps guests find the right type of place they’re looking for.
                </div>
            </div>

            <!-- 4: PLACE TYPE -->
            <div class="wizard-slide" data-step="4">
                <div class="slide-title">What type of place will guests have?</div>
                <div class="slide-subtitle">
                    Let guests know if they’ll have the entire place or just a room.
                </div>
                <div class="card-options" id="placeTypeOptions">
                    <button type="button" class="card-option" data-value="entire_place">
                        <span class="main">Entire place</span>
                        <span class="sub">Guests have the whole place to themselves.</span>
                    </button>
                    <button type="button" class="card-option" data-value="private_room">
                        <span class="main">Private room</span>
                        <span class="sub">Guests have their own room and share some spaces.</span>
                    </button>
                    <button type="button" class="card-option" data-value="shared_room">
                        <span class="main">Shared room</span>
                        <span class="sub">Guests sleep in a room or area shared with others.</span>
                    </button>
                    <button type="button" class="card-option" data-value="hotel_room">
                        <span class="main">Hotel room</span>
                        <span class="sub">A room inside a hotel or similar property.</span>
                    </button>
                </div>
            </div>

            <!-- 5: GUESTS -->
            <div class="wizard-slide" data-step="5">
                <div class="slide-title">How many guests can stay?</div>
                <div class="slide-subtitle">
                    Think about beds, sofas, and any extra sleeping options available.
                </div>
                <label class="field-label" for="max_guests">Maximum guests</label>
                <input type="number" class="input-number" id="max_guests" min="1" max="50" value="2">
                <div class="slide-helper">
                    You can adjust this later if you add more beds.
                </div>
            </div>

            <!-- 6: BEDROOMS & BATHROOMS (with bed type per room + dropdown bathroom) -->
            <div class="wizard-slide" data-step="6">
                <div class="slide-title">Bedrooms and bathrooms</div>
                <div class="slide-subtitle">
                    Tell guests how many bedrooms you have, and what kind of beds are in each room.
                </div>

                <div class="inline-two">
                    <div>
                        <label class="field-label" for="bedrooms_count">Bedrooms</label>
                        <select id="bedrooms_count" class="select-simple">
                            <option value="0">0</option>
                            <option value="1" selected>1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10+</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="bathrooms_count">Bathrooms</label>
                        <select id="bathrooms_count" class="select-simple">
                            <option value="0">0</option>
                            <option value="1" selected>1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5+</option>
                        </select>
                    </div>
                </div>

                <div class="bedroom-list" id="bedroomDetails">
                    <!-- Bedroom cards generated by JS -->
                </div>

                <div class="slide-helper">
                    For each bedroom, choose the main bed type: double, twin, or single bed.
                </div>
            </div>

            <!-- 7: BATHROOM TYPE -->
            <div class="wizard-slide" data-step="7">
                <div class="slide-title">What kind of bathroom will guests use?</div>
                <div class="slide-subtitle">
                    Let guests know if the bathroom is private or shared, and how it’s set up.
                </div>
                <label class="field-label">Bathroom type</label>
                <div class="radio-row">
                    <label class="radio-item">
                        <input type="radio" name="bathroom_type" value="private" checked>
                        <span>Private bathroom</span>
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="bathroom_type" value="shared">
                        <span>Shared bathroom</span>
                    </label>
                </div>

                <label class="field-label" for="bathroom_access" style="margin-top:14px;">
                    Bathroom access details
                </label>
                <select id="bathroom_access" class="select-simple">
                    <option value="">Choose an option</option>
                    <option value="ensuite">Attached to the bedroom (ensuite)</option>
                    <option value="across_hall">Across the hall from the bedroom</option>
                    <option value="shared_with_host">Shared with you (the host)</option>
                    <option value="shared_with_others">Shared with other guests</option>
                </select>
                <div class="slide-helper">
                    You can add more detailed notes later in your listing description.
                </div>
            </div>

            <!-- 8: WHO ELSE MIGHT BE THERE -->
            <div class="wizard-slide" data-step="8">
                <div class="slide-title">Who else might be there?</div>
                <div class="slide-subtitle">
                    Let guests know if they’ll share the property with you or others.
                </div>
                <div class="radio-row">
                    <label class="radio-item">
                        <input type="radio" name="other_occupants" value="none" checked>
                        <span>The place is all theirs – no one else will stay there.</span>
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="other_occupants" value="host">
                        <span>They’ll share the property with me.</span>
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="other_occupants" value="others">
                        <span>They’ll share the property with other people (family, roommates, etc.).</span>
                    </label>
                </div>
                <label class="field-label" for="other_occupants_details" style="margin-top:12px;">
                    Add details (optional)
                </label>
                <textarea class="input-textarea" id="other_occupants_details"
                          placeholder="Example: I live downstairs with my partner and a small dog."></textarea>
            </div>

            <!-- 9: PRICE (nightly + original + weekend + original weekend) -->
            <div class="wizard-slide" data-step="9">
                <div class="slide-title">Set your price per night</div>
                <div class="slide-subtitle">
                    Set your standard nightly price and optional discounts, plus a different price for weekends.
                </div>

                <label class="field-label" for="price_nightly">Nightly price</label>
                <input type="number" class="input-number" id="price_nightly" min="0" step="1"
                       placeholder="e.g. 750000">

                <label class="field-label" for="price_nightly_original" style="margin-top:12px;">
                    Original nightly price (before discount)
                </label>
                <input type="number" class="input-number" id="price_nightly_original" min="0" step="1"
                       placeholder="Optional – e.g. 900000">

                <label class="field-label" for="weekend_price" style="margin-top:16px;">
                    Weekend price (Fri–Sun)
                </label>
                <input type="number" class="input-number" id="weekend_price" min="0" step="1"
                       placeholder="e.g. 850000">

                <label class="field-label" for="weekend_price_original" style="margin-top:12px;">
                    Original weekend price (before discount)
                </label>
                <input type="number" class="input-number" id="weekend_price_original" min="0" step="1"
                       placeholder="Optional – e.g. 1000000">

                <div class="price-preview" id="pricePreview">
                    <div class="price-preview-line">
                        <span class="price-current">
                            Set a price to see how guests will see it <span>/ night</span>
                        </span>
                    </div>
                </div>

                <div class="slide-helper">
                    Later you’ll be able to add weekly and monthly discounts, plus smart pricing.
                </div>
            </div>

            <div id="msgError" class="msg-error" style="display:none;"></div>
            <div id="msgSuccess" class="msg-success" style="display:none;"></div>

            <div class="wizard-footer">
                <button type="button" class="btn-secondary" id="backBtn">Back</button>
                <div class="footer-right">
                    <span>Question <strong><span id="miniStepIndexBottom">1</span> of 9</strong></span>
                    <button type="button" class="btn-primary" id="nextBtn">
                        Next
                        <span>→</span>
                    </button>
                </div>
            </div>
        </form>
        </div>

        <div class="wizard-stage" data-stage="2">
            <div class="stage-header">
                <div class="pill-host-type"><?php echo strtoupper($hostType); ?> · STAND OUT</div>
                <h2 class="stage-title">Show guests what makes it special</h2>
                <p class="stage-subtitle">
                    Add photos, highlight amenities, and tell the story behind your place. Great visuals help guests fall in love with it.
                </p>
            </div>

            <form id="stage2Form">
                <div class="stage-section">
                    <h3>Photos</h3>
                    <p>Upload bright, high-resolution photos. We recommend at least three to give guests a complete picture.</p>
                    <label class="photo-drop" for="photoInput">
                        <strong>Upload photos</strong>
                        <span>Drag & drop or click to select images (JPG/PNG, up to 10 MB each).</span>
                        <input type="file" id="photoInput" accept="image/*" multiple>
                    </label>
                    <div id="photoList" class="photo-list"></div>
                </div>

                <div class="stage-section">
                    <h3>Highlights</h3>
                    <p>Select the highlights that best describe your space. These appear near the top of your listing.</p>
                    <div id="highlightChips" class="highlight-grid">
                        <button type="button" class="highlight-chip" data-highlight="great_location">Great location</button>
                        <button type="button" class="highlight-chip" data-highlight="city_view">City skyline view</button>
                        <button type="button" class="highlight-chip" data-highlight="fast_wifi">Fast Wi‑Fi</button>
                        <button type="button" class="highlight-chip" data-highlight="self_check_in">Self check-in</button>
                        <button type="button" class="highlight-chip" data-highlight="workspace">Dedicated workspace</button>
                        <button type="button" class="highlight-chip" data-highlight="parking">Free parking</button>
                        <button type="button" class="highlight-chip" data-highlight="pet_friendly">Pet friendly</button>
                        <button type="button" class="highlight-chip" data-highlight="long_stays">Great for long stays</button>
                    </div>
                </div>

                <div class="stage-section">
                    <h3>Amenities</h3>
                    <p>Let guests know what’s included during their stay.</p>
                    <div class="amenity-grid">
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="wifi"> <span>Wi‑Fi</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="air_conditioning"> <span>Air conditioning</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="kitchen"> <span>Full kitchen</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="washing_machine"> <span>Washer</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="dryer"> <span>Dryer</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="tv"> <span>Smart TV</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="workspace"> <span>Workspace</span></label>
                        <label class="amenity-item"><input type="checkbox" name="amenities[]" value="balcony"> <span>Balcony or patio</span></label>
                    </div>
                </div>

                <div class="stage-section">
                    <h3>Listing story</h3>
                    <p>Add a short paragraph that brings your place to life.</p>
                    <label class="field-label" for="stage2Headline">Short headline</label>
                    <input type="text" class="input-text" id="stage2Headline" placeholder="City views from every window">
                    <label class="field-label" for="stage2Description" style="margin-top:12px;">Detailed story</label>
                    <textarea class="input-textarea" id="stage2Description" placeholder="Share a few sentences about the design, atmosphere, and what guests love most."></textarea>
                </div>

                <div id="stage2Error" class="msg-error" style="display:none;"></div>
                <div id="stage2Success" class="msg-success" style="display:none;"></div>

                <div class="stage-footer">
                    <button type="button" class="btn-secondary" id="stage2BackBtn">Back to basics</button>
                    <div class="stage-footer-right">
                        <button type="button" class="btn-primary" id="stage2NextBtn">Save & Continue</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="wizard-stage" data-stage="3">
            <div class="stage-header">
                <div class="pill-host-type"><?php echo strtoupper($hostType); ?> · FINISHING TOUCHES</div>
                <h2 class="stage-title">Set expectations and get ready to publish</h2>
                <p class="stage-subtitle">
                    Review the essentials, add house rules, and choose whether to save a draft or submit for review.
                </p>
            </div>

            <form id="stage3Form">
                <div class="stage-section">
                    <h3>House rules</h3>
                    <p>Pick the rules that guests need to know before booking.</p>
                    <div class="rule-list">
                        <label class="rule-item"><input type="checkbox" value="no_smoking" name="house_rules[]"> <span>No smoking</span></label>
                        <label class="rule-item"><input type="checkbox" value="no_pets" name="house_rules[]"> <span>No pets</span></label>
                        <label class="rule-item"><input type="checkbox" value="no_parties" name="house_rules[]"> <span>No parties or events</span></label>
                        <label class="rule-item"><input type="checkbox" value="quiet_hours" name="house_rules[]"> <span>Quiet hours after 10 PM</span></label>
                        <label class="rule-item"><input type="checkbox" value="suitable_children" name="house_rules[]"> <span>Suitable for children</span></label>
                    </div>
                    <label class="field-label" for="stage3CustomRules" style="margin-top:12px;">Additional notes (optional)</label>
                    <textarea class="input-textarea" id="stage3CustomRules" placeholder="Share any extra expectations such as pet policies or community guidelines."></textarea>
                </div>

                <div class="stage-section">
                    <h3>Arrival details</h3>
                    <p>Help guests plan their arrival and departure.</p>
                    <div class="inline-two">
                        <div>
                            <label class="field-label" for="checkinWindow">Check-in window</label>
                            <select class="select-simple" id="checkinWindow">
                                <option value="15:00-21:00">3:00 PM – 9:00 PM</option>
                                <option value="14:00-20:00">2:00 PM – 8:00 PM</option>
                                <option value="flexible">Flexible – message me</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="checkoutTime">Check-out time</label>
                            <select class="select-simple" id="checkoutTime">
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="flexible">Flexible – message me</option>
                            </select>
                        </div>
                    </div>
                    <label class="field-label" for="welcomeMessage" style="margin-top:12px;">Welcome message</label>
                    <textarea class="input-textarea" id="welcomeMessage" placeholder="Give guests a warm welcome and any arrival tips they should know."></textarea>
                </div>

                <div class="stage-section">
                    <h3>Listing preview</h3>
                    <div class="preview-card">
                        <div class="preview-header" id="previewTitle">—</div>
                        <div class="preview-line"><span>Guests</span><span class="value" id="previewGuests">—</span></div>
                        <div class="preview-line"><span>Bedrooms</span><span class="value" id="previewBedrooms">—</span></div>
                        <div class="preview-line"><span>Bathrooms</span><span class="value" id="previewBathrooms">—</span></div>
                        <div class="preview-line"><span>Nightly price</span><span class="value" id="previewPrice">—</span></div>
                        <div class="stage-divider"></div>
                        <div>
                            <div class="field-label" style="margin-bottom:6px;">Highlights</div>
                            <div class="chip-preview" id="previewHighlights"></div>
                        </div>
                    </div>
                </div>

                <div class="stage-section">
                    <h3>Publishing</h3>
                    <p>Choose how you want to move forward.</p>
                    <label class="field-label" for="cancellationPolicy">Cancellation policy</label>
                    <select class="select-simple" id="cancellationPolicy">
                        <option value="flexible">Flexible · Full refund 1 day prior</option>
                        <option value="moderate">Moderate · Full refund 5 days prior</option>
                        <option value="strict">Strict · 50% refund up to 7 days prior</option>
                    </select>
                </div>

                <div id="stage3Error" class="msg-error" style="display:none;"></div>
                <div id="stage3Success" class="msg-success" style="display:none;"></div>

                <div class="stage-footer">
                    <button type="button" class="btn-secondary" id="stage3BackBtn">Back to photos</button>
                    <div class="stage-footer-right">
                        <button type="button" class="btn-secondary" id="stage3SaveDraftBtn">Save draft</button>
                        <button type="button" class="btn-primary" id="stage3SubmitBtn">Submit for review</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
(function () {
    const TOTAL_STEPS = 9;
    const STAGE_COUNT = 3;
    let currentStep = 1;
    let currentStage = 1;
    let createdListingId = null;
    let photoIdCounter = 0;

    const HOST_ID   = <?php echo (int)$hostId; ?>;
    const HOST_TYPE = <?php echo json_encode($type, JSON_UNESCAPED_SLASHES); ?>;

    const STAGE_META = {
        1: {
            title: 'Basics',
            heading: 'Let\u2019s get the basics of your place.',
            lead: <?php echo json_encode($stageLeadBasics, JSON_UNESCAPED_UNICODE); ?>
        },
        2: {
            title: 'Make it stand out',
            heading: 'Bring your place to life.',
            lead: 'Add photos, highlights, and amenities that show guests why they\u2019ll love staying here.'
        },
        3: {
            title: 'Finish & publish',
            heading: 'Ready to welcome guests?',
            lead: 'Review your settings, add house rules, and choose how you want to finish.'
        }
    };

    const highlightLabels = {
        great_location: 'Great location',
        city_view: 'City skyline view',
        fast_wifi: 'Fast Wi\u2011Fi',
        self_check_in: 'Self check-in',
        workspace: 'Dedicated workspace',
        parking: 'Free parking',
        pet_friendly: 'Pet friendly',
        long_stays: 'Great for long stays'
    };

    const houseRuleLabels = {
        no_smoking: 'No smoking',
        no_pets: 'No pets',
        no_parties: 'No parties or events',
        quiet_hours: 'Quiet hours after 10 PM',
        suitable_children: 'Suitable for children'
    };

    const listingState = {
        basic: null,
        stage2: {
            photos: [],
            highlights: [],
            amenities: [],
            headline: '',
            story: ''
        },
        stage3: null
    };

    const stageWrappers = document.querySelectorAll('.wizard-stage');
    const stageNumberEl = document.getElementById('stageNumber');
    const stageTitleEl = document.getElementById('stageTitle');
    const stageHeadingEl = document.getElementById('stageHeading');
    const stageLeadEl = document.getElementById('stageLead');
    const stripRows = document.querySelectorAll('.strip-row');

    const slides = document.querySelectorAll('.wizard-slide');
    const miniTop = document.getElementById('miniStepIndex');
    const miniBottom = document.getElementById('miniStepIndexBottom');
    const backBtn = document.getElementById('backBtn');
    const nextBtn = document.getElementById('nextBtn');
    const msgError = document.getElementById('msgError');
    const msgSuccess = document.getElementById('msgSuccess');

    const titleEl = document.getElementById('title');
    const descEl = document.getElementById('description');
    const guestsEl = document.getElementById('max_guests');
    const bedsEl = document.getElementById('bedrooms_count');
    const bathsEl = document.getElementById('bathrooms_count');
    const bedroomListEl = document.getElementById('bedroomDetails');
    const priceEl = document.getElementById('price_nightly');
    const priceOrigEl = document.getElementById('price_nightly_original');
    const weekendPriceEl = document.getElementById('weekend_price');
    const weekendOrigEl = document.getElementById('weekend_price_original');
    const pricePreviewEl = document.getElementById('pricePreview');
    const propertyTypeHidden = document.getElementById('property_type');
    const placeTypeHidden = document.getElementById('place_type');
    const bathroomAccessEl = document.getElementById('bathroom_access');
    const otherOccupantsDetailsEl = document.getElementById('other_occupants_details');

    const photoInput = document.getElementById('photoInput');
    const photoListEl = document.getElementById('photoList');
    const highlightChips = document.querySelectorAll('#highlightChips .highlight-chip');
    const amenityCheckboxes = document.querySelectorAll('input[name="amenities[]"]');
    const stage2HeadlineEl = document.getElementById('stage2Headline');
    const stage2DescriptionEl = document.getElementById('stage2Description');
    const stage2BackBtn = document.getElementById('stage2BackBtn');
    const stage2NextBtn = document.getElementById('stage2NextBtn');
    const stage2Error = document.getElementById('stage2Error');
    const stage2Success = document.getElementById('stage2Success');

    const stage3BackBtn = document.getElementById('stage3BackBtn');
    const stage3SaveDraftBtn = document.getElementById('stage3SaveDraftBtn');
    const stage3SubmitBtn = document.getElementById('stage3SubmitBtn');
    const stage3Error = document.getElementById('stage3Error');
    const stage3Success = document.getElementById('stage3Success');
    const stage3CustomRulesEl = document.getElementById('stage3CustomRules');
    const checkinWindowEl = document.getElementById('checkinWindow');
    const checkoutTimeEl = document.getElementById('checkoutTime');
    const welcomeMessageEl = document.getElementById('welcomeMessage');
    const cancellationPolicyEl = document.getElementById('cancellationPolicy');
    const houseRuleCheckboxes = document.querySelectorAll('input[name="house_rules[]"]');

    const previewTitleEl = document.getElementById('previewTitle');
    const previewGuestsEl = document.getElementById('previewGuests');
    const previewBedroomsEl = document.getElementById('previewBedrooms');
    const previewBathroomsEl = document.getElementById('previewBathrooms');
    const previewPriceEl = document.getElementById('previewPrice');
    const previewHighlightsEl = document.getElementById('previewHighlights');

    const propertyTypeOptions = document.querySelectorAll('#propertyTypeOptions .card-option');
    const placeTypeOptions = document.querySelectorAll('#placeTypeOptions .card-option');

    function ensureStage2State() {
        if (!listingState.stage2) {
            listingState.stage2 = {
                photos: [],
                highlights: [],
                amenities: [],
                headline: '',
                story: ''
            };
        }
        return listingState.stage2;
    }

    function hideStage2Messages() {
        if (stage2Error) stage2Error.style.display = 'none';
        if (stage2Success) stage2Success.style.display = 'none';
    }

    function hideStage3Messages() {
        if (stage3Error) stage3Error.style.display = 'none';
        if (stage3Success) stage3Success.style.display = 'none';
    }

    function scrollToWizardTop() {
        const shell = document.querySelector('.wizard-shell');
        const top = shell ? shell.offsetTop : 0;
        window.scrollTo({ top, behavior: 'smooth' });
    }

    function parseIntOrNull(value) {
        if (value === undefined || value === null) {
            return null;
        }
        const parsed = parseInt(String(value), 10);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function parseFloatOrNull(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        const parsed = parseFloat(String(value));
        return Number.isFinite(parsed) ? parsed : null;
    }

    function selectCard(options, hiddenInput, value) {
        options.forEach(btn => {
            btn.classList.toggle('selected', btn.getAttribute('data-value') === value);
        });
        hiddenInput.value = value;
    }

    propertyTypeOptions.forEach(btn => {
        btn.addEventListener('click', () => {
            selectCard(propertyTypeOptions, propertyTypeHidden, btn.getAttribute('data-value'));
        });
    });

    placeTypeOptions.forEach(btn => {
        btn.addEventListener('click', () => {
            selectCard(placeTypeOptions, placeTypeHidden, btn.getAttribute('data-value'));
        });
    });

    function renderBedroomDetails() {
        if (!bedroomListEl || !bedsEl) return;
        const count = parseInt(bedsEl.value || '0', 10);
        bedroomListEl.innerHTML = '';
        for (let i = 1; i <= count; i++) {
            const card = document.createElement('div');
            card.className = 'bedroom-card';
            card.innerHTML = `
                <div class="bedroom-card-title">Bedroom ${i}</div>
                <label class="field-label" style="margin-bottom:4px;">Bed type</label>
                <select class="select-simple" id="bedroom_bed_type_${i}">
                    <option value="">Select bed type</option>
                    <option value="double">Double bed</option>
                    <option value="twin">Twin bed</option>
                    <option value="single">Single bed</option>
                </select>
            `;
            bedroomListEl.appendChild(card);
        }
    }

    function showStep(step) {
        currentStep = step;
        slides.forEach(s => {
            const idx = parseInt(s.getAttribute('data-step'), 10);
            s.classList.toggle('active', idx === step);
        });

        if (miniTop) miniTop.textContent = String(step);
        if (miniBottom) miniBottom.textContent = String(step);

        if (backBtn) backBtn.disabled = step === 1;
        if (nextBtn) {
            nextBtn.textContent = step === TOTAL_STEPS ? 'Save & continue' : 'Next →';
            nextBtn.disabled = false;
        }
        if (msgError) msgError.style.display = 'none';
        if (msgSuccess) msgSuccess.style.display = 'none';
    }

    function showError(msg) {
        if (!msgError) return;
        msgError.textContent = msg;
        msgError.style.display = 'block';
        if (msgSuccess) msgSuccess.style.display = 'none';
    }

    function showSuccess(msg) {
        if (!msgSuccess) return;
        msgSuccess.textContent = msg;
        msgSuccess.style.display = 'block';
        if (msgError) msgError.style.display = 'none';
    }

    function validateCurrentStep() {
        if (currentStep === 1) {
            if (!titleEl.value.trim()) {
                showError('Please add a title so guests know what your place is.');
                return false;
            }
        }
        if (currentStep === 2) {
            if (!descEl.value.trim()) {
                showError('Share a short description so guests know what to expect.');
                return false;
            }
        }
        if (currentStep === 3) {
            if (!propertyTypeHidden.value) {
                showError('Please choose a property type.');
                return false;
            }
        }
        if (currentStep === 4) {
            if (!placeTypeHidden.value) {
                showError('Please choose the type of place guests will have.');
                return false;
            }
        }
        if (currentStep === 5) {
            const g = parseInt(guestsEl.value || '0', 10);
            if (!g || g < 1) {
                showError('Set at least 1 guest.');
                return false;
            }
        }
        if (currentStep === TOTAL_STEPS) {
            const nightly = parseFloat(priceEl.value || '0');
            if (!nightly || nightly <= 0) {
                showError('Set a nightly price greater than zero to continue.');
                return false;
            }
        }
        return true;
    }

    function updatePricePreview() {
        if (!pricePreviewEl) return;
        const priceVal = parseFloat(priceEl.value || '0');
        const origVal = parseFloat(priceOrigEl.value || '0');
        const weekendVal = parseFloat(weekendPriceEl.value || '0');
        const weekendOrigVal = parseFloat(weekendOrigEl.value || '0');

        let html = '';

        if (priceVal > 0) {
            const priceStr = priceVal.toLocaleString('id-ID');
            let weekdayLine = `
                <span class="price-current">
                    Rp ${priceStr} <span>/ night</span>
                </span>
            `;

            if (origVal > 0 && origVal > priceVal) {
                const origStr = origVal.toLocaleString('id-ID');
                const discPct = Math.round((origVal - priceVal) / origVal * 100);

                weekdayLine += `
                    <span class="price-original">Rp ${origStr}</span>
                    <span class="price-badge">
                        <img src="assets/icons/blue-fire.gif" alt="discount flame">
                        ${discPct}% OFF
                    </span>
                `;
            }

            html += `<div class="price-preview-line">${weekdayLine}</div>`;
        } else {
            html += `
                <div class="price-preview-line">
                    <span class="price-current">
                        Set a price to see how guests will see it <span>/ night</span>
                    </span>
                </div>
            `;
        }

        if (weekendVal > 0) {
            const wStr = weekendVal.toLocaleString('id-ID');
            let weekendLine = `
                <span class="price-current">
                    Weekend: Rp ${wStr} <span>/ night</span>
                </span>
            `;

            if (weekendOrigVal > 0 && weekendOrigVal > weekendVal) {
                const wOrigStr = weekendOrigVal.toLocaleString('id-ID');
                const wDiscPct = Math.round((weekendOrigVal - weekendVal) / weekendOrigVal * 100);

                weekendLine += `
                    <span class="price-original">Rp ${wOrigStr}</span>
                    <span class="price-badge">
                        <img src="assets/icons/blue-fire.gif" alt="discount flame">
                        ${wDiscPct}% OFF
                    </span>
                `;
            }

            html += `<br><div class="price-preview-line">${weekendLine}</div>`;
        }

        pricePreviewEl.innerHTML = html;
    }

    function renderPhotoList() {
        if (!photoListEl) return;
        const stage2 = ensureStage2State();
        photoListEl.innerHTML = '';

        if (!stage2.photos.length) {
            const empty = document.createElement('div');
            empty.style.fontSize = '12px';
            empty.style.color = 'var(--og-text-muted)';
            empty.textContent = 'No photos added yet.';
            photoListEl.appendChild(empty);
            return;
        }

        stage2.photos.forEach((photo, index) => {
            const item = document.createElement('div');
            item.className = 'photo-item';

            const img = document.createElement('img');
            img.src = photo.url;
            img.alt = photo.name || 'Listing photo';
            item.appendChild(img);

            if (index === 0) {
                const badge = document.createElement('div');
                badge.className = 'photo-cover-badge';
                badge.textContent = 'Cover';
                item.appendChild(badge);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', () => {
                const stage2State = ensureStage2State();
                stage2State.photos = stage2State.photos.filter(p => p.id !== photo.id);
                try { URL.revokeObjectURL(photo.url); } catch (e) { /* ignore */ }
                renderPhotoList();
                if (currentStage === 3) {
                    refreshStage3Preview();
                }
            });
            item.appendChild(removeBtn);

            photoListEl.appendChild(item);
        });
    }

    function refreshStage3Preview() {
        const base = listingState.basic || {};
        if (previewTitleEl) previewTitleEl.textContent = base.title || '—';

        if (previewGuestsEl) {
            if (base.maxGuests) {
                const plural = base.maxGuests > 1 ? 's' : '';
                previewGuestsEl.textContent = `${base.maxGuests} guest${plural}`;
            } else {
                previewGuestsEl.textContent = '—';
            }
        }

        if (previewBedroomsEl) {
            const hasBedrooms = base.bedrooms !== undefined && base.bedrooms !== null;
            previewBedroomsEl.textContent = hasBedrooms ? String(base.bedrooms) : '—';
        }
        if (previewBathroomsEl) {
            const hasBathrooms = base.bathrooms !== undefined && base.bathrooms !== null;
            previewBathroomsEl.textContent = hasBathrooms ? String(base.bathrooms) : '—';
        }
        if (previewPriceEl) {
            previewPriceEl.textContent = base.nightlyPrice && base.nightlyPrice > 0
                ? `Rp ${Number(base.nightlyPrice).toLocaleString('id-ID')}`
                : '—';
        }

        if (previewHighlightsEl) {
            previewHighlightsEl.innerHTML = '';
            const highlights = (listingState.stage2 && listingState.stage2.highlights) || [];
            if (highlights.length) {
                highlights.forEach(code => {
                    const chip = document.createElement('span');
                    chip.textContent = highlightLabels[code] || code;
                    previewHighlightsEl.appendChild(chip);
                });
            } else {
                const chip = document.createElement('span');
                chip.textContent = 'Add highlights to showcase your space';
                chip.style.background = '#f3f4f6';
                chip.style.color = '#6b7280';
                previewHighlightsEl.appendChild(chip);
            }
        }
    }

    function hydrateStage2() {
        const stage2 = ensureStage2State();
        if (stage2HeadlineEl) stage2HeadlineEl.value = stage2.headline || '';
        if (stage2DescriptionEl) stage2DescriptionEl.value = stage2.story || '';

        highlightChips.forEach(btn => {
            const code = btn.getAttribute('data-highlight');
            btn.classList.toggle('active', stage2.highlights.includes(code));
        });
        amenityCheckboxes.forEach(cb => {
            cb.checked = stage2.amenities.includes(cb.value);
        });
        renderPhotoList();
        hideStage2Messages();
    }

    function hydrateStage3() {
        hideStage3Messages();
        const state = listingState.stage3;
        if (!state) {
            houseRuleCheckboxes.forEach(cb => { cb.checked = false; });
            if (stage3CustomRulesEl) stage3CustomRulesEl.value = '';
            if (welcomeMessageEl) welcomeMessageEl.value = '';
            if (checkinWindowEl) checkinWindowEl.value = checkinWindowEl.options[0]?.value || '15:00-21:00';
            if (checkoutTimeEl) checkoutTimeEl.value = checkoutTimeEl.options[0]?.value || '11:00';
            if (cancellationPolicyEl) cancellationPolicyEl.value = cancellationPolicyEl.options[0]?.value || 'flexible';
            return;
        }

        const ruleSet = new Set(state.houseRules || []);
        houseRuleCheckboxes.forEach(cb => {
            cb.checked = ruleSet.has(cb.value);
        });
        if (stage3CustomRulesEl) stage3CustomRulesEl.value = state.customRules || '';
        if (welcomeMessageEl) welcomeMessageEl.value = state.welcomeMessage || '';
        if (checkinWindowEl && state.checkinWindow) checkinWindowEl.value = state.checkinWindow;
        if (checkoutTimeEl && state.checkoutTime) checkoutTimeEl.value = state.checkoutTime;
        if (cancellationPolicyEl && state.cancellationPolicy) {
            cancellationPolicyEl.value = state.cancellationPolicy;
        }
    }

    function gatherStage3Data() {
        const houseRules = Array.from(houseRuleCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        return {
            houseRules,
            customRules: stage3CustomRulesEl ? stage3CustomRulesEl.value.trim() : '',
            checkinWindow: checkinWindowEl ? checkinWindowEl.value : '',
            checkoutTime: checkoutTimeEl ? checkoutTimeEl.value : '',
            welcomeMessage: welcomeMessageEl ? welcomeMessageEl.value.trim() : '',
            cancellationPolicy: cancellationPolicyEl ? cancellationPolicyEl.value : 'flexible'
        };
    }

    function setStage3Loading(isLoading) {
        if (stage3SaveDraftBtn) stage3SaveDraftBtn.disabled = isLoading;
        if (stage3SubmitBtn) stage3SubmitBtn.disabled = isLoading;
    }

    function updateStageShell(stage) {
        currentStage = stage;
        stageWrappers.forEach(wrapper => {
            const wrapperStage = parseInt(wrapper.getAttribute('data-stage'), 10);
            wrapper.classList.toggle('active', wrapperStage === stage);
        });

        if (stageNumberEl) stageNumberEl.textContent = String(stage);
        const meta = STAGE_META[stage] || STAGE_META[1];
        if (stageTitleEl) stageTitleEl.textContent = meta.title;
        if (stageHeadingEl) stageHeadingEl.textContent = meta.heading;
        if (stageLeadEl) stageLeadEl.textContent = meta.lead;

        stripRows.forEach(row => {
            const rowStage = parseInt(row.getAttribute('data-stage'), 10);
            row.classList.toggle('active', rowStage === stage);
            row.classList.toggle('completed', rowStage < stage);
        });

        if (stage === 1) {
            showStep(currentStep);
        } else if (stage === 2) {
            hydrateStage2();
        } else if (stage === 3) {
            hydrateStage3();
            refreshStage3Preview();
        }

        scrollToWizardTop();
    }

    async function finalizeListing(targetStatus) {
        hideStage3Messages();
        if (!createdListingId || !listingState.basic) {
            if (stage3Error) {
                stage3Error.textContent = 'Please finish the basics first.';
                stage3Error.style.display = 'block';
            }
            return;
        }

        const base = listingState.basic;
        if (!base.nightlyPrice || base.nightlyPrice <= 0) {
            if (stage3Error) {
                stage3Error.textContent = 'Add a nightly price in Basics before finishing.';
                stage3Error.style.display = 'block';
            }
            return;
        }

        const stage2 = ensureStage2State();
        const stage3Data = gatherStage3Data();
        listingState.stage3 = stage3Data;

        const descriptionParts = [];
        if (base.description) descriptionParts.push(base.description);
        if (stage2.headline) descriptionParts.push(stage2.headline);
        if (stage2.story) descriptionParts.push(stage2.story);
        if (stage2.highlights.length) {
            descriptionParts.push('Highlights: ' + stage2.highlights.map(code => highlightLabels[code] || code).join(', '));
        }
        if (stage3Data.houseRules.length) {
            descriptionParts.push('House rules: ' + stage3Data.houseRules.map(code => houseRuleLabels[code] || code).join(', '));
        }
        if (stage3Data.customRules) descriptionParts.push(stage3Data.customRules);
        if (stage3Data.welcomeMessage) descriptionParts.push(stage3Data.welcomeMessage);

        const payload = {
            id: createdListingId,
            title: base.title,
            description: descriptionParts.join('\n\n') || base.title,
            guests: base.maxGuests || 1,
            bedrooms: base.bedrooms ?? 0,
            bathrooms: base.bathrooms ?? 0,
            nightly_price: base.nightlyPrice || 0,
            status: targetStatus,
            highlights: stage2.highlights,
            amenities: stage2.amenities,
            headline: stage2.headline,
            story: stage2.story,
            house_rules: stage3Data.houseRules,
            custom_rules: stage3Data.customRules,
            checkin_window: stage3Data.checkinWindow,
            checkout_time: stage3Data.checkoutTime,
            welcome_message: stage3Data.welcomeMessage,
            cancellation_policy: stage3Data.cancellationPolicy
        };

        if (!payload.title || !payload.description) {
            if (stage3Error) {
                stage3Error.textContent = 'Listing title and description are required.';
                stage3Error.style.display = 'block';
            }
            return;
        }

        if (!payload.nightly_price || payload.nightly_price <= 0) {
            if (stage3Error) {
                stage3Error.textContent = 'Nightly price must be greater than zero.';
                stage3Error.style.display = 'block';
            }
            return;
        }

        setStage3Loading(true);

        try {
            const response = await fetch('ogo-api/listings-update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(() => null);

            if (!response.ok || !data || data.status !== 'ok') {
                throw new Error((data && data.message) || 'Failed to save listing.');
            }

            refreshStage3Preview();
            if (stage3Success) {
                stage3Success.textContent = targetStatus === 'in_review'
                    ? 'Submitted for review! We’ll let you know when it’s approved.'
                    : 'Listing saved as draft.';
                stage3Success.style.display = 'block';
            }
        } catch (err) {
            if (stage3Error) {
                stage3Error.textContent = err.message || 'Failed to save listing.';
                stage3Error.style.display = 'block';
            }
        } finally {
            setStage3Loading(false);
        }
    }

    if (bedsEl) {
        bedsEl.addEventListener('change', renderBedroomDetails);
        renderBedroomDetails();
    }

    updatePricePreview();
    priceEl.addEventListener('input', updatePricePreview);
    priceOrigEl.addEventListener('input', updatePricePreview);
    weekendPriceEl.addEventListener('input', updatePricePreview);
    weekendOrigEl.addEventListener('input', updatePricePreview);

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            if (currentStage !== 1) return;
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', async function () {
            if (currentStage !== 1) return;
            if (!validateCurrentStep()) return;

            if (currentStep < TOTAL_STEPS) {
                showStep(currentStep + 1);
                return;
            }

            nextBtn.disabled = true;

            const bathroomTypeRadio = document.querySelector('input[name="bathroom_type"]:checked');
            const otherOccRadio = document.querySelector('input[name="other_occupants"]:checked');

            const maxGuests = parseIntOrNull(guestsEl.value) ?? 0;
            const bedroomCount = parseIntOrNull(bedsEl.value) ?? 0;
            const bedroomBeds = [];
            for (let i = 1; i <= bedroomCount; i++) {
                const sel = document.getElementById('bedroom_bed_type_' + i);
                if (sel) {
                    bedroomBeds.push({
                        bedroom_index: i,
                        bed_type: sel.value || null
                    });
                }
            }

            const bathroomCount = parseIntOrNull(bathsEl.value) ?? 0;
            const nightlyPrice = parseFloatOrNull(priceEl.value);
            const nightlyStrike = parseFloatOrNull(priceOrigEl.value);
            const weekendPrice = parseFloatOrNull(weekendPriceEl.value);
            const weekendStrike = parseFloatOrNull(weekendOrigEl.value);

            const hasDiscount =
                (nightlyPrice !== null && nightlyStrike !== null && nightlyStrike > nightlyPrice) ||
                (weekendPrice !== null && weekendStrike !== null && weekendStrike > weekendPrice);

            const payload = {
                host_id: HOST_ID,
                host_user_id: HOST_ID,
                host_type: HOST_TYPE,
                title: titleEl.value.trim(),
                description: descEl ? descEl.value.trim() : '',
                property_type: propertyTypeHidden.value,
                room_type: placeTypeHidden.value,
                max_guests: maxGuests,
                guests: maxGuests,
                bedrooms: bedroomCount,
                bathrooms: bathroomCount,
                bedroom_beds_json: bedroomBeds.length ? JSON.stringify(bedroomBeds) : null,
                bathroom_type: bathroomTypeRadio ? bathroomTypeRadio.value : null,
                bathroom_access: bathroomAccessEl.value || null,
                other_occupants: otherOccRadio ? otherOccRadio.value : null,
                other_occupants_details: otherOccupantsDetailsEl.value.trim() || null,
                nightly_price: nightlyPrice,
                nightly_price_strike: nightlyStrike,
                weekend_price: weekendPrice,
                weekend_price_strike: weekendStrike,
                has_discount: hasDiscount ? 1 : 0,
                status: 'draft'
            };

            Object.keys(payload).forEach(key => {
                if (payload[key] === null || payload[key] === undefined || Number.isNaN(payload[key])) {
                    delete payload[key];
                }
            });

            if (!payload.title) {
                showError('Please add a title before continuing.');
                nextBtn.disabled = false;
                return;
            }

            try {
                const nightlyNumeric = nightlyPrice !== null ? nightlyPrice : 0;

                if (createdListingId) {
                    const updatePayload = {
                        id: createdListingId,
                        title: payload.title,
                        description: payload.description || payload.title,
                        guests: maxGuests || 1,
                        bedrooms: bedroomCount,
                        bathrooms: bathroomCount,
                        nightly_price: nightlyNumeric,
                        status: 'draft'
                    };

                    const updateRes = await fetch('ogo-api/listings-update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(updatePayload)
                    });
                    const updateData = await updateRes.json().catch(() => null);

                    if (!updateRes.ok || !updateData || updateData.status !== 'ok') {
                        throw new Error((updateData && updateData.message) || 'Failed to update listing.');
                    }

                    listingState.basic = {
                        id: createdListingId,
                        hostType: HOST_TYPE,
                        title: payload.title,
                        description: payload.description || '',
                        propertyType: payload.property_type || null,
                        roomType: payload.room_type || null,
                        maxGuests,
                        bedrooms: bedroomCount,
                        bathrooms: bathroomCount,
                        bathroomType: bathroomTypeRadio ? bathroomTypeRadio.value : null,
                        bathroomAccess: bathroomAccessEl.value || null,
                        otherOccupants: otherOccRadio ? otherOccRadio.value : null,
                        otherDetails: otherOccupantsDetailsEl.value.trim() || null,
                        nightlyPrice: nightlyNumeric,
                        nightlyStrike: nightlyStrike || null,
                        weekendPrice: weekendPrice || null,
                        weekendStrike: weekendStrike || null
                    };

                    refreshStage3Preview();
                    showSuccess('Basics updated! Moving to details…');
                    setTimeout(() => {
                        nextBtn.disabled = false;
                        updateStageShell(2);
                    }, 500);
                    return;
                }

                const res = await fetch('ogo-api/index.php?action=listings-create-basic', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    showError('Failed to save listing. Please try again. (' + res.status + ')');
                    nextBtn.disabled = false;
                    return;
                }

                const data = await res.json().catch(() => null);
                if (!data || data.status !== 'ok') {
                    showError((data && data.message) || 'Failed to save listing.');
                    nextBtn.disabled = false;
                    return;
                }

                const newListingId =
                    (data.listing && (data.listing.id || data.listing.listing_id)) ||
                    data.listing_id ||
                    data.id ||
                    null;

                if (!newListingId) {
                    showError('Listing saved, but no ID was returned. Please try again.');
                    nextBtn.disabled = false;
                    return;
                }

                createdListingId = Number(newListingId);
                listingState.basic = {
                    id: createdListingId,
                    hostType: HOST_TYPE,
                    title: payload.title,
                    description: payload.description || '',
                    propertyType: payload.property_type || null,
                    roomType: payload.room_type || null,
                    maxGuests,
                    bedrooms: bedroomCount,
                    bathrooms: bathroomCount,
                    bathroomType: bathroomTypeRadio ? bathroomTypeRadio.value : null,
                    bathroomAccess: bathroomAccessEl.value || null,
                    otherOccupants: otherOccRadio ? otherOccRadio.value : null,
                    otherDetails: otherOccupantsDetailsEl.value.trim() || null,
                    nightlyPrice: nightlyNumeric,
                    nightlyStrike: nightlyStrike || null,
                    weekendPrice: weekendPrice || null,
                    weekendStrike: weekendStrike || null
                };

                refreshStage3Preview();
                showSuccess('Basics saved! Moving to details…');
                setTimeout(() => {
                    nextBtn.disabled = false;
                    updateStageShell(2);
                }, 700);
            } catch (err) {
                console.error(err);
                showError('Network or server error. Please try again.');
                nextBtn.disabled = false;
            }
        });
    }

    if (photoInput) {
        photoInput.addEventListener('change', event => {
            const files = Array.from(event.target.files || []);
            if (!files.length) return;
            const stage2 = ensureStage2State();

            files.forEach(file => {
                if (!file.type || !file.type.startsWith('image/')) {
                    return;
                }
                const url = URL.createObjectURL(file);
                stage2.photos.push({
                    id: 'photo-' + (++photoIdCounter),
                    name: file.name,
                    url
                });
            });

            photoInput.value = '';
            hideStage2Messages();
            renderPhotoList();
            if (currentStage === 3) {
                refreshStage3Preview();
            }
        });
    }

    highlightChips.forEach(btn => {
        btn.addEventListener('click', () => {
            hideStage2Messages();
            const code = btn.getAttribute('data-highlight');
            const stage2 = ensureStage2State();
            const isActive = btn.classList.contains('active');

            if (isActive) {
                btn.classList.remove('active');
                stage2.highlights = stage2.highlights.filter(item => item !== code);
            } else {
                if (stage2.highlights.length >= 5) {
                    if (stage2Error) {
                        stage2Error.textContent = 'Select up to five highlights.';
                        stage2Error.style.display = 'block';
                        setTimeout(() => {
                            if (stage2Error) stage2Error.style.display = 'none';
                        }, 2000);
                    }
                    return;
                }
                btn.classList.add('active');
                stage2.highlights.push(code);
            }

            if (currentStage === 3) {
                refreshStage3Preview();
            }
        });
    });

    amenityCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const stage2 = ensureStage2State();
            if (cb.checked) {
                if (!stage2.amenities.includes(cb.value)) {
                    stage2.amenities.push(cb.value);
                }
            } else {
                stage2.amenities = stage2.amenities.filter(item => item !== cb.value);
            }
        });
    });

    if (stage2HeadlineEl) {
        stage2HeadlineEl.addEventListener('input', () => {
            const stage2 = ensureStage2State();
            stage2.headline = stage2HeadlineEl.value.trim();
        });
    }

    if (stage2DescriptionEl) {
        stage2DescriptionEl.addEventListener('input', () => {
            const stage2 = ensureStage2State();
            stage2.story = stage2DescriptionEl.value.trim();
        });
    }

    if (stage2BackBtn) {
        stage2BackBtn.addEventListener('click', () => {
            currentStep = TOTAL_STEPS;
            updateStageShell(1);
            showStep(currentStep);
        });
    }

    if (stage2NextBtn) {
        stage2NextBtn.addEventListener('click', () => {
            hideStage2Messages();
            if (!createdListingId) {
                if (stage2Error) {
                    stage2Error.textContent = 'Complete the basics first.';
                    stage2Error.style.display = 'block';
                }
                return;
            }

            const stage2 = ensureStage2State();
            stage2.headline = stage2HeadlineEl ? stage2HeadlineEl.value.trim() : stage2.headline;
            stage2.story = stage2DescriptionEl ? stage2DescriptionEl.value.trim() : stage2.story;

            if (!stage2.photos.length) {
                if (stage2Error) {
                    stage2Error.textContent = 'Add at least one photo before continuing.';
                    stage2Error.style.display = 'block';
                }
                return;
            }

            if (stage2Success) {
                stage2Success.textContent = 'Details saved! Time for the finishing touches.';
                stage2Success.style.display = 'block';
            }
            refreshStage3Preview();
            setTimeout(() => {
                if (stage2Success) stage2Success.style.display = 'none';
                updateStageShell(3);
            }, 400);
        });
    }

    if (stage3BackBtn) {
        stage3BackBtn.addEventListener('click', () => {
            updateStageShell(2);
        });
    }

    if (stage3SaveDraftBtn) {
        stage3SaveDraftBtn.addEventListener('click', () => finalizeListing('draft'));
    }

    if (stage3SubmitBtn) {
        stage3SubmitBtn.addEventListener('click', () => finalizeListing('in_review'));
    }

    updateStageShell(1);
    showStep(1);
})();
</script>

</body>
</html>
