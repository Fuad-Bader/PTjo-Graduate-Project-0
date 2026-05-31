<?php
/**
 * PTjo — Customer: Get vulnerability reports submitted against their engagements
 * GET /api/customer/get_reports.php[?status=submitted&engagement_id=<uuid>]
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');

$statusFilter     = input('status');
$engagementFilter = input('engagement_id');
$pdo = db();

$allowedStatuses = ['submitted','under_review','edit_requested','approved','paid','rejected','archived'];

$sql = '
    SELECT vr.*,
           hp.display_name AS hacker_name,
           hp.avatar_url   AS hacker_avatar,
           hp.handle,
           e.public_id     AS engagement_public_id,
           e.service_type
    FROM vulnerability_reports vr
    JOIN hacker_profiles hp ON hp.user_id = vr.hacker_id
    JOIN engagements e       ON e.id = vr.engagement_id
    WHERE vr.customer_id = ?
';

$params = [$user['id']];

if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
    $sql     .= ' AND vr.status = ?';
    $params[] = $statusFilter;
}

if ($engagementFilter) {
    $sql     .= ' AND vr.engagement_id = ?';
    $params[] = $engagementFilter;
}

$sql .= ' ORDER BY vr.submitted_at DESC';

$st = $pdo->prepare($sql);
$st->execute($params);
$reports = $st->fetchAll();

// Attach attachments
foreach ($reports as &$r) {
    $ast = $pdo->prepare('SELECT * FROM report_attachments WHERE report_id = ?');
    $ast->execute([$r['id']]);
    $r['attachments'] = $ast->fetchAll();
}

json_ok(['reports' => $reports]);
