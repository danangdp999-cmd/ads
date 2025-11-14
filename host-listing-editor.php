<?php
// host-listing-editor.php — Edit basic listing OGORooms (simple_listings)

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$hostId    = (int)$_SESSION['user_id'];
$hostEmail = $_SESSION['user_email'] ?? '';

require_once __DIR__ . '/ogo-api/config.php';

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

function normalize_local_path(string $value): string
{
    $normalized = str_replace('\\', '/', $value);
    $normalized = preg_replace('#/{2,}#', '/', $normalized);

    while (strpos($normalized, '../') === 0) {
        $normalized = substr($normalized, 3);
    }

    return ltrim($normalized, './');
}

function public_path(string $value): string
{
    $trimmed = ltrim($value, '/');
    $base    = app_base_uri();
    $prefix  = $base !== '' ? $base . '/' : '/';

    return $prefix . $trimmed;
}

function resolve_cover_display(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $trimmed)) {
        return $trimmed;
    }

    $normalized = normalize_local_path($trimmed);
    if ($normalized === '') {
        return '';
    }

    $full = __DIR__ . '/' . ltrim($normalized, '/');
    if (is_file($full)) {
        return public_path($normalized);
    }

    if (strpos($normalized, 'listing-photos/') === 0) {
        return public_path($normalized);
    }

    return '';
}

$appBasePath        = app_base_uri();
$previewUrl         = '';
$coverPhotoResolved = '';

// Option lists shared with the host wizard
$highlightOptions = [
    'great_location'  => 'Great location',
    'city_view'       => 'City skyline view',
    'fast_wifi'       => 'Fast Wi-Fi',
    'self_check_in'   => 'Self check-in',
    'workspace'       => 'Dedicated workspace',
    'parking'         => 'Free parking',
    'pet_friendly'    => 'Pet friendly',
    'long_stays'      => 'Great for long stays',
];

$amenityOptions = [
    'wifi'             => 'Wi-Fi',
    'air_conditioning' => 'Air conditioning',
    'kitchen'          => 'Full kitchen',
    'washing_machine'  => 'Washer',
    'dryer'            => 'Dryer',
    'tv'               => 'TV',
    'workspace'        => 'Dedicated workspace',
    'balcony'          => 'Balcony',
];

$amenityCodeMap = [
    'wifi'             => 'wifi',
    'air_conditioning' => 'air_conditioning',
    'kitchen'          => 'kitchen',
    'washing_machine'  => 'washer',
    'dryer'            => 'dryer',
    'tv'               => 'tv',
    'workspace'        => 'dedicated_workspace',
    'balcony'          => 'balcony',
];

$houseRuleOptions = [
    'no_smoking'        => 'No smoking',
    'no_pets'           => 'No pets',
    'no_parties'        => 'No parties or events',
    'quiet_hours'       => 'Quiet hours after 10 PM',
    'suitable_children' => 'Suitable for children',
];

$checkinWindowOptions = [
    '15:00-21:00' => '3:00 PM – 9:00 PM',
    '14:00-20:00' => '2:00 PM – 8:00 PM',
    'flexible'    => 'Flexible – message me',
];

$checkoutTimeOptions = [
    '11:00'    => '11:00 AM',
    '12:00'    => '12:00 PM',
    'flexible' => 'Flexible – message me',
];

$cancellationOptions = [
    'flexible' => 'Flexible · Full refund 1 day prior',
    'moderate' => 'Moderate · Full refund 5 days prior',
    'strict'   => 'Strict · 50% refund up to 7 days prior',
];

$title             = '';
$description       = '';
$city              = '';
$country           = '';
$nightly           = null;
$nightlyStrike     = null;
$weekend           = null;
$weekendStrike     = null;
$hasDiscount       = false;
$discountLabel     = '';
$headline          = '';
$story             = '';
$selectedHighlights = [];
$selectedAmenities  = [];
$selectedHouseRules = [];
$customRules       = '';
$checkinWindow     = '';
$checkoutTime      = '';
$welcomeMessage    = '';
$cancellationPolicy = '';
$coverPhotoUrl     = '';
$removeCoverRequested = false;
$status            = 'draft';
$statusChoice      = 'draft';

$refreshFormFromListing = $_SERVER['REQUEST_METHOD'] !== 'POST';

// Ambil ID listing dari query
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($listingId <= 0) {
    http_response_code(400);
    echo 'Listing ID is required.';
    exit;
}

$previewUrl = ($appBasePath !== '' ? $appBasePath . '/' : '/') . 'listing-room.php?id=' . (int)$listingId . '&preview=1';

