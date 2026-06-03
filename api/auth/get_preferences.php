<?php
/**
 * PTjo — Get the current user's preferences (notification toggles, formats).
 * GET /api/auth/get_preferences.php
 * Returns { ok, preferences: { <key>: <value>, ... } } (defaults applied).
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();

// Defaults mirror the Settings UI's initial checkbox/select states.
$defaults = [
    'notif_new_job'    => true,
    'notif_engagement' => true,
    'notif_messages'   => true,
    'notif_security'   => true,
    'notif_marketing'  => false,
    'date_format'      => 'DD/MM/YYYY',
    'time_format'      => '12',
];

$stored = [];
try {
    $st = db()->prepare('SELECT prefs FROM user_preferences WHERE user_id = ? LIMIT 1');
    $st->execute([$user['id']]);
    $raw = $st->fetchColumn();
    if ($raw) {
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) $stored = $decoded;
    }
} catch (Throwable) { /* table missing → defaults only */ }

json_ok(['preferences' => array_merge($defaults, $stored)]);
