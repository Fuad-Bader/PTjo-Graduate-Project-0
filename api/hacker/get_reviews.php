<?php
/**
 * PTjo — Hacker: Get reviews left about me.
 * GET /api/hacker/get_reviews.php
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
$pdo  = db();

$rv = $pdo->prepare(
    'SELECT public_id, client_display_name, client_company, rating, recommended,
            comment, service_label, vuln_title, severity_label, verified, created_at
     FROM reviews WHERE hacker_id = ? ORDER BY created_at DESC LIMIT 200'
);
$rv->execute([$user['id']]);
$reviews = $rv->fetchAll();

$agg = $pdo->prepare(
    'SELECT COUNT(*) AS c, ROUND(AVG(rating), 2) AS a, ROUND(AVG(recommended) * 100) AS rec
     FROM reviews WHERE hacker_id = ?'
);
$agg->execute([$user['id']]);
$row = $agg->fetch() ?: [];

json_ok([
    'reviews'       => $reviews,
    'review_count'  => (int)($row['c'] ?? 0),
    'avg_rating'    => (float)($row['a'] ?? 0),
    'recommend_pct' => (int)($row['rec'] ?? 0),
]);