$error   = '';
$success = '';

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

    // Ambil data listing milik host ini
    $stmt = $pdo->prepare(
        'SELECT *
         FROM simple_listings
         WHERE id = ? AND host_user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$listingId, $hostId]);
    $listing = $stmt->fetch();

    if (!$listing) {
        http_response_code(404);
        echo 'Listing not found or not owned by this host.';
        exit;
    }

    // Proses form update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title        = trim($_POST['title'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $city         = trim($_POST['city'] ?? '');
        $country      = trim($_POST['country'] ?? '');
        $headline     = trim($_POST['headline'] ?? '');
        if ($headline !== '') {
            $headline = limit_string($headline, 190);
        }
        $story = trim($_POST['story'] ?? '');

        foreach (decode_json_array($listing['highlights_json'] ?? null) as $code) {
            if (!isset($highlightOptions[$code])) {
                $highlightOptions[$code] = ucwords(str_replace('_', ' ', $code));
            }
        }
        foreach (decode_json_array($listing['amenities_json'] ?? null) as $code) {
            if (!isset($amenityOptions[$code])) {
                $amenityOptions[$code] = ucwords(str_replace('_', ' ', str_replace('-', ' ', $code)));
            }
        }
        foreach (decode_json_array($listing['house_rules_json'] ?? null) as $code) {
            if (!isset($houseRuleOptions[$code])) {
                $houseRuleOptions[$code] = ucwords(str_replace('_', ' ', $code));
            }
        }

        $selectedHighlights = sanitize_selection($_POST['highlights'] ?? [], array_keys($highlightOptions), 5);
        $selectedAmenities  = sanitize_selection($_POST['amenities'] ?? [], array_keys($amenityOptions));
        $selectedHouseRules = sanitize_selection($_POST['house_rules'] ?? [], array_keys($houseRuleOptions), 10);

        $customRules = trim($_POST['custom_rules'] ?? '');
        $checkinWindow = trim($_POST['checkin_window'] ?? '');
        if ($checkinWindow !== '') {
            $checkinWindow = limit_string($checkinWindow, 60);
        }
        $checkoutTime = trim($_POST['checkout_time'] ?? '');
        if ($checkoutTime !== '') {
            $checkoutTime = limit_string($checkoutTime, 60);
        }
        $welcomeMessage = trim($_POST['welcome_message'] ?? '');
        $cancellationPolicy = trim($_POST['cancellation_policy'] ?? '');
        if ($cancellationPolicy !== '' && !isset($cancellationOptions[$cancellationPolicy])) {
            $cancellationPolicy = limit_string($cancellationPolicy, 60);
        }

        $nightly       = $_POST['nightly_price'] !== '' ? (float)$_POST['nightly_price'] : null;
        $nightlyStrike = $_POST['nightly_price_strike'] !== '' ? (float)$_POST['nightly_price_strike'] : null;
        $weekend       = $_POST['weekend_price'] !== '' ? (float)$_POST['weekend_price'] : null;
        $weekendStrike = $_POST['weekend_price_strike'] !== '' ? (float)$_POST['weekend_price_strike'] : null;

        $hasDiscount   = isset($_POST['has_discount']);
        $discountLabel = trim($_POST['discount_label'] ?? '');

        // status_choice: draft | submit
        $statusChoice = $_POST['status_choice'] ?? 'draft';
        $status = 'draft';
        if ($statusChoice === 'submit') {
            $status = 'in_review';
        }

        $dbCoverPhoto   = $listing['cover_photo_url'] ?? '';
        $persistedCover = sanitize_existing_cover_path($_POST['cover_photo_existing'] ?? null, $listingId);
        $hasTempCover   = $persistedCover !== '' && $persistedCover !== $dbCoverPhoto;
        $newCoverPhotoUrl = $persistedCover !== ''
            ? $persistedCover
            : ($dbCoverPhoto !== '' ? $dbCoverPhoto : null);
        $coverToDelete = null;

        if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
            if ($hasTempCover) {
                remove_listing_photo($persistedCover);
                $persistedCover = '';
                $hasTempCover   = false;
            } elseif ($dbCoverPhoto !== '') {
                $coverToDelete = $dbCoverPhoto;
            }
            $newCoverPhotoUrl = null;
            $removeCoverRequested = true;
        }

        if (isset($_FILES['cover_photo']) && is_array($_FILES['cover_photo'])) {
            $errorCode = $_FILES['cover_photo']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                if ($hasTempCover) {
                    remove_listing_photo($persistedCover);
                    $persistedCover = '';
                    $hasTempCover   = false;
                }

                if ($errorCode !== UPLOAD_ERR_OK) {
                    $validationErrors[] = 'Failed to upload cover photo (error code ' . (int)$errorCode . ').';
                } else {
                    try {
                        $uploadedPath = process_cover_upload($listingId, $_FILES['cover_photo']);
                        if ($dbCoverPhoto !== '' && $dbCoverPhoto !== $uploadedPath) {
                            $coverToDelete = $dbCoverPhoto;
                        }
                        $persistedCover   = $uploadedPath;
                        $newCoverPhotoUrl = $uploadedPath;
                        $hasTempCover     = $uploadedPath !== '' && $uploadedPath !== $dbCoverPhoto;
                        $removeCoverRequested = false;
                    } catch (Exception $e) {
                        $validationErrors[] = $e->getMessage();
                    }
                }
            }
        }

        if ($dbCoverPhoto !== '' && $newCoverPhotoUrl !== null && $newCoverPhotoUrl !== $dbCoverPhoto && $coverToDelete === null) {
            $coverToDelete = $dbCoverPhoto;
        }

        $validationErrors = [];
        if ($title === '') {
            $validationErrors[] = 'Title is required.';
        }
        if ($description === '') {
            $validationErrors[] = 'Description is required.';
        }
        if ($nightly === null || $nightly <= 0) {
            $validationErrors[] = 'Nightly price must be greater than zero.';
        }

        if (!empty($validationErrors)) {
            $error = implode(' ', $validationErrors);
            $coverPhotoUrl = $newCoverPhotoUrl ?? '';
            $coverPhotoResolved = resolve_cover_display($coverPhotoUrl);
        } else {
            $headlineValue = $headline === '' ? null : $headline;
            $storyValue = $story === '' ? null : $story;
            $customRulesValue = $customRules === '' ? null : $customRules;
            $checkinWindowValue = $checkinWindow === '' ? null : $checkinWindow;
            $checkoutTimeValue = $checkoutTime === '' ? null : $checkoutTime;
            $welcomeMessageValue = $welcomeMessage === '' ? null : $welcomeMessage;
            $cancellationPolicyValue = $cancellationPolicy === '' ? null : $cancellationPolicy;

            $highlightsJson = !empty($selectedHighlights) ? json_encode($selectedHighlights) : null;
            $amenitiesJson  = !empty($selectedAmenities) ? json_encode($selectedAmenities) : null;
            $houseRulesJson = !empty($selectedHouseRules) ? json_encode($selectedHouseRules) : null;

            $amenityCodes = [];
            foreach ($selectedAmenities as $code) {
                if (isset($amenityCodeMap[$code])) {
                    $amenityCodes[] = $amenityCodeMap[$code];
                }
            }
            $amenityCodes = array_values(array_unique($amenityCodes));

            try {
                $pdo->beginTransaction();

                $upd = $pdo->prepare(
                    'UPDATE simple_listings
                     SET title = :title,
                         description = :description,
                         city = :city,
                         country = :country,
                         nightly_price = :nightly,
                         nightly_price_strike = :nightly_strike,
                         weekend_price = :weekend,
                         weekend_price_strike = :weekend_strike,
                         has_discount = :has_discount,
                         discount_label = :discount_label,
                         status = :status,
                         headline = :headline,
                         story = :story,
                         highlights_json = :highlights_json,
                         amenities_json = :amenities_json,
                         house_rules_json = :house_rules_json,
                         custom_rules = :custom_rules,
                         checkin_window = :checkin_window,
                         checkout_time = :checkout_time,
                         welcome_message = :welcome_message,
                         cover_photo_url = :cover_photo_url,
                         cancellation_policy = :cancellation_policy,
                         updated_at = NOW()
                     WHERE id = :id AND host_user_id = :host_id'
                );

                $upd->bindValue(':title', $title, PDO::PARAM_STR);
                $upd->bindValue(':description', $description, PDO::PARAM_STR);
                $upd->bindValue(':city', $city, PDO::PARAM_STR);
                $upd->bindValue(':country', $country, PDO::PARAM_STR);
                if ($nightly === null) {
                    $upd->bindValue(':nightly', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':nightly', $nightly, PDO::PARAM_STR);
                }
                if ($nightlyStrike === null) {
                    $upd->bindValue(':nightly_strike', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':nightly_strike', $nightlyStrike, PDO::PARAM_STR);
                }
                if ($weekend === null) {
                    $upd->bindValue(':weekend', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':weekend', $weekend, PDO::PARAM_STR);
                }
                if ($weekendStrike === null) {
                    $upd->bindValue(':weekend_strike', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':weekend_strike', $weekendStrike, PDO::PARAM_STR);
                }
                $upd->bindValue(':has_discount', $hasDiscount ? 1 : 0, PDO::PARAM_INT);
                if ($discountLabel === '') {
                    $upd->bindValue(':discount_label', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':discount_label', $discountLabel, PDO::PARAM_STR);
                }
                $upd->bindValue(':status', $status, PDO::PARAM_STR);
                $upd->bindValue(':headline', $headlineValue, $headlineValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':story', $storyValue, $storyValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':highlights_json', $highlightsJson, $highlightsJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':amenities_json', $amenitiesJson, $amenitiesJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':house_rules_json', $houseRulesJson, $houseRulesJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':custom_rules', $customRulesValue, $customRulesValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':checkin_window', $checkinWindowValue, $checkinWindowValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':checkout_time', $checkoutTimeValue, $checkoutTimeValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':welcome_message', $welcomeMessageValue, $welcomeMessageValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                if ($newCoverPhotoUrl === null) {
                    $upd->bindValue(':cover_photo_url', null, PDO::PARAM_NULL);
                } else {
                    $upd->bindValue(':cover_photo_url', $newCoverPhotoUrl, PDO::PARAM_STR);
                }
                $upd->bindValue(':cancellation_policy', $cancellationPolicyValue, $cancellationPolicyValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':id', $listingId, PDO::PARAM_INT);
                $upd->bindValue(':host_id', $hostId, PDO::PARAM_INT);

                $upd->execute();

                $amenityIds = [];
                if (!empty($amenityCodes)) {
                    $codeToId = fetch_amenity_ids($pdo, $amenityCodes);
                    foreach ($amenityCodes as $code) {
                        if (isset($codeToId[$code])) {
                            $amenityIds[] = $codeToId[$code];
                        }
                    }
                }

                sync_listing_highlights($pdo, $listingId, $selectedHighlights);
                sync_listing_amenities($pdo, $listingId, $amenityIds);

                $completedSections = ['stage1_basics'];
                if (!empty($selectedHighlights) || !empty($amenityCodes) || $headlineValue !== null || $storyValue !== null) {
                    $completedSections[] = 'stage2_details';
                }
                if (!empty($selectedHouseRules) || $customRulesValue !== null || $checkinWindowValue !== null || $checkoutTimeValue !== null || $welcomeMessageValue !== null || $cancellationPolicyValue !== null) {
                    $completedSections[] = 'stage3_finish';
                }
                $currentStep = in_array('stage3_finish', $completedSections, true)
                    ? '3'
                    : (in_array('stage2_details', $completedSections, true) ? '2' : '1');

                sync_wizard_progress($pdo, $listingId, $currentStep, $completedSections);

                $pdo->commit();

                $coverPhotoUrl = $newCoverPhotoUrl ?? '';
                $coverPhotoResolved = resolve_cover_display($coverPhotoUrl);
                if ($coverToDelete !== null && ($newCoverPhotoUrl === null || $coverToDelete !== $newCoverPhotoUrl)) {
                    remove_listing_photo($coverToDelete);
                }
                $removeCoverRequested = false;

                $success = 'Listing updated.';
                $refreshFormFromListing = true;
                $stmt->execute([$listingId, $hostId]);
                $listing = $stmt->fetch();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to update listing: ' . $e->getMessage();
            }
        }
    }

} catch (Exception $e) {
    $error = 'Server error: ' . $e->getMessage();
}

