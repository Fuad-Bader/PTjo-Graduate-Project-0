<?php
/**
 * PTjo — Session check
 * GET /api/auth/session.php
 * Returns the current user info (id, email, role) or 401.
 * Used by dashboards to verify the user is still logged in.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = current_user();
if (!$user) {
    json_error('Not authenticated.', 401);
}

// Fetch fresh profile data depending on role
$pdo = db();
$profile = [];

if ($user['role'] === 'customer') {
    $st = $pdo->prepare(
        'SELECT cp.display_name, cp.company_name, cp.avatar_url,
                w.balance AS wallet_balance
         FROM customer_profiles cp
         LEFT JOIN wallets w ON w.user_id = cp.user_id AND w.type = "customer"
         WHERE cp.user_id = ?'
    );
    $st->execute([$user['id']]);
    $profile = $st->fetch() ?: [];
} elseif ($user['role'] === 'hacker') {
    $st = $pdo->prepare(
        'SELECT hp.handle, hp.display_name, hp.avatar_url, hp.professional_title,
                w.balance AS wallet_balance,
                (SELECT COUNT(*) FROM engagements e
                   WHERE e.hacker_id = hp.user_id AND e.status = "completed") AS jobs_done,
                (SELECT ROUND(AVG(r.rating), 2) FROM reviews r
                   WHERE r.hacker_id = hp.user_id) AS avg_rating,
                (SELECT COUNT(*) FROM reviews r
                   WHERE r.hacker_id = hp.user_id) AS review_count
         FROM hacker_profiles hp
         LEFT JOIN wallets w ON w.user_id = hp.user_id AND w.type = "hacker"
         WHERE hp.user_id = ?'
    );
    $st->execute([$user['id']]);
    $profile = $st->fetch() ?: [];
}

json_ok([
    'user'    => $user,
    'profile' => $profile,
    'csrf'    => csrf_token(),
]);
