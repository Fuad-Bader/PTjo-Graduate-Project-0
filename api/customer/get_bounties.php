<?php
/**
 * PTjo — Customer: Get own bounty requests
 * GET /api/customer/get_bounties.php[?status=open]
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');

$allowedStatuses = ['open','assigned','cancelled','completed'];
$statusFilter    = input('status');
$pdo = db();

if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
    $st = $pdo->prepare(
        'SELECT br.*, hp.display_name AS hacker_name, hp.avatar_url AS hacker_avatar
         FROM bounty_requests br
         LEFT JOIN hacker_profiles hp ON hp.user_id = br.assigned_hacker_id
         WHERE br.customer_id = ? AND br.status = ?
         ORDER BY br.created_at DESC'
    );
    $st->execute([$user['id'], $statusFilter]);
} else {
    $st = $pdo->prepare(
        'SELECT br.*, hp.display_name AS hacker_name, hp.avatar_url AS hacker_avatar
         FROM bounty_requests br
         LEFT JOIN hacker_profiles hp ON hp.user_id = br.assigned_hacker_id
         WHERE br.customer_id = ?
         ORDER BY br.created_at DESC'
    );
    $st->execute([$user['id']]);
}

json_ok(['bounties' => $st->fetchAll()]);
