<?php
/**
 * PTjo — Hacker signup handler
 * Handles POST from SignUp_Hacker/SignUpH.html
 */

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: SignUpH.html');
    exit;
}

// ── Read & sanitise inputs ────────────────────────────────────────────────────
$fullName    = substr(trim(input('full_name')), 0, 255);
$phone       = substr(trim(input('phone_number')), 0, 30);
$email       = filter_var(trim(input('email')), FILTER_SANITIZE_EMAIL);
$password    = input('password');
$confirm     = input('confirm_password');
$serviceType = substr(trim(input('service_type')), 0, 64); // primary skill chosen during signup

// ── Validation ────────────────────────────────────────────────────────────────
$errors = [];

if (empty($fullName))                               $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Invalid email address.';
if (strlen($password) < 8)                          $errors[] = 'Password must be at least 8 characters.';
if (!preg_match('/[A-Z]/', $password))              $errors[] = 'Password must contain an uppercase letter.';
if (!preg_match('/[a-z]/', $password))              $errors[] = 'Password must contain a lowercase letter.';
if (!preg_match('/[0-9]/', $password))              $errors[] = 'Password must contain a digit.';
if (!preg_match('/[^A-Za-z0-9]/', $password))      $errors[] = 'Password must contain a special character.';
if ($password !== $confirm)                         $errors[] = 'Passwords do not match.';

if ($errors) {
    redirect_error(implode(' ', $errors));
}

$pdo = db();

// ── Duplicate email check ─────────────────────────────────────────────────────
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
if ($st->fetch()) {
    redirect_error('An account with that email already exists.');
}

// ── Generate unique handle ────────────────────────────────────────────────────
$baseHandle = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $fullName)));
$baseHandle = substr($baseHandle ?: 'hacker', 0, 60);
$handle     = $baseHandle;
$attempt    = 0;
while (true) {
    $st = $pdo->prepare('SELECT user_id FROM hacker_profiles WHERE handle = ? LIMIT 1');
    $st->execute([$handle]);
    if (!$st->fetch()) break;
    $handle = $baseHandle . '_' . (++$attempt);
}

// ── Insert user + profile + wallet ───────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO users (email, password_hash, role) VALUES (?, ?, "hacker")'
    )->execute([$email, $hash]);

    $userId = $pdo->query('SELECT id FROM users WHERE email = ' . $pdo->quote($email) . ' LIMIT 1')->fetchColumn();

    $pdo->prepare(
        'INSERT INTO hacker_profiles (user_id, handle, display_name, phone_e164, public_slug)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $handle, $fullName, $phone ?: null, $handle]);

    // wallet
    $pdo->prepare(
        'INSERT INTO wallets (user_id, type) VALUES (?, "hacker")'
    )->execute([$userId]);

    // Store initial skill if provided
    $allowedSkills = ['webapp','mobile','api','iot','network','cloud','ad'];
    if ($serviceType && in_array($serviceType, $allowedSkills, true)) {
        $pdo->prepare('INSERT IGNORE INTO hacker_skills (hacker_id, skill_code) VALUES (?, ?)')
            ->execute([$userId, $serviceType]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Hacker signup failed: ' . $e->getMessage());
    redirect_error('Registration failed. Please try again.');
}

audit('register', 'users', $userId, null, ['email' => $email, 'role' => 'hacker']);

header('Location: ../LogIn/Login.html?signup=success');
exit;

function redirect_error(string $msg): never
{
    header('Location: SignUpH.html?error=' . urlencode($msg));
    exit;
}
