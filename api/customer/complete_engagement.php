<?php
/**
 * PTjo — Customer: Close an engagement as SUCCESSFUL and pay the hacker.
 * POST /api/customer/complete_engagement.php
 * Body (JSON): { "engagement_id": "<uuid>", "payment_method": "wallet" | "card" }
 *
 * Pays the agreed price to the hacker's wallet. "wallet" debits the customer's
 * wallet (must have sufficient balance); "card" is a simulated external charge
 * (no wallet debit) — mirroring the existing report-payment behaviour.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body         = json_input();
$engagementId = trim($body['engagement_id'] ?? '');
$method       = trim($body['payment_method'] ?? 'wallet');
if (!$engagementId) json_error('engagement_id is required.');
if (!in_array($method, ['wallet', 'card'], true)) $method = 'wallet';

$pdo = db();
$st  = $pdo->prepare(
    'SELECT id, customer_id, hacker_id, status, agreed_price_usd, bounty_request_id, service_type
     FROM engagements WHERE id = ? AND customer_id = ? LIMIT 1'
);
$st->execute([$engagementId, $user['id']]);
$eng = $st->fetch();

if (!$eng) json_error('Engagement not found.', 404);
if (in_array($eng['status'], ['completed', 'cancelled', 'declined'], true)) {
    json_error('This engagement is already closed.');
}

$amount = (float)$eng['agreed_price_usd'];

try {
    $pdo->beginTransaction();

    if ($method === 'wallet') {
        // Make sure the customer has a wallet row, then check funds.
        $pdo->prepare("INSERT IGNORE INTO wallets (user_id, type, currency, balance) VALUES (?, 'customer', 'USD', 0)")
            ->execute([$user['id']]);
        $cst = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND type = 'customer' FOR UPDATE");
        $cst->execute([$user['id']]);
        $balance = (float)$cst->fetchColumn();
        if ($balance < $amount) {
            $pdo->rollBack();
            json_error('Insufficient wallet balance. Please top up or pay by card.');
        }
        $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND type = 'customer'")
            ->execute([$amount, $user['id']]);
    }
    // "card" → simulated external charge; nothing to debit locally.

    // Credit the hacker's wallet.
    $pdo->prepare("INSERT IGNORE INTO wallets (user_id, type, currency, balance) VALUES (?, 'hacker', 'USD', 0)")
        ->execute([$eng['hacker_id']]);
    $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND type = 'hacker'")
        ->execute([$amount, $eng['hacker_id']]);

    // Close out engagement + bounty.
    $pdo->prepare("UPDATE engagements SET status = 'completed' WHERE id = ?")->execute([$engagementId]);
    if ($eng['bounty_request_id']) {
        $pdo->prepare("UPDATE bounty_requests SET status = 'completed' WHERE id = ?")
            ->execute([$eng['bounty_request_id']]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('complete_engagement: ' . $e->getMessage());
    json_error('Could not complete payment. Please try again.');
}

notify($eng['hacker_id'], 'engagement_completed', 'Engagement Completed & Paid',
    'Your engagement was marked complete and $' . number_format($amount, 2) . ' was credited to your wallet.',
    ['engagement_id' => $engagementId]);

audit('complete_engagement', 'engagements', $engagementId,
    ['status' => $eng['status']],
    ['status' => 'completed', 'amount' => $amount, 'method' => $method]);

json_ok(['message' => 'Engagement completed.', 'amount' => $amount]);
