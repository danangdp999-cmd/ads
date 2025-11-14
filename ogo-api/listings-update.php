<?php
// listings-update.php – update listing via POST JSON (very simple)

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/config.php';

if (!function_exists('json_response')) {
    function json_response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
        exit;
    }
}

// Cek koneksi DB
try {
    $pdo = db();
} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB connect failed'
    ), 500);
}

// Kalau GET: health check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(array(
        'status'  => 'ok',
        'message' => 'listings-update alive, POST JSON',
        'db'      => DB_NAME
    ), 200);
}

// Baca JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid JSON body'
    ), 400);
}

function g($key, $default = '') {
    global $data;
    if (isset($data[$key]) && !is_array($data[$key])) {
        return trim((string)$data[$key]);
    }
    return $default;
}

function g_nullable(string $key): ?string
{
    global $data;
    if (!array_key_exists($key, $data) || is_array($data[$key])) {
        return null;
    }

    $value = trim((string)$data[$key]);
    return $value === '' ? null : $value;
}

function limit_string(?string $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function read_string_array(string $key): array
{
    global $data;
    if (!isset($data[$key]) || !is_array($data[$key])) {
        return array();
    }

    $values = array();
    foreach ($data[$key] as $item) {
        if (is_string($item) || is_numeric($item)) {
            $trimmed = trim((string)$item);
            if ($trimmed !== '') {
                $values[] = $trimmed;
            }
        }
    }

    return $values;
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

function fetch_amenity_ids(PDO $pdo, array $codes): array
{
    if (empty($codes)) {
        return array();
    }

    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare('SELECT id, code FROM amenities WHERE code IN (' . $placeholders . ')');
    $stmt->execute($codes);

    $map = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['code']] = (int)$row['id'];
    }

    return $map;
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
        ':listing_id'        => $listingId,
        ':current_step'      => $step,
        ':completed_sections'=> $completedJson,
    ]);
}

// Ambil dan validasi field
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid listing id'
    ), 400);
}

$title       = g('title');
$description = g('description');
$guestsRaw   = g('guests');
$bedroomsRaw = array_key_exists('bedrooms', $data) ? trim((string)$data['bedrooms']) : '';
$bathroomsRaw= array_key_exists('bathrooms', $data) ? trim((string)$data['bathrooms']) : '';
$priceRaw    = g('nightly_price', '');
$status      = g('status', 'draft');

$missingFields = array();

if ($title === '') {
    $missingFields[] = 'title';
}

if ($description === '') {
    $missingFields[] = 'description';
}

$guests = null;
if ($guestsRaw === '') {
    $missingFields[] = 'guests';
} elseif (is_numeric($guestsRaw)) {
    $guests = max(1, (int)$guestsRaw);
} else {
    $missingFields[] = 'guests';
}

$bedrooms = null;
if ($bedroomsRaw !== '') {
    $bedrooms = is_numeric($bedroomsRaw) ? (int)$bedroomsRaw : null;
    if ($bedrooms === null) {
        $missingFields[] = 'bedrooms';
    }
}

$bathrooms = null;
if ($bathroomsRaw !== '') {
    $bathrooms = is_numeric($bathroomsRaw) ? (int)$bathroomsRaw : null;
    if ($bathrooms === null) {
        $missingFields[] = 'bathrooms';
    }
}

$price = null;
if ($priceRaw === '') {
    $missingFields[] = 'nightly_price';
} elseif (is_numeric($priceRaw)) {
    $price = (float)$priceRaw;
    if ($price <= 0) {
        $missingFields[] = 'nightly_price';
    }
} else {
    $missingFields[] = 'nightly_price';
}

if (!empty($missingFields)) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Missing or invalid fields: ' . implode(', ', array_unique($missingFields))
    ), 400);
}

// jaga status hanya draft/published
if ($status !== 'draft' && $status !== 'published') $status = g('status', 'draft');

// hanya izinkan 4 nilai ini
$allowedStatus = array('draft','in_review','published','rejected');
if (!in_array($status, $allowedStatus, true)) {
    $status = 'draft';
}

$headline           = limit_string(g_nullable('headline'), 190);
$story              = g_nullable('story');
$customRules        = g_nullable('custom_rules');
$checkinWindow      = limit_string(g_nullable('checkin_window'), 60);
$checkoutTime       = limit_string(g_nullable('checkout_time'), 60);
$welcomeMessage     = g_nullable('welcome_message');
$cancellationPolicy = limit_string(g_nullable('cancellation_policy'), 60);

