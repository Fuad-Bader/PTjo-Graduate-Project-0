<?php
/**
 * PTjo — Customer: Get applicants for one of my own bounties
 * GET /api/customer/get_applicants.php?bounty_id=<uuid>
 *
 * Returns the hackers who applied to this bounty (hacker_applications joined
 * with their profile + review stats), so the customer can review and hire one.
 */

require_once __DIR__ . '/../../config/auth.php';

$user     = require_role('customer');
$bountyId = input('bounty_id');
if (!$bountyId) json_error('bounty_id is required.');

$pdo = db();

// Bounty must belong to the requesting customer.
$st = $pdo->prepare('SELECT id, status FROM bounty_requests WHERE id = ? AND customer_id = ? LIMIT 1');
$st->execute([$bountyId, $user['id']]);
$bounty = $st->fetch();
if (!$bounty) json_error('Bounty not found.', 404);

// The application timestamp column name varies across schema versions
// (applied_at / created_at / submitted_at); detect whichever exists.
$tsCol = application_ts_column($pdo);
$appliedSel = $tsCol ? ('ha.' . $tsCol) : 'NULL';
$orderTs    = $tsCol ? ('ha.' . $tsCol) : 'ha.id';

// No GROUP BY: skills + jobs are scalar subqueries so every selected column is
// 1:1 with the application row (avoids ONLY_FULL_GROUP_BY surprises with views).
$st = $pdo->prepare(
    "SELECT
        ha.id            AS application_id,
        ha.status        AS application_status,
        ha.availability_note,
        ha.snapshot_rating,
        ha.snapshot_jobs_done,
        $appliedSel      AS applied_at,
        hp.user_id       AS hacker_id,
        hp.handle,
        hp.display_name,
        hp.professional_title,
        hp.bio,
        hp.location,
        hp.years_experience,
        hp.avatar_url,
        COALESCE(vs.review_count, 0) AS review_count,
        COALESCE(vs.avg_rating,   0) AS avg_rating,
        (SELECT COUNT(*) FROM engagements e
          WHERE e.hacker_id = hp.user_id AND e.status = 'completed') AS jobs_done,
        (SELECT GROUP_CONCAT(DISTINCT hs.skill_code ORDER BY hs.skill_code SEPARATOR ',')
          FROM hacker_skills hs WHERE hs.hacker_id = hp.user_id) AS skills
    FROM hacker_applications ha
    JOIN hacker_profiles hp ON hp.user_id = ha.hacker_id
    LEFT JOIN v_hacker_review_stats vs ON vs.user_id = hp.user_id
    WHERE ha.request_id = ?
    ORDER BY (ha.status = 'pending') DESC, avg_rating DESC, $orderTs ASC"
);
$st->execute([$bountyId]);

/**
 * Return the name of the hacker_applications timestamp column that exists,
 * or null if none of the candidates are present.
 */
function application_ts_column(PDO $pdo): ?string {
    foreach (['applied_at', 'created_at', 'submitted_at'] as $c) {
        $chk = $pdo->prepare(
            "SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'hacker_applications'
               AND column_name = ? LIMIT 1"
        );
        $chk->execute([$c]);
        if ($chk->fetchColumn()) return $c;
    }
    return null;
}
$applicants = $st->fetchAll();

// Expand skills CSV → array
foreach ($applicants as &$a) {
    $a['skills'] = $a['skills'] ? explode(',', $a['skills']) : [];
}

json_ok(['applicants' => $applicants, 'bounty_status' => $bounty['status']]);
