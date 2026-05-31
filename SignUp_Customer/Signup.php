<?php
/**
 * PTjo — Customer signup handler
 * Handles POST from SignUp_Customer/SignupC.html
 */

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: SignupC.html');
    exit;
}

// ── Read & sanitise inputs ────────────────────────────────────────────────────
$fullName    = substr(trim(input('full_name')), 0, 255);
$phone       = substr(trim(input('phone_number')), 0, 30);
$email       = filter_var(trim(input('email')), FILTER_SANITIZE_EMAIL);
$company     = substr(trim(input('company_name')), 0, 255);
$password    = input('password');
$confirm     = input('confirm_password');

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

// ── Check for duplicate email ─────────────────────────────────────────────────
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
if ($st->fetch()) {
    redirect_error('An account with that email already exists.');
}

// ── Insert user + profile + wallet ───────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo->beginTransaction();

    // users row
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, role) VALUES (?, ?, "customer")'
    )->execute([$email, $hash]);

    // MySQL UUID() isn't returned by lastInsertId for CHAR(36) PKs, so we fetch it
    $userId = $pdo->query('SELECT id FROM users WHERE email = ' . $pdo->quote($email) . ' LIMIT 1')->fetchColumn();

    // customer_profiles row
    $pdo->prepare(
        'INSERT INTO customer_profiles (user_id, display_name, company_name, phone_e164)
         VALUES (?, ?, ?, ?)'
    )->execute([$userId, $fullName, $company ?: null, $phone ?: null]);

    // wallet
    $pdo->prepare(
        'INSERT INTO wallets (user_id, type) VALUES (?, "customer")'
    )->execute([$userId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Customer signup failed: ' . $e->getMessage());
    redirect_error('Registration failed. Please try again.');
}

audit('register', 'users', $userId, null, ['email' => $email, 'role' => 'customer']);

// ── Success: redirect to login ────────────────────────────────────────────────
header('Location: ../LogIn/Login.html?signup=success');
exit;

function redirect_error(string $msg): never
{
    header('Location: SignupC.html?error=' . urlencode($msg));
    exit;
}
