<?php
/**
 * PTjo — Customer: View a pentester's public profile + reviews.
 * GET /api/customer/get_hacker_public.php?hacker_id=<uuid>
 *  or  /api/customer/get_hacker_public.php?engagement_id=<uuid>
 *
 * When called with engagement_id, the hacker is resolved from an engagement the
 * customer owns, and `already_reviewed` reflects that specific engagement.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
$pdo  = db();

$engagementId = input('engagement_id');
$hackerId     = input('hacker_id');

if ($engagementId !== '') {
    // Resolve the hacker via an engagement this customer owns.
    $st = $pdo->prepare('SELECT hacker_id FROM engagements WHERE id = ? AND customer_id = ? LIMIT 1');
    $st->execute([$engagementId, $user['id']]);
    $hackerId = (string)$st->fetchColumn();
}
if ($hackerId === '') json_error('hacker_id or engagement_id is required.');

// Public profile fields only.
$st = $pdo->prepare(
    'SELECT hp.user_id, hp.handle, hp.display_name, hp.professional_title, hp.bio,
            hp.location, hp.years_experience, hp.avatar_url,
            hp.github_url, hp.linkedin_url, hp.portfolio_url
     FROM hacker_profiles hp WHERE hp.user_id = ? LIMIT 1'
);
$st->execute([$hackerId]);
$profile = $st->fetch();
if (!$profile) json_error('Pentester not found.', 404);

// Skills
$sk = $pdo->prepare('SELECT skill_code FROM hacker_skills WHERE hacker_id = ?');
$sk->execute([$hackerId]);
$profile['skills'] = array_column($sk->fetchAll(), 'skill_code');

// Completed jobs
$jc = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE hacker_id = ? AND status = 'completed'");
$jc->execute([$hackerId]);
$profile['jobs_done'] = (int)$jc->fetchColumn();

// Review aggregates
$rs = $pdo->prepare(
    'SELECT COUNT(*) AS c, ROUND(AVG(rating), 2) AS a, ROUND(AVG(recommended) * 100) AS rec
     FROM reviews WHERE hacker_id = ?'
);
$rs->execute([$hackerId]);
$row = $rs->fetch() ?: [];
$profile['review_count']  = (int)($row['c'] ?? 0);
$profile['avg_rating']    = (float)($row['a'] ?? 0);
$profile['recommend_pct'] = (int)($row['rec'] ?? 0);

// Public review cards
$rv = $pdo->prepare(
    'SELECT public_id, client_display_name, client_company, rating, recommended,
            comment, service_label, vuln_title, severity_label, verified, created_at
     FROM reviews WHERE hacker_id = ? ORDER BY created_at DESC LIMIT 100'
);
$rv->execute([$hackerId]);
$reviews = $rv->fetchAll();

// Has this customer already reviewed the supplied engagement?
$alreadyReviewed = false;
if ($engagementId !== '') {
    $ar = $pdo->prepare('SELECT 1 FROM reviews WHERE engagement_id = ? LIMIT 1');
    $ar->execute([$engagementId]);
    $alreadyReviewed = (bool)$ar->fetchColumn();
}

json_ok([
    'profile'          => $profile,
    'reviews'          => $reviews,
    'already_reviewed' => $alreadyReviewed,
]);
