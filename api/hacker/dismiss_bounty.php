<?php
/**
 * PTjo — Hacker: Pass / dismiss a bounty so it stays hidden from the job list.
 * POST /api/hacker/dismiss_bounty.php
 * Body (JSON): { "bounty_id": "<uuid>" }
 * Returns { ok }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body     = json_input();
$bountyId = trim($body['bounty_id'] ?? '');
if ($bountyId === '') json_error('bounty_id is required.');

$pdo = db();

// The bounty must exist (FK would reject otherwise, but a clean message is nicer).
$st = $pdo->prepare('SELECT id FROM bounty_requests WHERE id = ? LIMIT 1');
$st->execute([$bountyId]);
if (!$st->fetch()) json_error('Bounty not found.', 404);

$pdo->prepare(
    'INSERT INTO hacker_dismissed_bounties (hacker_id, bounty_id)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE dismissed_at = CURRENT_TIMESTAMP'
)->execute([$user['id'], $bountyId]);

audit('dismiss_bounty', 'bounty_requests', $bountyId);

json_ok(['message' => 'Bounty dismissed.']);
