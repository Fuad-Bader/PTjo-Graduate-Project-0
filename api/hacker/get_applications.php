<?php
/**
 * PTjo — Hacker: List the bounties I have applied to ("My Queue")
 * GET /api/hacker/get_applications.php
 *
 * Returns each of the hacker's applications joined with the bounty it targets,
 * so the dashboard can show a queue of everything they've applied to and the
 * current status of each application (pending / accepted / rejected).
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
$pdo  = db();

// The application timestamp column name varies across schema versions
// (applied_at / created_at / submitted_at); detect whichever exists.
$tsCol = null;
foreach (['applied_at', 'created_at', 'submitted_at'] as $c) {
    $chk = $pdo->prepare(
        "SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'hacker_applications'
           AND column_name = ? LIMIT 1"
    );
    $chk->execute([$c]);
    if ($chk->fetchColumn()) { $tsCol = $c; break; }
}
$appliedSel = $tsCol ? ('ha.' . $tsCol) : 'NULL';
$orderTs    = $tsCol ? ('ha.' . $tsCol) : 'ha.id';

$st = $pdo->prepare(
    "SELECT
        ha.id            AS application_id,
        ha.status        AS application_status,
        ha.availability_note,
        $appliedSel      AS applied_at,
        ha.resolved_at,
        br.id            AS bounty_id,
        br.public_id,
        br.service_key,
        br.service_label,
        br.icon,
        br.price_amount,
        br.priority_text,
        br.deadline,
        br.scope_details,
        br.status        AS bounty_status,
        br.assigned_hacker_id,
        cp.display_name  AS customer_name
    FROM hacker_applications ha
    JOIN bounty_requests br ON br.id = ha.request_id
    LEFT JOIN customer_profiles cp ON cp.user_id = br.customer_id
    WHERE ha.hacker_id = ?
    ORDER BY $orderTs DESC"
);
$st->execute([$user['id']]);

json_ok(['applications' => $st->fetchAll()]);
