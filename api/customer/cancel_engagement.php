<?php
/**
 * PTjo — Customer: Close an engagement as FAILED / CANCELLED (no payment).
 * POST /api/customer/cancel_engagement.php
 * Body (JSON): { "engagement_id": "<uuid>" }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body         = json_input();
$engagementId = trim($body['engagement_id'] ?? '');
if (!$engagementId) json_error('engagement_id is required.');

$pdo = db();
$st  = $pdo->prepare(
    'SELECT id, hacker_id, status, bounty_request_id, service_type
     FROM engagements WHERE id = ? AND customer_id = ? LIMIT 1'
);
$st->execute([$engagementId, $user['id']]);
$eng = $st->fetch();

if (!$eng) json_error('Engagement not found.', 404);
if (in_array($eng['status'], ['completed', 'cancelled', 'declined'], true)) {
    json_error('This engagement is already closed.');
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE engagements SET status = 'cancelled' WHERE id = ?")->execute([$engagementId]);
    if ($eng['bounty_request_id']) {
        $pdo->prepare("UPDATE bounty_requests SET status = 'cancelled' WHERE id = ?")
            ->execute([$eng['bounty_request_id']]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('cancel_engagement: ' . $e->getMessage());
    json_error('Could not close the bounty. Please try again.');
}

notify($eng['hacker_id'], 'engagement_cancelled', 'Engagement Closed',
    'The customer closed this engagement as not completed.',
    ['engagement_id' => $engagementId]);

audit('cancel_engagement', 'engagements', $engagementId,
    ['status' => $eng['status']], ['status' => 'cancelled']);

json_ok(['message' => 'Engagement closed.']);
