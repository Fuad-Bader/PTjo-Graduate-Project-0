<?php
/**
 * Shared chat GET handler.
 * The including role endpoint must already have set $user (via require_role).
 * Returns the message thread for an engagement the user is a party to, and
 * marks the other party's messages as read.
 *
 * GET ?engagement_id=<uuid>
 */

$engagementId = input('engagement_id');
if (!$engagementId) json_error('engagement_id is required.');

$pdo = db();

// The engagement must belong to this user (as customer or hacker).
$st = $pdo->prepare(
    'SELECT id, customer_id, hacker_id FROM engagements
     WHERE id = ? AND (customer_id = ? OR hacker_id = ?) LIMIT 1'
);
$st->execute([$engagementId, $user['id'], $user['id']]);
$eng = $st->fetch();
if (!$eng) json_error('Engagement not found.', 404);

// Mark inbound (not-mine) messages as read.
$pdo->prepare(
    'UPDATE engagement_messages SET read_at = NOW()
     WHERE engagement_id = ? AND sender_id <> ? AND read_at IS NULL'
)->execute([$engagementId, $user['id']]);

$st = $pdo->prepare(
    "SELECT m.id, m.sender_id, m.body, m.created_at, m.read_at,
            COALESCE(cp.display_name, hp.display_name, u.email) AS sender_name
     FROM engagement_messages m
     JOIN users u ON u.id = m.sender_id
     LEFT JOIN customer_profiles cp ON cp.user_id = m.sender_id
     LEFT JOIN hacker_profiles   hp ON hp.user_id = m.sender_id
     WHERE m.engagement_id = ?
     ORDER BY m.created_at ASC"
);
$st->execute([$engagementId]);
$messages = $st->fetchAll();

foreach ($messages as &$m) {
    $m['mine'] = ($m['sender_id'] === $user['id']);
}

json_ok(['messages' => $messages, 'me' => $user['id']]);
