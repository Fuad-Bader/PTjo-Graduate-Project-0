<?php
/**
 * PTjo — Hacker: Get own vulnerability reports
 * GET /api/hacker/get_reports.php[?status=submitted&engagement_id=<uuid>]
 */

require_once __DIR__ . '/../../config/auth.php';

$user             = require_role('hacker');
$statusFilter     = input('status');
$engagementFilter = input('engagement_id');
$pdo              = db();

$allowedStatuses = ['submitted','under_review','edit_requested','approved','paid','rejected','archived'];

$sql = '
    SELECT vr.*,
           cp.display_name AS customer_name,
           cp.company_name,
           e.public_id     AS engagement_public_id,
           e.service_type
    FROM vulnerability_reports vr
    JOIN customer_profiles cp ON cp.user_id = vr.customer_id
    JOIN engagements e         ON e.id = vr.engagement_id
    WHERE vr.hacker_id = ?
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
