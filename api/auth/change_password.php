<?php
/**
 * PTjo — Change the current user's password.
 * POST /api/auth/change_password.php
 * Body (JSON): { "current_password": "...", "new_password": "..." }
 * Verifies the current password, stores a new hash, and rotates the session id.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();
csrf_verify();

$body    = json_input();
$current = (string)($body['current_password'] ?? '');
$new     = (string)($body['new_password'] ?? '');

if ($current === '' || $new === '') json_error('Current and new password are required.');
if (strlen($new) < 8)               json_error('New password must be at least 8 characters.');
if ($new === $current)              json_error('New password must be different from the current one.');

$pdo = db();
$st  = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
$st->execute([$user['id']]);
$hash = (string)$st->fetchColumn();

if ($hash === '' || !password_verify($current, $hash)) {
    json_error('Current password is incorrect.', 403);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $user['id']]);

// Rotate the session id so a stolen pre-change cookie can't be reused, and
// move the session-tracking row to the new id so the Active Sessions list
// keeps showing the current device (not a phantom).
$oldSid = session_id();
session_regenerate_id(true);
session_rekey($oldSid, session_id());

audit('change_password', 'users', $user['id']);

json_ok(['message' => 'Password updated.']);
