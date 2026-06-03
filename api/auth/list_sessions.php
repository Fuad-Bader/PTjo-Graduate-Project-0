<?php
/**
 * PTjo — List the current user's active (non-revoked) login sessions.
 * GET /api/auth/list_sessions.php
 * Returns { ok, sessions: [ { id, device, ip, last_seen, current } ] }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_auth();

// Make sure the current session is recorded before we list.
session_track($user['id']);

$pdo = db();
$st  = $pdo->prepare(
    'SELECT id, php_session_id, ip_address, user_agent, last_seen_at, created_at
     FROM user_sessions
     WHERE user_id = ? AND revoked_at IS NULL
     ORDER BY last_seen_at DESC'
);
$st->execute([$user['id']]);
$rows = $st->fetchAll();

/** Best-effort "OS — Browser" label from a User-Agent string. */
function ua_label(?string $ua): string
{
    $ua = (string)$ua;
    if ($ua === '') return 'Unknown device';

    $os = 'Unknown OS';
    if (preg_match('/Windows NT/i', $ua))                 $os = 'Windows';
    elseif (preg_match('/iPhone|iPad|iPod/i', $ua))       $os = 'iOS';
    elseif (preg_match('/Mac OS X|Macintosh/i', $ua))     $os = 'macOS';
    elseif (preg_match('/Android/i', $ua))                $os = 'Android';
    elseif (preg_match('/Linux/i', $ua))                  $os = 'Linux';

    $browser = 'Browser';
    if (preg_match('/Edg/i', $ua))                                   $browser = 'Edge';
    elseif (preg_match('/OPR|Opera/i', $ua))                         $browser = 'Opera';
    elseif (preg_match('/Chrome/i', $ua) && !preg_match('/Edg/i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Firefox/i', $ua))                           $browser = 'Firefox';
    elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = 'Safari';

    return $os . ' — ' . $browser;
}

$currentSid = session_id();
$sessions = array_map(function ($r) use ($currentSid) {
    return [
        'id'        => $r['id'],
        'device'    => ua_label($r['user_agent']),
        'ip'        => $r['ip_address'] ?: 'Unknown IP',
        'last_seen' => $r['last_seen_at'],
        'current'   => hash_equals((string)$r['php_session_id'], (string)$currentSid),
    ];
}, $rows);

json_ok(['sessions' => $sessions]);
