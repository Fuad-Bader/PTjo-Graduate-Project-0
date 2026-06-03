<?php
/**
 * PTjo — Logout
 * POST /api/auth/logout.php
 * Destroys the session and redirects to the login page.
 */

require_once __DIR__ . '/../../config/auth.php';

// Mark this session revoked so it drops out of the Active Sessions list.
try {
    db()->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE php_session_id = ?')
        ->execute([session_id()]);
} catch (Throwable) {}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Accept both AJAX (returns JSON) and regular page redirects
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
    json_ok(['redirect' => '../../LogIn/Login.html']);
}

header('Location: ../../LogIn/Login.html');
exit;
