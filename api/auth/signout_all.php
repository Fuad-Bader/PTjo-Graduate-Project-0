<?php
/**
 * PTjo — Sign out all OTHER devices.
 * POST /api/auth/signout_all.php
 * Revokes every active session for the user except the current one, which
 * stays logged in. Returns { ok, revoked: <count> }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();
csrf_verify();

// Ensure the current session is tracked so it is correctly excluded.
session_track($user['id']);

$pdo = db();
$st  = $pdo->prepare(
    'UPDATE user_sessions
     SET revoked_at = NOW()
     WHERE user_id = ? AND revoked_at IS NULL AND php_session_id <> ?'
);
$st->execute([$user['id'], session_id()]);

audit('signout_all_sessions', 'user_sessions', $user['id'], null, ['revoked' => $st->rowCount()]);

json_ok(['revoked' => $st->rowCount()]);
