<?php
/**
 * PTjo — Customer: Get own engagements
 * GET /api/customer/get_engagements.php[?status=in_progress]
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');

$allowedStatuses = ['pending','accepted','in_progress','completed','declined','cancelled'];
$statusFilter    = input('status');
$pdo = db();

$sql = '
    SELECT e.*,
           hp.display_name AS hacker_name,
           hp.handle       AS hacker_handle,
           hp.avatar_url   AS hacker_avatar
    FROM engagements e
    LEFT JOIN hacker_profiles hp ON hp.user_id = e.hacker_id
    WHERE e.customer_id = ?
';
$params = [$user['id']];

if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
    $sql     .= ' AND e.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY e.created_at DESC';

$st = $pdo->prepare($sql);
$st->execute($params);
$engagements = $st->fetchAll();

foreach ($engagements as &$eng) {
    $rst = $pdo->prepare('SELECT COUNT(*) FROM vulnerability_reports WHERE engagement_id = ?');
    $rst->execute([$eng['id']]);
    $eng['report_count'] = (int)$rst->fetchColumn();
}

json_ok(['engagements' => $engagements]);
