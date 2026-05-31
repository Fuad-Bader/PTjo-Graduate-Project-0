<?php
/**
 * PTjo — Hacker: Get own full profile
 * GET /api/hacker/get_profile.php
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
$pdo  = db();

$st = $pdo->prepare('
    SELECT hp.*,
           w.balance AS wallet_balance,
           w.id      AS wallet_id
    FROM hacker_profiles hp
    LEFT JOIN wallets w ON w.user_id = hp.user_id AND w.type = "hacker"
    WHERE hp.user_id = ?
');
$st->execute([$user['id']]);
$profile = $st->fetch();
if (!$profile) json_error('Profile not found.', 404);

// Skills
$st = $pdo->prepare('SELECT skill_code FROM hacker_skills WHERE hacker_id = ?');
$st->execute([$user['id']]);
$profile['skills'] = array_column($st->fetchAll(), 'skill_code');

// Tools
$st = $pdo->prepare('SELECT tool_name FROM hacker_tools WHERE hacker_id = ?');
$st->execute([$user['id']]);
$profile['tools'] = array_column($st->fetchAll(), 'tool_name');

// Languages
$st = $pdo->prepare('SELECT language FROM hacker_languages WHERE hacker_id = ?');
$st->execute([$user['id']]);
$profile['languages'] = array_column($st->fetchAll(), 'language');

// Certifications
$st = $pdo->prepare('SELECT * FROM certifications WHERE hacker_id = ? ORDER BY issued_on DESC');
$st->execute([$user['id']]);
$profile['certifications'] = $st->fetchAll();

// Review stats
$st = $pdo->prepare('SELECT review_count, avg_rating FROM v_hacker_review_stats WHERE user_id = ?');
$st->execute([$user['id']]);
$stats = $st->fetch();
$profile['review_count'] = (int)($stats['review_count'] ?? 0);
$profile['avg_rating']   = (float)($stats['avg_rating'] ?? 0);

// Completed jobs count
$st = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE hacker_id = ? AND status = 'completed'");
$st->execute([$user['id']]);
$profile['jobs_done'] = (int)$st->fetchColumn();

json_ok(['profile' => $profile, 'user' => $user]);
