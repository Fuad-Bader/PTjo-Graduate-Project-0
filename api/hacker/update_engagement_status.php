<?php
/**
 * PTjo — Hacker: Accept or decline an engagement
 * POST /api/hacker/update_engagement_status.php
 * Body (JSON): { "engagement_id": "<uuid>", "action": "accept"|"decline"|"complete", "status_note": "..." }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body         = json_input();
$engId        = trim($body['engagement_id'] ?? '');
$action       = trim($body['action']        ?? '');
$statusNote   = substr(trim($body['status_note'] ?? ''), 0, 1000);

if (!$engId) json_error('engagement_id is required.');

$validActions = ['accept' => 'accepted', 'decline' => 'declined', 'complete' => 'completed', 'start' => 'in_progress'];
if (!array_key_exists($action, $validActions)) {
    json_error('action must be one of: ' . implode(', ', array_keys($validActions)));
}

$pdo = db();
$st  = $pdo->prepare('SELECT * FROM engagements WHERE id = ? AND hacker_id = ? LIMIT 1');
$st->execute([$engId, $user['id']]);
$eng = $st->fetch();

if (!$eng) json_error('Engagement not found.', 404);

// Allowed transitions
$transitions = [
    'pending'     => ['accept', 'decline'],
    'accepted'    => ['start', 'decline'],
    'in_progress' => ['complete'],
];

if (!in_array($action, $transitions[$eng['status']] ?? [], true)) {
    json_error("Cannot '$action' an engagement in status '{$eng['status']}'.");
}

$newStatus = $validActions[$action];

$pdo->prepare('UPDATE engagements SET status = ?, status_note = ? WHERE id = ?')
    ->execute([$newStatus, $statusNote ?: null, $engId]);

// Notify customer
$messages = [
    'accepted'    => 'Your engagement has been accepted by the pentester.',
    'declined'    => 'The pentester has declined your engagement.',
    'in_progress' => 'Work has started on your engagement.',
    'completed'   => 'Your engagement has been marked complete by the pentester.',
];
notify($eng['customer_id'], 'engagement_' . $newStatus,
    'Engagement Update',
    $messages[$newStatus] ?? 'Engagement status changed.',
    ['engagement_id' => $engId, 'public_id' => $eng['public_id']]);

audit('engagement_status', 'engagements', $engId, ['status' => $eng['status']], ['status' => $newStatus]);
json_ok(['message' => 'Engagement status updated.', 'new_status' => $newStatus]);
