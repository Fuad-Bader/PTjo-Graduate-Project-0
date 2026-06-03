<?php
/**
 * PTjo — Revoke one of the current user's login sessions.
 * POST /api/auth/revoke_session.php
 * Body (JSON): { "session_id": "<user_sessions.id>" }
 * The next request made by that device is logged out (see session_enforce()).
 * Returns { ok, current_revoked } — current_revoked=true means the caller
 * revoked their own session and should be redirected to login.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();
csrf_verify();

$body = json_input();
$id   = trim($body['session_id'] ?? '');
if ($id === '') json_error('session_id is required.');

$pdo = db();
$st  = $pdo->prepare('SELECT php_session_id FROM user_sessions WHERE id = ? AND user_id = ? AND revoked_at IS NULL LIMIT 1');
$st->execute([$id, $user['id']]);
$row = $st->fetch();
if (!$row) json_error('Session not found.', 404);

$pdo->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE id = ?')->execute([$id]);
audit('revoke_session', 'user_sessions', $id);

$isCurrent = hash_equals((string)$row['php_session_id'], (string)session_id());
json_ok(['current_revoked' => $isCurrent]);
