<?php
/**
 * PTjo — Customer: Get own profile
 * GET /api/customer/get_profile.php
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
$pdo  = db();

$st = $pdo->prepare('
    SELECT cp.*,
           w.balance AS wallet_balance,
           w.id      AS wallet_id
    FROM customer_profiles cp
    LEFT JOIN wallets w ON w.user_id = cp.user_id AND w.type = "customer"
    WHERE cp.user_id = ?
');
$st->execute([$user['id']]);
$profile = $st->fetch();
if (!$profile) json_error('Profile not found.', 404);

// Payment methods (no sensitive data — last4 only)
$st = $pdo->prepare('SELECT id, brand, last4, exp_month, exp_year, cardholder_name, label, is_default FROM payment_methods WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC');
$st->execute([$user['id']]);
$profile['payment_methods'] = $st->fetchAll();

// Engagement stats
$st = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE customer_id = ? AND status = 'completed'");
$st->execute([$user['id']]);
$profile['completed_engagements'] = (int)$st->fetchColumn();

json_ok(['profile' => $profile, 'user' => $user]);
