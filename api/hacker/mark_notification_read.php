<?php
/**
 * PTjo — Hacker: Mark notification(s) as read
 * POST /api/hacker/mark_notification_read.php
 * Body (JSON): { "notification_id": "<uuid>" } or { "mark_all": true }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body    = json_input();
$markAll = (bool)($body['mark_all'] ?? false);
$nid     = trim($body['notification_id'] ?? '');
$pdo     = db();

if ($markAll) {
    $pdo->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL')
        ->execute([$user['id']]);
    json_ok(['message' => 'All notifications marked as read.']);
}

if (!$nid) json_error('notification_id is required.');
$pdo->prepare('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?')
    ->execute([$nid, $user['id']]);

json_ok(['message' => 'Notification marked as read.']);
