<?php
/**
 * PTjo — Login handler
 * Handles POST from LogIn/Login.html
 * Redirects to the appropriate dashboard on success,
 * or back to Login.html with an error query-param on failure.
 */

require_once __DIR__ . '/../config/auth.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.html');
    exit;
}

$email    = filter_var(input('email'), FILTER_SANITIZE_EMAIL);
$password = input('password');

// ── Basic validation ──────────────────────────────────────────────────────────
if (!$email || !$password) {
    redirect_error('Email and password are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error('Invalid email address.');
}

// ── Look up user ──────────────────────────────────────────────────────────────
$pdo = db();
$st  = $pdo->prepare(
    'SELECT id, email, password_hash, role, is_active
     FROM users
     WHERE email = ?
     LIMIT 1'
);
$st->execute([$email]);
$user = $st->fetch();

// Constant-time comparison to avoid timing attacks
if (!$user || !password_verify($password, $user['password_hash'])) {
    redirect_error('Invalid email or password.');
}

if (!(bool)$user['is_active']) {
    redirect_error('Your account has been suspended. Contact support.');
}

// ── Update last_login_at ──────────────────────────────────────────────────────
$pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
    ->execute([$user['id']]);

// ── Build session ─────────────────────────────────────────────────────────────
session_regenerate_id(true);   // prevent session fixation

$_SESSION['user'] = [
    'id'    => $user['id'],
    'email' => $user['email'],
    'role'  => $user['role'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

audit('login', 'users', $user['id']);

// ── Redirect to dashboard ─────────────────────────────────────────────────────
$base = '../';
match ($user['role']) {
    'hacker' => header('Location: ' . $base . 'Hacker_Dashboard/HackerDashboard.html'),
    'admin'  => header('Location: ' . $base . 'index.html'),
    default  => header('Location: ' . $base . 'Customer_Dashboard/Customer_Dashboard.html'),
};
exit;

// ── Helper ────────────────────────────────────────────────────────────────────
function redirect_error(string $msg): never
{
    header('Location: Login.html?error=' . urlencode($msg));
    exit;
}
