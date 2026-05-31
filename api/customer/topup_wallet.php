<?php
/**
 * PTjo — Customer: Top up wallet (demo / internal)
 * POST /api/customer/topup_wallet.php
 * Body (JSON): { "amount": 100.00 }
 * In production this would integrate with a real payment gateway.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body   = json_input();
$amount = round((float)($body['amount'] ?? 0), 2);

if ($amount <= 0)       json_error('Amount must be greater than zero.');
if ($amount > 100000)   json_error('Amount exceeds maximum top-up limit.');

$pdo = db();
$wst = $pdo->prepare('SELECT id, balance FROM wallets WHERE user_id = ? AND type = "customer" LIMIT 1');
$wst->execute([$user['id']]);
$wallet = $wst->fetch();
if (!$wallet) json_error('Wallet not found.', 404);

$newBalance = round($wallet['balance'] + $amount, 2);

$pdo->beginTransaction();
$pdo->prepare('UPDATE wallets SET balance = ? WHERE id = ?')->execute([$newBalance, $wallet['id']]);
$pdo->prepare('
    INSERT INTO wallet_ledger_entries
    (wallet_id, amount, balance_after, entry_type, description, metadata)
    VALUES (?, ?, ?, "topup", ?, "{}")
')->execute([$wallet['id'], $amount, $newBalance, 'Manual top-up of $' . number_format($amount, 2)]);
$pdo->commit();

audit('topup_wallet', 'wallets', $wallet['id'], ['balance' => $wallet['balance']], ['balance' => $newBalance]);

json_ok(['balance' => $newBalance, 'amount_added' => $amount]);