// Normalisasi nilai untuk form
if ($refreshFormFromListing && $listing) {
    $title       = $listing['title'] ?? '';
    $description = $listing['description'] ?? '';
    $city        = $listing['city'] ?? '';
    $country     = $listing['country'] ?? '';
    $nightly       = $listing['nightly_price'];
    $nightlyStrike = $listing['nightly_price_strike'];
    $weekend       = $listing['weekend_price'];
    $weekendStrike = $listing['weekend_price_strike'];
    $hasDiscount   = !empty($listing['has_discount']);
    $discountLabel = $listing['discount_label'] ?? '';
    $headline      = $listing['headline'] ?? '';
    $story         = $listing['story'] ?? '';
    $selectedHighlights = decode_json_array($listing['highlights_json'] ?? null);
    $selectedAmenities  = decode_json_array($listing['amenities_json'] ?? null);
    $selectedHouseRules = decode_json_array($listing['house_rules_json'] ?? null);
    $customRules   = $listing['custom_rules'] ?? '';
    $checkinWindow = $listing['checkin_window'] ?? '';
    $checkoutTime  = $listing['checkout_time'] ?? '';
    $welcomeMessage = $listing['welcome_message'] ?? '';
    $cancellationPolicy = $listing['cancellation_policy'] ?? '';
    $status        = $listing['status'] ?? 'draft';
    $coverPhotoUrl = $listing['cover_photo_url'] ?? '';
    $removeCoverRequested = false;

    foreach ($selectedHighlights as $code) {
        if (!isset($highlightOptions[$code])) {
            $highlightOptions[$code] = ucwords(str_replace('_', ' ', $code));
        }
    }
    foreach ($selectedAmenities as $code) {
        if (!isset($amenityOptions[$code])) {
            $amenityOptions[$code] = ucwords(str_replace('_', ' ', str_replace('-', ' ', $code)));
        }
    }
    foreach ($selectedHouseRules as $code) {
        if (!isset($houseRuleOptions[$code])) {
            $houseRuleOptions[$code] = ucwords(str_replace('_', ' ', $code));
        }
    }
}

$coverPhotoResolved = resolve_cover_display($coverPhotoUrl);

// Untuk radio
$statusChoice = ($status === 'in_review' || $status === 'published') ? 'submit' : 'draft';

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function limit_string(?string $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function decode_json_array(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    $values = [];
    foreach ($decoded as $item) {
        if (is_string($item) || is_numeric($item)) {
            $trimmed = trim((string)$item);
            if ($trimmed !== '' && !in_array($trimmed, $values, true)) {
                $values[] = $trimmed;
            }
        }
    }

    return $values;
}

function sanitize_selection($input, array $allowedKeys, ?int $limit = null): array
{
    $allowedLookup = array_fill_keys($allowedKeys, true);
    $result = [];

    if (!is_array($input)) {
        if ($input === null) {
            return [];
        }
        $input = [$input];
    }

    foreach ($input as $value) {
        if (!is_string($value) && !is_numeric($value)) {
            continue;
        }
        $trimmed = trim((string)$value);
        if ($trimmed === '' || !isset($allowedLookup[$trimmed])) {
            continue;
        }
        if (!in_array($trimmed, $result, true)) {
            $result[] = $trimmed;
            if ($limit !== null && count($result) >= $limit) {
                break;
            }
        }
    }

    return $result;
}

function fetch_amenity_ids(PDO $pdo, array $codes): array
{
    if (empty($codes)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare('SELECT id, code FROM amenities WHERE code IN (' . $placeholders . ')');
    $stmt->execute($codes);

    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['code']] = (int)$row['id'];
    }

    return $map;
}

function sync_listing_highlights(PDO $pdo, int $listingId, array $codes): void
{
    $pdo->prepare('DELETE FROM listing_highlights WHERE listing_id = ?')->execute([$listingId]);

    if (empty($codes)) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO listing_highlights (listing_id, code, created_at) VALUES (:listing_id, :code, NOW())');
    foreach ($codes as $code) {
        $stmt->execute([
            ':listing_id' => $listingId,
            ':code'       => $code,
        ]);
    }
}

function sync_listing_amenities(PDO $pdo, int $listingId, array $amenityIds): void
{
    $pdo->prepare('DELETE FROM listing_amenities WHERE listing_id = ?')->execute([$listingId]);

    if (empty($amenityIds)) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO listing_amenities (listing_id, amenity_id, present_state) VALUES (:listing_id, :amenity_id, :state)');
    foreach ($amenityIds as $amenityId) {
        $stmt->execute([
            ':listing_id' => $listingId,
            ':amenity_id' => $amenityId,
            ':state'      => 'yes',
        ]);
    }
}

function sync_wizard_progress(PDO $pdo, int $listingId, string $step, array $sections): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO listing_wizard_progress (listing_id, current_step, completed_sections, last_saved_at)
         VALUES (:listing_id, :current_step, :completed_sections, NOW())
         ON DUPLICATE KEY UPDATE
            current_step = VALUES(current_step),
            completed_sections = VALUES(completed_sections),
            last_saved_at = NOW()'
    );

    $completedJson = !empty($sections) ? json_encode(array_values($sections)) : null;

    $stmt->execute([
        ':listing_id'         => $listingId,
        ':current_step'       => $step,
        ':completed_sections' => $completedJson,
    ]);
}


