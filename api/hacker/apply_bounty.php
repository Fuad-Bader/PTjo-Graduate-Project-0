<?php
/**
 * PTjo — Hacker: Apply to a bounty request
 * POST /api/hacker/apply_bounty.php
 * Body (JSON): { "bounty_id": "<uuid>", "availability_note": "..." }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body    = json_input();
$bountyId = trim($body['bounty_id'] ?? '');
$note     = substr(trim($body['availability_note'] ?? ''), 0, 1000);

if (!$bountyId) json_error('bounty_id is required.');

$pdo = db();

// Verify bounty is open
$st = $pdo->prepare("SELECT * FROM bounty_requests WHERE id = ? AND status = 'open' LIMIT 1");
$st->execute([$bountyId]);
$bounty = $st->fetch();
if (!$bounty) json_error('Bounty not found or no longer open.', 404);

// Check not already applied
$st = $pdo->prepare('SELECT id FROM hacker_applications WHERE request_id = ? AND hacker_id = ? LIMIT 1');
$st->execute([$bountyId, $user['id']]);
if ($st->fetch()) json_error('You have already applied to this bounty.');

// Snapshot stats for the application
$st = $pdo->prepare('SELECT review_count, avg_rating FROM v_hacker_review_stats WHERE user_id = ?');
$st->execute([$user['id']]);
$stats = $st->fetch();

$st2 = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE hacker_id = ? AND status = 'completed'");
$st2->execute([$user['id']]);
$jobsDone = (int)$st2->fetchColumn();

$st3 = $pdo->prepare('SELECT bio FROM hacker_profiles WHERE user_id = ?');
$st3->execute([$user['id']]);
$hp = $st3->fetch();

$pdo->prepare('
    INSERT INTO hacker_applications
    (request_id, hacker_id, availability_note, snapshot_rating, snapshot_jobs_done, snapshot_bio)
    VALUES (?, ?, ?, ?, ?, ?)
')->execute([
    $bountyId, $user['id'], $note ?: null,
    $stats['avg_rating']   ?? null,
    $jobsDone,
    $hp['bio'] ?? null,
]);

$appId = $pdo->query('SELECT id FROM hacker_applications WHERE request_id = ' . $pdo->quote($bountyId) . ' AND hacker_id = ' . $pdo->quote($user['id']) . ' LIMIT 1')->fetchColumn();

// Notify the customer
notify($bounty['customer_id'], 'new_application', 'New Pentester Application',
    'A pentester has applied to your bounty request.',
    ['bounty_id' => $bountyId, 'application_id' => $appId, 'public_id' => $bounty['public_id']]);

audit('apply_bounty', 'hacker_applications', $appId, null, ['bounty_id' => $bountyId]);

json_ok(['message' => 'Application submitted.', 'application_id' => $appId], 201);
