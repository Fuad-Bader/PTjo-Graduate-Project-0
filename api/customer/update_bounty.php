<?php
/**
 * PTjo — Customer: Update an open bounty request
 * POST /api/customer/update_bounty.php
 * Body (JSON): bounty_id, service_key, service_label, price_amount,
 *              priority_text, deadline, scope_details
 *
 * Only the owning customer may edit, and only while the bounty is still open.
 * Omitted fields keep their current value.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body     = json_input();
$bountyId = trim($body['bounty_id'] ?? '');
if (!$bountyId) json_error('bounty_id is required.');

$pdo = db();
$st  = $pdo->prepare(
    'SELECT id, status, service_key, service_label, price_amount,
            priority_text, deadline, scope_details
     FROM bounty_requests WHERE id = ? AND customer_id = ? LIMIT 1'
);
$st->execute([$bountyId, $user['id']]);
$bounty = $st->fetch();

if (!$bounty)                       json_error('Bounty not found.', 404);
if ($bounty['status'] !== 'open')   json_error('Only open bounties can be edited.');

$allowedServices = ['network','ad','cloud','webapp','mobile','api','iot'];

// Fall back to existing values when a field is omitted.
$serviceKey   = trim($body['service_key']   ?? $bounty['service_key']);
$serviceLabel = trim($body['service_label'] ?? (string)$bounty['service_label']);
$priceAmount  = isset($body['price_amount']) ? (float)$body['price_amount'] : (float)$bounty['price_amount'];
$priorityText = trim($body['priority_text'] ?? (string)$bounty['priority_text']);
$deadline     = array_key_exists('deadline', $body)
                  ? trim((string)$body['deadline'])
                  : (string)$bounty['deadline'];
$scopeDetails = trim($body['scope_details'] ?? (string)$bounty['scope_details']);

if (!in_array($serviceKey, $allowedServices, true)) json_error('Invalid service type.');
if ($priceAmount < 0)                                json_error('Price cannot be negative.');
if (strlen($scopeDetails) < 10)                     json_error('Scope / description is required (min 10 chars).');
if ($deadline && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
    json_error('Invalid deadline format (YYYY-MM-DD).');
}

$serviceLabel = $serviceLabel ?: (ucfirst($serviceKey) . ' Testing');

$pdo->prepare(
    'UPDATE bounty_requests
        SET service_key   = ?,
            service_label = ?,
            price_amount  = ?,
            priority_text = ?,
            deadline      = ?,
            scope_details = ?
      WHERE id = ? AND customer_id = ?'
)->execute([
    $serviceKey,
    substr($serviceLabel, 0, 255),
    $priceAmount,
    substr($priorityText, 0, 120),
    $deadline ?: null,
    $scopeDetails,
    $bountyId,
    $user['id'],
]);

audit('update_bounty', 'bounty_requests', $bountyId,
    [
        'price_amount' => $bounty['price_amount'],
        'deadline'     => $bounty['deadline'],
        'scope_details'=> $bounty['scope_details'],
    ],
    [
        'price_amount' => $priceAmount,
        'deadline'     => $deadline,
        'scope_details'=> $scopeDetails,
    ]
);

json_ok(['message' => 'Bounty updated.', 'bounty_id' => $bountyId]);
