<?php
/**
 * PTjo — Customer: Update profile
 * POST /api/customer/update_profile.php
 * Body (JSON): { "display_name": "...", "company_name": "...",
 *               "phone_e164": "...", "avatar_url": "..." }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body        = json_input();
$displayName = substr(trim($body['display_name'] ?? ''), 0, 255);
$company     = substr(trim($body['company_name'] ?? ''), 0, 255);
$phone       = substr(trim($body['phone_e164']   ?? ''), 0, 30);
$avatar      = substr(trim($body['avatar_url']   ?? ''), 0, 500);

if (!$displayName) json_error('display_name is required.');

$pdo = db();
$old = $pdo->prepare('SELECT * FROM customer_profiles WHERE user_id = ?');
$old->execute([$user['id']]);
$oldData = $old->fetch();

$pdo->prepare('
    UPDATE customer_profiles
    SET display_name=?, company_name=?, phone_e164=?, avatar_url=?
    WHERE user_id=?
')->execute([$displayName, $company ?: null, $phone ?: null, $avatar ?: null, $user['id']]);

audit('update_profile', 'customer_profiles', $user['id'], $oldData,
    ['display_name' => $displayName, 'company_name' => $company]);

json_ok(['message' => 'Profile updated.']);
