<?php
/**
 * PTjo — Session bootstrap & auth helpers
 * Include this at the top of every page/API that needs auth.
 */

require_once __DIR__ . '/db.php';

// ── Secure session configuration ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    // Set cookie_secure to 1 in production (HTTPS only)
    // ini_set('session.cookie_secure', '1');
    session_start();
}

// ── Security headers ───────────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ── CSRF helpers ───────────────────────────────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token']
          ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals((string)csrf_token(), (string)$token)) {
        json_error('Invalid or missing CSRF token.', 403);
    }
}

// ── Auth guards ────────────────────────────────────────────────────────────────
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        json_error('Unauthenticated. Please log in.', 401);
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_auth();
    if (!in_array($user['role'], $roles, true)) {
        json_error('Forbidden.', 403);
    }
    return $user;
}

// ── JSON response helpers ──────────────────────────────────────────────────────
function json_ok(array $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

function json_error(string $message, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

// ── Input helpers ──────────────────────────────────────────────────────────────
function input(string $key, string $default = ''): string
{
    $val = $_POST[$key] ?? $_GET[$key] ?? $default;
    return trim((string)$val);
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ── Audit log helper ───────────────────────────────────────────────────────────
function audit(string $action, string $table, ?string $entityId, ?array $old = null, ?array $new = null): void
{
    $user = current_user();
    $actorId = $user['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    try {
        $st = db()->prepare(
            'INSERT INTO audit_log
             (actor_user_id, action, entity_table, entity_id, old_data, new_data, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $actorId, $action, $table, $entityId,
            $old ? json_encode($old) : null,
            $new  ? json_encode($new)  : null,
            $ip, $ua,
        ]);
    } catch (Throwable) {
        // Audit failure must never break the main flow
    }
}

// ── Notification helper ────────────────────────────────────────────────────────
function notify(string $userId, string $type, string $title, string $body, array $payload = []): void
{
    try {
        $st = db()->prepare(
            'INSERT INTO notifications (user_id, channel, type, title, body, payload)
             VALUES (?, "in_app", ?, ?, ?, ?)'
        );
        $st->execute([$userId, $type, $title, $body, json_encode($payload)]);
    } catch (Throwable) {}
}
