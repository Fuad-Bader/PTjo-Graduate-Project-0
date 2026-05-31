<?php
/**
 * PTjo — Hacker: Get wallet balance + ledger
 * GET /api/hacker/get_wallet.php
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
$pdo  = db();

$wst = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ? AND type = "hacker" LIMIT 1');
$wst->execute([$user['id']]);
$wallet = $wst->fetch();

if (!$wallet) json_error('Wallet not found.', 404);

$lst = $pdo->prepare(
    'SELECT * FROM wallet_ledger_entries WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 30'
);
$lst->execute([$wallet['id']]);
$ledger = $lst->fetchAll();

json_ok(['wallet' => $wallet, 'ledger' => $ledger]);