$highlightCodes = array_values(array_unique(read_string_array('highlights')));
$allowedHighlights = array('great_location','city_view','fast_wifi','self_check_in','workspace','parking','pet_friendly','long_stays');
$highlightCodes = array_values(array_intersect($highlightCodes, $allowedHighlights));
if (count($highlightCodes) > 5) {
    $highlightCodes = array_slice($highlightCodes, 0, 5);
}

$amenityInput = array_values(array_unique(read_string_array('amenities')));
$amenityMap = array(
    'wifi'              => 'wifi',
    'air_conditioning'  => 'air_conditioning',
    'kitchen'           => 'kitchen',
    'washing_machine'   => 'washer',
    'dryer'             => 'dryer',
    'tv'                => 'tv',
    'workspace'         => 'dedicated_workspace',
    'balcony'           => 'balcony',
);

$mappedAmenityCodes = array();
foreach ($amenityInput as $code) {
    if (isset($amenityMap[$code])) {
        $mappedAmenityCodes[$amenityMap[$code]] = true;
    }
}
$amenityCodes = array_keys($mappedAmenityCodes);

$houseRules = array_values(array_unique(read_string_array('house_rules')));
$allowedHouseRules = array('no_smoking','no_pets','no_parties','quiet_hours','suitable_children');
$houseRules = array_values(array_intersect($houseRules, $allowedHouseRules));
if (count($houseRules) > 10) {
    $houseRules = array_slice($houseRules, 0, 10);
}

$highlightsJson = !empty($highlightCodes) ? json_encode($highlightCodes) : null;
$amenitiesJson  = !empty($amenityInput) ? json_encode($amenityInput) : null;
$houseRulesJson = !empty($houseRules) ? json_encode($houseRules) : null;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE simple_listings
         SET title = :title,
             description = :description,
             guests = :guests,
             bedrooms = :bedrooms,
             bathrooms = :bathrooms,
             nightly_price = :nightly_price,
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
             cancellation_policy = :cancellation_policy,
             updated_at = NOW()
         WHERE id = :id"
    );

    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':guests', $guests, PDO::PARAM_INT);
    if ($bedrooms === null) {
        $stmt->bindValue(':bedrooms', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':bedrooms', $bedrooms, PDO::PARAM_INT);
    }
    if ($bathrooms === null) {
        $stmt->bindValue(':bathrooms', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':bathrooms', $bathrooms, PDO::PARAM_INT);
    }
    $stmt->bindValue(':nightly_price', $price, PDO::PARAM_STR);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':headline', $headline, $headline === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':story', $story, $story === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':highlights_json', $highlightsJson, $highlightsJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':amenities_json', $amenitiesJson, $amenitiesJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':house_rules_json', $houseRulesJson, $houseRulesJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':custom_rules', $customRules, $customRules === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':checkin_window', $checkinWindow, $checkinWindow === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':checkout_time', $checkoutTime, $checkoutTime === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':welcome_message', $welcomeMessage, $welcomeMessage === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':cancellation_policy', $cancellationPolicy, $cancellationPolicy === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    $stmt->execute();

    $amenityIds = array();
    if (!empty($amenityCodes)) {
        $codeToId = fetch_amenity_ids($pdo, $amenityCodes);
        foreach ($amenityCodes as $code) {
            if (isset($codeToId[$code])) {
                $amenityIds[] = $codeToId[$code];
            }
        }
    }

    sync_listing_highlights($pdo, $id, $highlightCodes);
    sync_listing_amenities($pdo, $id, $amenityIds);

    $completedSections = array('stage1_basics');
    if (!empty($highlightCodes) || !empty($amenityCodes)) {
        $completedSections[] = 'stage2_details';
    }
    if (!empty($houseRules) || $customRules !== null || $checkinWindow !== null || $checkoutTime !== null) {
        $completedSections[] = 'stage3_finish';
    }
    $currentStep = !empty($completedSections) && in_array('stage3_finish', $completedSections, true) ? '3' : (count($completedSections) >= 2 ? '2' : '1');

    sync_wizard_progress($pdo, $id, $currentStep, $completedSections);

    $pdo->commit();

    json_response(array(
        'status'             => 'ok',
        'message'            => 'Listing updated',
        'saved_highlights'   => $highlightCodes,
        'saved_amenities'    => $amenityCodes,
        'saved_house_rules'  => $houseRules,
        'current_step'       => $currentStep,
    ), 200);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('listings-update error for listing ' . $id . ': ' . $e->getMessage());
    json_response(array(
        'status'  => 'error',
        'message' => 'DB update error: ' . $e->getMessage()
    ), 500);
}
