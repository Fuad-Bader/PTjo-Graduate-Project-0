<?php
/**
 * PTjo — Hacker: Get in-app notifications
 * GET /api/hacker/get_notifications.php[?unread_only=1]
 */

require_once __DIR__ . '/../../config/auth.php';

$user       = require_role('hacker');
$unreadOnly = (input('unread_only') === '1');
$pdo        = db();

$sql    = 'SELECT * FROM notifications WHERE user_id = ? AND channel = "in_app"';
$params = [$user['id']];

if ($unreadOnly) {
    $sql .= ' AND read_at IS NULL';
}
$sql .= ' ORDER BY created_at DESC LIMIT 50';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

foreach ($rows as &$n) {
    $n['payload'] = json_decode($n['payload'], true) ?? [];
}

json_ok([
    'notifications' => $rows,
    'unread_count'  => array_sum(array_map(fn($n) => is_null($n['read_at']) ? 1 : 0, $rows)),
]);