function listing_photos_base_path(): string
{
    return __DIR__ . '/listing-photos';
}

function ensure_listing_photo_dir(int $listingId): string
{
    $base = listing_photos_base_path();
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }

    $dir = $base . '/' . $listingId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function process_cover_upload(int $listingId, array $file): string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Cover photo upload failed. Please try again.');
    }

    $size = isset($file['size']) ? (int)$file['size'] : 0;
    if ($size <= 0) {
        throw new RuntimeException('Uploaded cover photo is empty.');
    }
    if ($size > 10 * 1024 * 1024) {
        throw new RuntimeException('Cover photo must be 10 MB or smaller.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($info['mime'])) {
        throw new RuntimeException('Cover photo must be an image (JPG, PNG, or WebP).');
    }

    $mime = strtolower((string)$info['mime']);
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensionMap[$mime])) {
        throw new RuntimeException('Cover photo must be JPG, PNG, or WebP format.');
    }

    $ext = $extensionMap[$mime];
    $dir = ensure_listing_photo_dir($listingId);

    try {
        $uniqueBytes = random_bytes(4);
    } catch (Exception $e) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $uniqueBytes = openssl_random_pseudo_bytes(4) ?: random_bytes(4);
        } else {
            $uniqueBytes = substr(hash('sha256', (string)microtime(true) . mt_rand()), 0, 8);
            $unique      = $uniqueBytes;
        }
    }

    if (!isset($unique)) {
        $unique = bin2hex($uniqueBytes);
    }

    $filename = 'cover-' . date('Ymd-His') . '-' . $unique . '.' . $ext;
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to save cover photo on the server.');
    }

    @chmod($destination, 0644);

    return 'listing-photos/' . $listingId . '/' . $filename;
}

function remove_listing_photo(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $cleanPath = ltrim($relativePath, '/');
    $fullPath  = __DIR__ . '/' . $cleanPath;

    $baseReal = realpath(listing_photos_base_path());
    $fullReal = realpath($fullPath);

    if ($baseReal === false || $fullReal === false) {
        return;
    }

    if (strpos($fullReal, $baseReal) !== 0) {
        return;
    }

    if (is_file($fullReal)) {
        @unlink($fullReal);

        $parent = dirname($fullReal);
        if ($parent !== $baseReal && is_dir($parent)) {
            $files = array_diff(scandir($parent) ?: [], ['.', '..']);
            if (empty($files)) {
                @rmdir($parent);
            }
        }
    }
}

function sanitize_existing_cover_path($value, int $listingId): string
{
    if ($value === null) {
        return '';
    }

    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return '';
    }

    $trimmed = ltrim($trimmed, '/');
    $expectedPrefix = 'listing-photos/' . $listingId . '/';
    if (strpos($trimmed, $expectedPrefix) !== 0) {
        return '';
    }

    $fullPath = __DIR__ . '/' . $trimmed;
    if (!is_file($fullPath)) {
        return '';
    }

    return $trimmed;
}

