<?php
/**
 * PTjo — Customer: Cancel a bounty request
 * POST /api/customer/cancel_bounty.php
 * Body (JSON): { "bounty_id": "<uuid>" }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body     = json_input();
$bountyId = trim($body['bounty_id'] ?? '');
if (!$bountyId) json_error('bounty_id is required.');

$pdo = db();
$st  = $pdo->prepare('SELECT id, status FROM bounty_requests WHERE id = ? AND customer_id = ? LIMIT 1');
$st->execute([$bountyId, $user['id']]);
$bounty = $st->fetch();

if (!$bounty)                        json_error('Bounty not found.', 404);
if ($bounty['status'] !== 'open')   json_error('Only open bounties can be cancelled.');

$pdo->prepare('UPDATE bounty_requests SET status = "cancelled" WHERE id = ?')
    ->execute([$bountyId]);

audit('cancel_bounty', 'bounty_requests', $bountyId, ['status' => 'open'], ['status' => 'cancelled']);

json_ok(['message' => 'Bounty cancelled.']);
