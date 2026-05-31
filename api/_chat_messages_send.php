<?php
/**
 * Shared chat POST handler.
 * The including role endpoint must already have set $user (via require_role)
 * and called csrf_verify().
 *
 * Body (JSON): { "engagement_id": "<uuid>", "body": "..." }
 */

$body         = json_input();
$engagementId = trim($body['engagement_id'] ?? '');
$text         = trim($body['body'] ?? '');

if (!$engagementId) json_error('engagement_id is required.');
if ($text === '')   json_error('Message cannot be empty.');
if (mb_strlen($text) > 4000) $text = mb_substr($text, 0, 4000);

$pdo = db();

$st = $pdo->prepare(
    'SELECT id, customer_id, hacker_id FROM engagements
     WHERE id = ? AND (customer_id = ? OR hacker_id = ?) LIMIT 1'
);
$st->execute([$engagementId, $user['id'], $user['id']]);
$eng = $st->fetch();
if (!$eng) json_error('Engagement not found.', 404);

$pdo->prepare(
    'INSERT INTO engagement_messages (engagement_id, sender_id, body) VALUES (?, ?, ?)'
)->execute([$engagementId, $user['id'], $text]);

// Notify the other party.
$recipient = ($eng['customer_id'] === $user['id']) ? $eng['hacker_id'] : $eng['customer_id'];
notify(
    $recipient,
    'new_message',
    'New message',
    mb_substr($text, 0, 120),
    ['engagement_id' => $engagementId]
);

json_ok(['message' => 'sent']);