?>
<!DOCTYPE html>
<html lang="en" data-base-path="<?php echo h($appBasePath); ?>">
<head>
    <meta charset="UTF-8">
    <title>OGORooms – Edit listing #<?php echo (int)$listingId; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-xl: 999px;
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

        .page {
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }

        /* NAV */
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

        /* MAIN LAYOUT */
        .main {
            flex:1;
            padding:22px 16px 32px;
        }
        .main-inner {
            max-width:1240px;
            margin:0 auto;
            display:grid;
            grid-template-columns:260px minmax(0, 1fr);
            gap:18px;
        }
        @media(max-width:900px){
            .main-inner {
                grid-template-columns:1fr;
            }
        }

        /* SIDEBAR */
        .sidebar-card {
            background:var(--bg-card);
            border-radius:24px;
            border:1px solid rgba(229,231,235,0.95);
            box-shadow:0 10px 30px rgba(148,163,184,0.2);
            padding:14px 14px 12px;
        }
        .sidebar-title {
            font-size:15px;
            font-weight:600;
            margin-bottom:6px;
        }
        .sidebar-sub {
            font-size:12px;
            color:var(--text-muted);
            margin-bottom:10px;
        }
        .side-section-label {
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:#9ca3af;
            margin:10px 0 6px;
        }
        .side-nav-link {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:6px;
            padding:6px 8px;
            border-radius:999px;
            font-size:13px;
            cursor:pointer;
            border:1px solid transparent;
            background:transparent;
            width:100%;
            text-align:left;
        }
        .side-nav-link span.badge {
            font-size:11px;
            padding:2px 7px;
            border-radius:999px;
            background:#f3f4f6;
            color:#4b5563;
        }
        .side-nav-link.active {
            background:#111827;
            color:#f9fafb;
        }
        .side-nav-link:focus-visible {
            outline:2px solid var(--accent);
            outline-offset:2px;
        }

        /* FORM CARD */
        .form-card {
            background:var(--bg-card);
            border-radius:24px;
            border:1px solid rgba(229,231,235,0.95);
            box-shadow:0 18px 40px rgba(148,163,184,0.25);
            padding:18px 20px 16px;
        }
        .editor-section {
            scroll-margin-top:140px;
        }
        .form-header {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
            flex-wrap:wrap;
            margin-bottom:10px;
        }
        .form-title-block {
            display:flex;
            flex-direction:column;
            gap:2px;
        }
        .form-title {
            font-size:18px;
            font-weight:700;
        }
        .form-sub {
            font-size:12px;
            color:var(--text-muted);
        }
        .btn-secondary {
            border-radius:999px;
            border:1px solid #e5e7eb;
            padding:7px 12px;
            font-size:12px;
            background:#fff;
            cursor:pointer;
        }
        .btn-primary {
            border-radius:999px;
            border:none;
            padding:8px 16px;
            font-size:13px;
            font-weight:600;
            cursor:pointer;
            background:var(--accent);
            color:#fff;
            box-shadow:0 12px 30px rgba(178,116,59,0.45);
        }

        .divider {
            margin:8px 0 12px;
            border-top:1px solid #e5e7eb;
        }

        .field-row {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px;
        }
        @media(max-width:720px){
            .field-row { grid-template-columns:1fr; }
        }
        .field-group {
            margin-bottom:10px;
        }
        .field-group-full {
            grid-column:1 / -1;
        }
        .field-label {
            font-size:12px;
            color:#6b7280;
            margin-bottom:4px;
        }
        .input {
            width:100%;
            border-radius:999px;
            border:1px solid rgba(209,213,219,1);
            padding:9px 14px;
            font-size:14px;
            outline:none;
            background:#f9fafb;
        }
        .input:focus {
            border-color:var(--accent);
            background:#ffffff;
            box-shadow:0 0 0 1px rgba(178,116,59,0.3);
        }
        .input-number {
            width:100%;
        }
        .textarea {
            width:100%;
            border-radius:18px;
            border:1px solid rgba(209,213,219,1);
            padding:12px 14px;
            font-size:14px;
            background:#f9fafb;
            resize:vertical;
            min-height:120px;
            transition:border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .textarea:focus {
            border-color:var(--accent);
            background:#ffffff;
            box-shadow:0 0 0 1px rgba(178,116,59,0.3);
            outline:none;
        }
        .select {
            width:100%;
            border-radius:999px;
            border:1px solid rgba(209,213,219,1);
            padding:9px 14px;
            font-size:14px;
            background:#f9fafb;
            outline:none;
            transition:border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .select:focus {
            border-color:var(--accent);
            background:#ffffff;
            box-shadow:0 0 0 1px rgba(178,116,59,0.3);
        }
        .hint {
            font-size:11px;
            color:#9ca3af;
            margin-top:2px;
        }
        .hint-error {
            color:#b91c1c;
        }

        .section-heading {
            font-size:16px;
            font-weight:600;
            margin:16px 0 6px;
        }
        .section-sub {
            font-size:13px;
            color:var(--text-muted);
            margin:0 0 14px;
        }

        .media-row {
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            align-items:stretch;
        }
        .cover-preview {
            flex:1;
            min-width:260px;
            border-radius:20px;
            overflow:hidden;
            position:relative;
            background:linear-gradient(135deg, #f3f4f6, #e5e7eb);
            box-shadow:0 18px 40px rgba(148,163,184,0.2);
        }
        .cover-preview img {
            display:block;
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .cover-placeholder {
            position:absolute;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-direction:column;
            gap:6px;
            color:#6b7280;
            font-size:13px;
            text-align:center;
        }
        .cover-placeholder span:first-child {
            font-size:26px;
        }
        .cover-overlay {
            position:absolute;
            inset:auto 12px 12px 12px;
            background:rgba(17,24,39,0.66);
            color:#f9fafb;
            font-size:12px;
            padding:8px 12px;
            border-radius:14px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .media-actions {
            flex:1;
            min-width:240px;
            display:flex;
            flex-direction:column;
            gap:12px;
        }
        .upload-label {
            display:inline-flex;
            align-items:center;
            gap:10px;
            border-radius:999px;
            padding:10px 18px;
            background:var(--accent);
            color:#fff;
            font-weight:600;
            cursor:pointer;
            box-shadow:0 16px 32px rgba(178,116,59,0.4);
            width:max-content;
        }
        .upload-label input {
            display:none;
        }
        .media-actions .hint {
            margin-bottom:0;
        }
        .btn-text-danger {
            border:none;
            background:none;
            color:#dc2626;
            font-size:13px;
            text-decoration:underline;
            cursor:pointer;
            padding:0;
            align-self:flex-start;
        }
        .cover-note {
            font-size:12px;
            color:#9ca3af;
        }
        .cover-chip {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 12px;
            border-radius:999px;
            background:#f3f4f6;
            font-size:12px;
            color:#4b5563;
        }

        .chip-grid {
            display:flex;
            flex-wrap:wrap;
            gap:10px;
        }
        .chip-option {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 14px;
            border-radius:999px;
            border:1px solid var(--border-subtle);
            background:#f9fafb;
            font-size:13px;
            cursor:pointer;
            transition:all 0.18s ease;
        }
        .chip-option input {
            width:16px;
            height:16px;
            border-radius:50%;
            border:1px solid rgba(148,163,184,0.8);
            background:#ffffff;
            appearance:none;
            -webkit-appearance:none;
            position:relative;
            flex-shrink:0;
        }
        .chip-option input:checked {
            background:var(--accent);
            border-color:var(--accent);
        }
        .chip-option input:checked::after {
            content:"";
            position:absolute;
            top:3px;
            left:3px;
            width:8px;
            height:8px;
            border-radius:50%;
            background:#ffffff;
        }
        .chip-option.selected {
            background:var(--accent-soft);
            border-color:var(--accent);
            color:var(--accent-dark);
            box-shadow:0 6px 16px rgba(178,116,59,0.18);
        }
        .chip-option span {
            pointer-events:none;
        }

        .badge-status {
            display:inline-flex;
            align-items:center;
            padding:3px 8px;
            border-radius:999px;
            font-size:11px;
            gap:4px;
            background:#f3f4f6;
            color:#4b5563;
        }
        .badge-status-dot {
            width:8px;height:8px;border-radius:999px;background:#9ca3af;
        }

        .msg-error {
            margin-bottom:10px;
            font-size:13px;
            color:#b91c1c;
            background:#fef2f2;
            border-radius:14px;
            padding:8px 12px;
            border:1px solid #fecaca;
        }
        .msg-success {
            margin-bottom:10px;
            font-size:13px;
            color:#166534;
            background:#dcfce7;
            border-radius:14px;
            padding:8px 12px;
            border:1px solid #86efac;
        }

        /* PRICE PREVIEW */
        .price-preview-box {
            margin-top:10px;
            padding:10px 12px;
            border-radius:18px;
            background:#f9fafb;
            border:1px dashed #e5e7eb;
            font-size:13px;
        }
        .price-preview-line {
            display:flex;
            flex-wrap:wrap;
            gap:6px;
            align-items:baseline;
        }
        .price-current {
            font-weight:600;
        }
        .price-current span {
            font-weight:400;
            font-size:12px;
            color:#6b7280;
        }
        .price-original {
            font-size:12px;
            color:#9ca3af;
            text-decoration:line-through;
        }
        .price-badge {
            font-size:11px;
            font-weight:600;
            background:#dbeafe;
            color:#1d4ed8;
            border-radius:999px;
            padding:3px 10px;
            display:inline-flex;
            align-items:center;
            gap:4px;
        }
        .price-badge img {
            width:14px;
            height:14px;
            object-fit:contain;
            display:block;
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
                    <span class="nav-brand-sub">LISTING EDITOR</span>
                </div>
            </div>
            <div class="nav-center">
                <button type="button" onclick="window.location.href='host-dashboard.php';">Dashboard</button>
                <button type="button" class="active">Listing editor</button>
            </div>
            <div class="nav-right">
                <span class="nav-link"><?php echo h($hostEmail ?: 'Host'); ?></span>
                <button class="nav-pill" type="button" onclick="window.location.href='account-settings.php';">
                    <span style="font-size:16px;">🌐</span>
                    <span class="nav-pill-icon">H</span>
                </button>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="main-inner">

            <!-- SIDEBAR (dummy sections untuk feel Airbnb) -->
            <aside class="sidebar-card">
                <div class="sidebar-title">Your space</div>
                <div class="sidebar-sub">Edit the basics guests see first.</div>

                <div class="side-section-label">Basics</div>
                <button type="button" class="side-nav-link active" data-target="basics">
                    <span>Title & location</span>
                    <span class="badge">Step 1</span>
                </button>

                <div class="side-section-label">Pricing</div>
                <button type="button" class="side-nav-link" data-target="pricing">
                    <span>Rates & discount</span>
                    <span class="badge">Step 2</span>
                </button>

                <div class="side-section-label">Status</div>
                <button type="button" class="side-nav-link" data-target="publish">
                    <span>Publish</span>
                    <span class="badge">Step 3</span>
                </button>
            </aside>

            <!-- FORM -->
            <section class="form-card">
                <div class="form-header">
                    <div class="form-title-block">
                        <div class="form-title">Edit listing basics</div>
                        <div class="form-sub">
                            Listing ID #<?php echo (int)$listingId; ?> ·
                            <span class="badge-status">
                                <span class="badge-status-dot"></span>
                                Current status:
                                <strong style="margin-left:4px;">
                                    <?php echo h($status === 'in_review' ? 'In review' : ucfirst($status)); ?>
                                </strong>
                            </span>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn-secondary" id="previewListingBtn" data-preview-url="<?php echo h($previewUrl); ?>">
                            Preview listing
                        </button>
                        <button type="button" class="btn-secondary"
                                onclick="window.location.href='host-dashboard.php';">
                            Back to dashboard
                        </button>
                        <button type="submit" form="listingForm" class="btn-primary">
                            Save changes
                        </button>
                    </div>
                </div>

                <div class="divider"></div>

                <?php if ($error !== ''): ?>
                    <div class="msg-error"><?php echo h($error); ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div class="msg-success"><?php echo h($success); ?></div>
                <?php endif; ?>

                <form id="listingForm" method="post" action="host-listing-editor.php?id=<?php echo (int)$listingId; ?>" enctype="multipart/form-data">
                    <div class="editor-section" id="section-basics">
                    <!-- TITLE & LOCATION -->
                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Listing title</div>
                            <input
                                type="text"
                                name="title"
                                class="input"
                                placeholder="Cozy studio in the city center"
                                value="<?php echo h($title); ?>"
                                required
                            />
                            <div class="hint">Guests see this first. Keep it short and descriptive.</div>
                        </div>

                        <div class="field-group">
                            <div class="field-label">City</div>
                            <input
                                type="text"
                                name="city"
                                class="input"
                                placeholder="Jakarta"
                                value="<?php echo h($city); ?>"
                            />
                            <div class="hint">We’ll use this to show in search and filters.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Country / Region</div>
                            <input
                                type="text"
                                name="country"
                                class="input"
                                placeholder="Indonesia"
                                value="<?php echo h($country); ?>"
                            />
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Listing description</div>
                            <textarea
                                name="description"
                                class="textarea"
                                rows="4"
                                placeholder="Share a few sentences about the layout, vibe, and nearby highlights."
                            ><?php echo h($description); ?></textarea>
                            <div class="hint">This appears near the top of your listing and helps guests imagine the stay.</div>
                        </div>
                    </div>

                    <div class="section-heading">Cover photo</div>
                    <p class="section-sub">Upload a hero image that best represents your place. This is the first photo guests will see in search results.</p>
                    <div class="media-row">
                        <div class="cover-preview" id="coverPreview">
                            <?php if ($coverPhotoResolved !== ''): ?>
                                <img src="<?php echo h($coverPhotoResolved); ?>" alt="Cover photo preview" id="coverPreviewImage">
                                <div class="cover-overlay">
                                    <span>Featured photo</span>
                                    <span class="cover-chip">Shown on homepage</span>
                                </div>
                            <?php else: ?>
                                <div class="cover-placeholder" id="coverPlaceholder">
                                    <span>📷</span>
                                    <span>No cover photo yet</span>
                                    <small>Add one to make your listing stand out.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="media-actions">
                            <div class="field-label">Upload a new cover</div>
                            <label class="upload-label">
                                <span>⬆️ Upload photo</span>
                                <input type="file" name="cover_photo" id="coverPhotoInput" accept="image/jpeg,image/png,image/webp">
                            </label>
                            <div class="hint">Use landscape photos at least 1600×900 pixels. JPG, PNG, or WebP up to 10&nbsp;MB.</div>
                            <p class="cover-note">Tip: Bright daytime photos with natural light perform best.</p>
                            <?php if ($removeCoverRequested): ?>
                                <p class="cover-note" style="color:#b91c1c;">This listing's previous cover will be removed when you save.</p>
                            <?php endif; ?>
                            <input type="hidden" name="cover_photo_existing" id="coverPhotoExisting" value="<?php echo h($coverPhotoUrl); ?>">
                            <input type="hidden" name="remove_cover" id="removeCoverInput" value="<?php echo $removeCoverRequested ? '1' : '0'; ?>">
                            <?php if ($coverPhotoUrl !== ''): ?>
                                <button type="button" class="btn-text-danger" id="removeCoverBtn">Remove current cover</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    </div>

                    <div class="divider"></div>

                    <div class="editor-section" id="section-pricing">
                    <!-- PRICING -->
                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Nightly price (visible to guests)</div>
                            <input
                                type="number"
                                name="nightly_price"
                                class="input input-number"
                                step="1"
                                min="0"
                                placeholder="e.g. 800000"
                                value="<?php echo $nightly !== null ? h((string)$nightly) : ''; ?>"
                                oninput="updatePricePreview()"
                                id="nightlyPrice"
                            />
                            <div class="hint">Base price per night for weekdays.</div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Original nightly price (strikethrough)</div>
                            <input
                                type="number"
                                name="nightly_price_strike"
                                class="input input-number"
                                step="1"
                                min="0"
                                placeholder="e.g. 1000000"
                                value="<?php echo $nightlyStrike !== null ? h((string)$nightlyStrike) : ''; ?>"
                                oninput="updatePricePreview()"
                                id="nightlyPriceStrike"
                            />
                            <div class="hint">Optional. We’ll show this crossed out if higher than current.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Weekend price</div>
                            <input
                                type="number"
                                name="weekend_price"
                                class="input input-number"
                                step="1"
                                min="0"
                                placeholder="e.g. 900000"
                                value="<?php echo $weekend !== null ? h((string)$weekend) : ''; ?>"
                                oninput="updatePricePreview()"
                                id="weekendPrice"
                            />
                            <div class="hint">Used for Friday–Sunday nights if set.</div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Original weekend price (strikethrough)</div>
                            <input
                                type="number"
                                name="weekend_price_strike"
                                class="input input-number"
                                step="1"
                                min="0"
                                placeholder="e.g. 1100000"
                                value="<?php echo $weekendStrike !== null ? h((string)$weekendStrike) : ''; ?>"
                                oninput="updatePricePreview()"
                                id="weekendPriceStrike"
                            />
                            <div class="hint">Optional. Shown crossed out if higher than weekend price.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                                <input
                                    type="checkbox"
                                    name="has_discount"
                                    value="1"
                                    <?php echo $hasDiscount ? 'checked' : ''; ?>
                                    onclick="updatePricePreview()"
                                    id="hasDiscount"
                                />
                                <span style="font-size:13px;">Highlight this listing with a discount badge</span>
                            </label>
                            <div class="hint">We’ll show a small badge, e.g. “Limited time”, for guests.</div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Discount label (optional)</div>
                            <input
                                type="text"
                                name="discount_label"
                                class="input"
                                placeholder="Last minute deal"
                                maxlength="40"
                                value="<?php echo h($discountLabel); ?>"
                                oninput="updatePricePreview()"
                                id="discountLabel"
                            />
                        </div>
                    </div>

                    <div class="price-preview-box" id="pricePreviewBox">
                        <!-- preview diisi JS -->
                    </div>

                    <div class="divider"></div>

                    <div class="section-heading">Story & highlights</div>
                    <p class="section-sub">Add the details guests look for to understand why your place is special.</p>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Short headline</div>
                            <input
                                type="text"
                                name="headline"
                                class="input"
                                placeholder="Skyline views from every window"
                                maxlength="190"
                                value="<?php echo h($headline); ?>"
                            />
                            <div class="hint">Appears near the top of your listing page and in search cards.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Detailed story</div>
                            <textarea
                                name="story"
                                class="textarea"
                                rows="4"
                                placeholder="Describe the design, mood, and what guests love most."
                            ><?php echo h($story); ?></textarea>
                            <div class="hint">Share a few sentences so guests can picture their stay.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Highlights</div>
                            <div class="chip-grid">
                                <?php foreach ($highlightOptions as $code => $label): ?>
                                    <label class="chip-option">
                                        <input
                                            type="checkbox"
                                            name="highlights[]"
                                            value="<?php echo h($code); ?>"
                                            class="highlight-checkbox"
                                            <?php echo in_array($code, $selectedHighlights, true) ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo h($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="hint" id="highlightNotice">Pick up to five highlights to feature near the top of your listing.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Amenities</div>
                            <div class="chip-grid">
                                <?php foreach ($amenityOptions as $code => $label): ?>
                                    <label class="chip-option">
                                        <input
                                            type="checkbox"
                                            name="amenities[]"
                                            value="<?php echo h($code); ?>"
                                            <?php echo in_array($code, $selectedAmenities, true) ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo h($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="hint">Guests see these as checkmarks and icons when they browse.</div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="section-heading">House rules & arrival</div>
                    <p class="section-sub">Set expectations so guests know how to respect your space.</p>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">House rules</div>
                            <div class="chip-grid">
                                <?php foreach ($houseRuleOptions as $code => $label): ?>
                                    <label class="chip-option">
                                        <input
                                            type="checkbox"
                                            name="house_rules[]"
                                            value="<?php echo h($code); ?>"
                                            <?php echo in_array($code, $selectedHouseRules, true) ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo h($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="hint">These appear before guests confirm their booking.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Additional notes (optional)</div>
                            <textarea
                                name="custom_rules"
                                class="textarea"
                                rows="3"
                                placeholder="Share extra expectations such as community rules or pet policies."
                            ><?php echo h($customRules); ?></textarea>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Check-in window</div>
                            <select name="checkin_window" class="select">
                                <option value="">Not set</option>
                                <?php foreach ($checkinWindowOptions as $value => $label): ?>
                                    <option value="<?php echo h($value); ?>" <?php echo $checkinWindow === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                                <?php if ($checkinWindow !== '' && !isset($checkinWindowOptions[$checkinWindow])): ?>
                                    <option value="<?php echo h($checkinWindow); ?>" selected>Custom: <?php echo h($checkinWindow); ?></option>
                                <?php endif; ?>
                            </select>
                            <div class="hint">Choose when guests can arrive.</div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Check-out time</div>
                            <select name="checkout_time" class="select">
                                <option value="">Not set</option>
                                <?php foreach ($checkoutTimeOptions as $value => $label): ?>
                                    <option value="<?php echo h($value); ?>" <?php echo $checkoutTime === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                                <?php if ($checkoutTime !== '' && !isset($checkoutTimeOptions[$checkoutTime])): ?>
                                    <option value="<?php echo h($checkoutTime); ?>" selected>Custom: <?php echo h($checkoutTime); ?></option>
                                <?php endif; ?>
                            </select>
                            <div class="hint">Help guests plan their departure.</div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Welcome message</div>
                            <textarea
                                name="welcome_message"
                                class="textarea"
                                rows="3"
                                placeholder="Give guests a warm welcome and any arrival tips they should know."
                            ><?php echo h($welcomeMessage); ?></textarea>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group field-group-full">
                            <div class="field-label">Cancellation policy</div>
                            <select name="cancellation_policy" class="select">
                                <option value="">Not set</option>
                                <?php foreach ($cancellationOptions as $value => $label): ?>
                                    <option value="<?php echo h($value); ?>" <?php echo $cancellationPolicy === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                                <?php if ($cancellationPolicy !== '' && !isset($cancellationOptions[$cancellationPolicy])): ?>
                                    <option value="<?php echo h($cancellationPolicy); ?>" selected>Custom: <?php echo h($cancellationPolicy); ?></option>
                                <?php endif; ?>
                            </select>
                            <div class="hint">Choose the cancellation policy guests agree to when they book.</div>
                        </div>
                    </div>

                    </div>

                    <div class="divider"></div>

                    <div class="editor-section" id="section-publish">
                    <div class="field-row">
                        <div class="field-group">
                            <div class="field-label">Listing status</div>
                            <label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:6px;">
                                <input
                                    type="radio"
                                    name="status_choice"
                                    value="draft"
                                    <?php echo $statusChoice === 'draft' ? 'checked' : ''; ?>
                                />
                                <span style="font-size:13px;">
                                    <strong>Keep as draft</strong><br>
                                    <span class="hint">
                                        Listing stays private. Guests can’t see or book it yet.
                                    </span>
                                </span>
                            </label>

                            <label style="display:flex;align-items:flex-start;gap:8px;">
                                <input
                                    type="radio"
                                    name="status_choice"
                                    value="submit"
                                    <?php echo $statusChoice === 'submit' ? 'checked' : ''; ?>
                                />
                                <span style="font-size:13px;">
                                    <strong>Submit for review</strong><br>
                                    <span class="hint">
                                        We’ll mark this as <b>In review</b>. An admin can later publish or reject it.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:14px;display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn-secondary"
                                onclick="window.location.href='host-dashboard.php';">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            Save changes
                        </button>
                    </div>
                    </div>
                </form>
            </section>

        </div>
    </main>
</div>

<script>
    const appBasePath = document.documentElement.dataset.basePath || '';

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

    const discountIconUrl = buildAppUrl('assets/icons/blue-fire.gif');

    // JS preview harga + badge api biru
    function updatePricePreview() {
        const nightlyEl       = document.getElementById('nightlyPrice');
        const nightlyStrikeEl = document.getElementById('nightlyPriceStrike');
        const weekendEl       = document.getElementById('weekendPrice');
        const weekendStrikeEl = document.getElementById('weekendPriceStrike');
        const hasDiscountEl   = document.getElementById('hasDiscount');
        const discountLabelEl = document.getElementById('discountLabel');
        const box             = document.getElementById('pricePreviewBox');

        if (!box) return;

        const nightly       = parseFloat(nightlyEl.value || '0');
        const nightlyStrike = parseFloat(nightlyStrikeEl.value || '0');
        const weekend       = parseFloat(weekendEl.value || '0');
        const weekendStrike = parseFloat(weekendStrikeEl.value || '0');
        const hasDiscount   = hasDiscountEl.checked;
        const labelText     = discountLabelEl.value.trim();

        let html = '';

        // Weekday
        if (nightly > 0) {
            const nStr = nightly.toLocaleString('id-ID');
            let line = `
                <span class="price-current">
                    Rp ${nStr} <span>/ night</span>
                </span>
            `;
            if (nightlyStrike > nightly) {
                const nsStr = nightlyStrike.toLocaleString('id-ID');
                const discPct = Math.round((nightlyStrike - nightly) / nightlyStrike * 100);
                line += `
                    <span class="price-original">Rp ${nsStr}</span>
                    <span class="price-badge">
                        <img src="${discountIconUrl}" alt="discount">
                        ${discPct}% OFF
                    </span>
                `;
            }
            html += `<div class="price-preview-line">${line}</div>`;
        } else {
            html += `
                <div class="price-preview-line">
                    <span class="price-current">
                        Set a nightly price to see how guests will see it <span>/ night</span>
                    </span>
                </div>
            `;
        }

        // Weekend
        if (weekend > 0) {
            const wStr = weekend.toLocaleString('id-ID');
            let wLine = `
                <span class="price-current">
                    Weekend: Rp ${wStr} <span>/ night</span>
                </span>
            `;
            if (weekendStrike > weekend) {
                const wsStr = weekendStrike.toLocaleString('id-ID');
                const wDiscPct = Math.round((weekendStrike - weekend) / weekendStrike * 100);
                wLine += `
                    <span class="price-original">Rp ${wsStr}</span>
                    <span class="price-badge">
                        <img src="${discountIconUrl}" alt="discount">
                        ${wDiscPct}% OFF
                    </span>
                `;
            }
            html += `<br><div class="price-preview-line">${wLine}</div>`;
        }

        // Extra label hint
        if (hasDiscount && labelText !== '') {
            html += `
                <div style="margin-top:6px;font-size:12px;color:#6b7280;">
                    Badge label: <strong>${labelText}</strong> (shown next to price on search card).
                </div>
            `;
        }

        box.innerHTML = html;
    }

    function syncChipClasses() {
        const chipInputs = document.querySelectorAll('.chip-option input');
        chipInputs.forEach(input => {
            const parent = input.closest('.chip-option');
            if (parent) {
                parent.classList.toggle('selected', input.checked);
            }
        });
    }

    function setupChipInputs() {
        const chipInputs = document.querySelectorAll('.chip-option input');
        chipInputs.forEach(input => {
            input.addEventListener('change', syncChipClasses);
        });
        syncChipClasses();
    }

    function setupHighlightLimit() {
        const highlightCheckboxes = Array.from(document.querySelectorAll('.highlight-checkbox'));
        const highlightNotice = document.getElementById('highlightNotice');
        const limit = 5;

        function refreshNotice(message, isError) {
            if (!highlightNotice) return;
            if (!message) {
                const count = highlightCheckboxes.filter(cb => cb.checked).length;
                message = count > 0
                    ? `${count} of ${limit} selected.`
                    : `Pick up to ${limit} highlights to feature near the top of your listing.`;
            }
            highlightNotice.textContent = message;
            highlightNotice.classList.toggle('hint-error', Boolean(isError));
        }

        highlightCheckboxes.forEach(cb => {
            cb.addEventListener('change', event => {
                const count = highlightCheckboxes.filter(item => item.checked).length;
                if (count > limit) {
                    event.target.checked = false;
                    syncChipClasses();
                    refreshNotice(`You can select up to ${limit} highlights.`, true);
                    return;
                }
                syncChipClasses();
                refreshNotice(null, false);
            });
        });

        refreshNotice(null, false);
    }

    function setupCoverUpload() {
        const input         = document.getElementById('coverPhotoInput');
        const removeBtn     = document.getElementById('removeCoverBtn');
        const removeInput   = document.getElementById('removeCoverInput');
        const existingInput = document.getElementById('coverPhotoExisting');
        const preview       = document.getElementById('coverPreview');
        let previewImage  = document.getElementById('coverPreviewImage');
        let placeholder   = document.getElementById('coverPlaceholder');
        let overlay       = preview ? preview.querySelector('.cover-overlay') : null;
        let tempUrl       = null;

        function ensurePlaceholder() {
            if (!preview) return;
            placeholder = document.getElementById('coverPlaceholder');
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.id = 'coverPlaceholder';
                placeholder.className = 'cover-placeholder';
                placeholder.innerHTML = '<span>📷</span><span>No cover photo yet</span><small>Add one to make your listing stand out.</small>';
                preview.appendChild(placeholder);
            }
            placeholder.style.display = 'flex';
        }

        function hidePlaceholder() {
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        }

        function ensureOverlay() {
            if (!preview) return;
            overlay = preview.querySelector('.cover-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'cover-overlay';
                overlay.innerHTML = '<span>Featured photo</span><span class="cover-chip">Shown on homepage</span>';
                preview.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        }

        function clearOverlay() {
            if (overlay) {
                overlay.remove();
                overlay = null;
            }
        }

        function revokeTempUrl() {
            if (tempUrl) {
                URL.revokeObjectURL(tempUrl);
                tempUrl = null;
            }
        }

        if (input) {
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                revokeTempUrl();

                if (!file || !file.type || !file.type.startsWith('image/')) {
                    return;
                }

                tempUrl = URL.createObjectURL(file);
                hidePlaceholder();
                ensureOverlay();

                if (previewImage) {
                    previewImage.src = tempUrl;
                } else if (preview) {
                    previewImage = document.createElement('img');
                    previewImage.id = 'coverPreviewImage';
                    previewImage.alt = 'Cover photo preview';
                    previewImage.src = tempUrl;
                    preview.appendChild(previewImage);
                }

                if (removeInput) {
                    removeInput.value = '0';
                }
                if (existingInput) {
                    existingInput.value = '';
                }
            });
        }

        if (removeBtn && removeInput) {
            removeBtn.addEventListener('click', () => {
                removeInput.value = '1';
                revokeTempUrl();
                if (previewImage) {
                    previewImage.remove();
                    previewImage = null;
                }
                clearOverlay();
                ensurePlaceholder();
                if (input) {
                    input.value = '';
                }
                if (existingInput) {
                    existingInput.value = '';
                }
            });
        }

        window.addEventListener('beforeunload', revokeTempUrl);
    }

    function setupSidebarNavigation() {
        const links = Array.from(document.querySelectorAll('.side-nav-link[data-target]'));
        if (!links.length) {
            return;
        }

        const sections = new Map();

        links.forEach(link => {
            const targetId = link.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            const section = document.getElementById(`section-${targetId}`);
            if (section) {
                section.dataset.sectionId = targetId;
                sections.set(targetId, section);
            }

            link.addEventListener('click', event => {
                event.preventDefault();
                if (!section) {
                    return;
                }

                const offset = section.getBoundingClientRect().top + window.scrollY - 90;
                window.scrollTo({ top: offset, behavior: 'smooth' });
            });
        });

        if (!sections.size) {
            return;
        }

        const setActive = (targetId) => {
            links.forEach(link => {
                link.classList.toggle('active', link.getAttribute('data-target') === targetId);
            });
        };

        const observer = new IntersectionObserver(entries => {
            const visible = entries.filter(entry => entry.isIntersecting);
            if (!visible.length) {
                return;
            }

            visible.sort((a, b) => b.intersectionRatio - a.intersectionRatio);
            const id = visible[0].target.dataset.sectionId;
            if (id) {
                setActive(id);
            }
        }, {
            root: null,
            rootMargin: '-40% 0px -50% 0px',
            threshold: [0.2, 0.4, 0.6],
        });

        sections.forEach(section => observer.observe(section));

        const initial = links.find(link => link.classList.contains('active'));
        if (initial && sections.has(initial.getAttribute('data-target'))) {
            setActive(initial.getAttribute('data-target'));
        }
    }

    function setupPreviewButton() {
        const btn = document.getElementById('previewListingBtn');
        if (!btn) {
            return;
        }

        const previewUrl = btn.getAttribute('data-preview-url');
        if (!previewUrl) {
            btn.disabled = true;
            btn.title = 'Preview unavailable for this listing.';
            return;
        }

        btn.addEventListener('click', () => {
            window.open(previewUrl, '_blank', 'noopener');
        });
    }

    // initial render on load
    document.addEventListener('DOMContentLoaded', function () {
        updatePricePreview();
        setupChipInputs();
        setupHighlightLimit();
        setupCoverUpload();
        setupSidebarNavigation();
        setupPreviewButton();
    });
</script>
</body>
</html>
