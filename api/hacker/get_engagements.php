<?php
/**
 * PTjo — Hacker: Get own engagements
 * GET /api/hacker/get_engagements.php[?status=in_progress]
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');

$allowedStatuses = ['pending','accepted','in_progress','completed','declined','cancelled'];
$statusFilter    = input('status');
$pdo = db();

$sql = '
    SELECT e.*,
           cp.display_name AS customer_name,
           cp.company_name,
           cp.avatar_url   AS customer_avatar
    FROM engagements e
    LEFT JOIN customer_profiles cp ON cp.user_id = e.customer_id
    WHERE e.hacker_id = ?
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

// Attach report count per engagement
foreach ($engagements as &$eng) {
    $rst = $pdo->prepare('SELECT COUNT(*) FROM vulnerability_reports WHERE engagement_id = ?');
    $rst->execute([$eng['id']]);
    $eng['report_count'] = (int)$rst->fetchColumn();
}

json_ok(['engagements' => $engagements]);
