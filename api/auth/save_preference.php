<?php
/**
 * PTjo — Save a single user preference.
 * POST /api/auth/save_preference.php
 * Body (JSON): { "key": "notif_new_job", "value": true | "DD/MM/YYYY" | ... }
 * Keys are whitelisted; values are coerced to the expected type.
 * Returns { ok, preferences } — the full merged set after the update.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();
csrf_verify();

$body = json_input();
$key  = trim((string)($body['key'] ?? ''));
$val  = $body['value'] ?? null;

// Whitelist of allowed preference keys and how to coerce their value.
$boolKeys = ['notif_new_job', 'notif_engagement', 'notif_messages', 'notif_security', 'notif_marketing'];
$enumKeys = [
    'date_format' => ['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD'],
    'time_format' => ['12', '24'],
];

if (in_array($key, $boolKeys, true)) {
    $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
} elseif (isset($enumKeys[$key])) {
    $val = (string)$val;
    if (!in_array($val, $enumKeys[$key], true)) json_error('Invalid value for ' . $key . '.');
} else {
    json_error('Unknown preference key.');
}

$pdo = db();

// Read existing prefs, merge, write back. JSON column keeps it role-agnostic.
$st = $pdo->prepare('SELECT prefs FROM user_preferences WHERE user_id = ? LIMIT 1');
$st->execute([$user['id']]);
$raw     = $st->fetchColumn();
$current = [];
if ($raw) {
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded)) $current = $decoded;
}
$current[$key] = $val;

$pdo->prepare(
    'INSERT INTO user_preferences (user_id, prefs) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE prefs = VALUES(prefs)'
)->execute([$user['id'], json_encode($current)]);

audit('save_preference', 'user_preferences', $user['id'], null, [$key => $val]);

json_ok(['preferences' => $current]);
