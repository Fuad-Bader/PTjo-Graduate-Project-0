<?php
/**
 * PTjo — Customer: Leave a review for a completed engagement.
 * POST /api/customer/submit_review.php
 * Body (JSON): { "engagement_id": "<uuid>", "rating": 1..5,
 *                "recommended": true|false, "comment": "<optional>" }
 *
 * One review per engagement. The review is public (other customers see it on
 * the pentester's profile) and drives the hacker's average rating.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body         = json_input();
$engagementId = trim($body['engagement_id'] ?? '');
$rating       = (int)($body['rating'] ?? 0);
$recommended  = !empty($body['recommended']) ? 1 : 0;
$comment      = trim((string)($body['comment'] ?? ''));

if ($engagementId === '')        json_error('engagement_id is required.');
if ($rating < 1 || $rating > 5)  json_error('Rating must be between 1 and 5.');
if (mb_strlen($comment) > 2000)  $comment = mb_substr($comment, 0, 2000);

$pdo = db();

// The engagement must belong to this customer and be completed.
$st = $pdo->prepare(
    'SELECT id, hacker_id, service_type
     FROM engagements
     WHERE id = ? AND customer_id = ? AND status = "completed" LIMIT 1'
);
$st->execute([$engagementId, $user['id']]);
$eng = $st->fetch();
if (!$eng) json_error('You can only review your own completed engagements.', 404);

// One review per engagement.
$dup = $pdo->prepare('SELECT id FROM reviews WHERE engagement_id = ? LIMIT 1');
$dup->execute([$engagementId]);
if ($dup->fetchColumn()) json_error('You have already reviewed this engagement.');

// Customer display name / company shown on the public review card.
$cst = $pdo->prepare('SELECT display_name, company_name FROM customer_profiles WHERE user_id = ? LIMIT 1');
$cst->execute([$user['id']]);
$cust          = $cst->fetch() ?: [];
$clientName    = ($cust['display_name'] ?? '') !== '' ? $cust['display_name'] : 'Customer';
$clientCompany = $cust['company_name'] ?? null;

$publicId = 'REV-' . strtoupper(bin2hex(random_bytes(4)));

try {
    $pdo->prepare(
        'INSERT INTO reviews
            (public_id, report_id, engagement_id, hacker_id, customer_id,
             client_display_name, client_company, rating, recommended, comment,
             service_label, verified)
         VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
    )->execute([
        $publicId, $engagementId, $eng['hacker_id'], $user['id'],
        $clientName, $clientCompany, $rating, $recommended,
        ($comment === '' ? null : $comment), $eng['service_type'],
    ]);
} catch (Throwable $e) {
    // Unique key race → already reviewed.
    json_error('You have already reviewed this engagement.');
}

notify($eng['hacker_id'], 'review_received', 'New Review',
    $clientName . ' left you a ' . $rating . '★ review.',
    ['engagement_id' => $engagementId]);

audit('submit_review', 'reviews', $engagementId, null,
    ['rating' => $rating, 'recommended' => $recommended]);

json_ok(['message' => 'Review submitted.', 'public_id' => $publicId]);
