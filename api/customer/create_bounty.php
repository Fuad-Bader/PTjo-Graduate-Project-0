<?php
/**
 * PTjo — Customer: Create bounty request
 * POST /api/customer/create_bounty.php
 * Body (JSON): service_key, service_label, icon, price_amount,
 *              priority_text, deadline, scope_details
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body = json_input();

// ── Validate ──────────────────────────────────────────────────────────────────
$allowedServices = ['network','ad','cloud','webapp','mobile','api','iot'];
$serviceKey   = trim($body['service_key']   ?? '');
$serviceLabel = trim($body['service_label'] ?? $body['title'] ?? '');
$icon         = trim($body['icon']          ?? '');
$priceAmount  = (float)($body['price_amount'] ?? $body['budget'] ?? 0);
$priorityText = trim($body['priority_text']  ?? '');
$deadline     = trim($body['deadline']       ?? '');

// Accept scope_details OR scope OR description (dashboard may send any of these)
$scopeDetails = trim(
    $body['scope_details'] ??
    $body['scope']         ??
    $body['description']   ??
    ''
);
// Append requirements if provided separately
$requirements = trim($body['requirements'] ?? '');
if ($requirements && strlen($requirements) > 2) {
    $scopeDetails .= ($scopeDetails ? "\n\nRequirements:\n" : '') . $requirements;
}

if (!in_array($serviceKey, $allowedServices, true)) json_error('Invalid service type.');
if ($priceAmount < 0)                                json_error('Price cannot be negative.');
if (strlen($scopeDetails) < 10)                     json_error('Scope / description is required (min 10 chars).');
if ($deadline && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
    json_error('Invalid deadline format (YYYY-MM-DD).');
}

$serviceLabel = $serviceLabel ?: ucfirst($serviceKey) . ' Testing';
$publicId     = 'BR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

$pdo = db();
$pdo->prepare(
    'INSERT INTO bounty_requests
     (public_id, customer_id, service_key, service_label, icon, price_amount,
      priority_text, deadline, scope_details)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    $publicId,
    $user['id'],
    $serviceKey,
    substr($serviceLabel, 0, 255),
    substr($icon, 0, 120),
    $priceAmount,
    substr($priorityText, 0, 120),
    $deadline ?: null,
    $scopeDetails,
]);

$id = $pdo->query(
    'SELECT id FROM bounty_requests WHERE public_id = ' . $pdo->quote($publicId) . ' LIMIT 1'
)->fetchColumn();

notify($user['id'], 'bounty_created', 'Bounty Posted',
    'Your ' . $serviceLabel . ' bounty has been posted.',
    ['public_id' => $publicId, 'id' => $id]);

audit('create_bounty', 'bounty_requests', $id, null,
    ['public_id' => $publicId, 'service_key' => $serviceKey]);

json_ok(['public_id' => $publicId, 'bounty_id' => $id, 'id' => $id], 201);
