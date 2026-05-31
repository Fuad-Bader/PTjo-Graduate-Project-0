<?php
/**
 * PTjo — Customer: Direct-hire a hacker for a bounty
 * POST /api/customer/direct_hire.php
 * Body (JSON): { "bounty_id": "<uuid>", "hacker_id": "<uuid>" }
 *
 * Creates a hacker_application (if none exists) + engagement atomically.
 * Allows the customer to hire from the "Browse Pentesters" list
 * even before the hacker formally applied.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body     = json_input();
$bountyId = trim($body['bounty_id'] ?? '');
$hackerId = trim($body['hacker_id'] ?? '');

if (!$bountyId) json_error('bounty_id is required.');
if (!$hackerId) json_error('hacker_id is required.');

$pdo = db();

// Verify bounty belongs to this customer and is open
$st = $pdo->prepare("
    SELECT br.*, cp.display_name AS customer_display
    FROM bounty_requests br
    LEFT JOIN customer_profiles cp ON cp.user_id = br.customer_id
    WHERE br.id = ? AND br.customer_id = ? AND br.status = 'open'
    LIMIT 1
");
$st->execute([$bountyId, $user['id']]);
$bounty = $st->fetch();
if (!$bounty) json_error('Bounty not found or no longer open.', 404);

// Verify hacker exists and is active
$st = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'hacker' AND is_active = 1 LIMIT 1");
$st->execute([$hackerId]);
if (!$st->fetch()) json_error('Hacker not found.', 404);

// Get or create application
$st = $pdo->prepare('SELECT id FROM hacker_applications WHERE request_id = ? AND hacker_id = ? LIMIT 1');
$st->execute([$bountyId, $hackerId]);
$existingApp = $st->fetch();

$publicId = 'ENG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

try {
    $pdo->beginTransaction();

    if ($existingApp) {
        $appId = $existingApp['id'];
        $pdo->prepare("UPDATE hacker_applications SET status = 'accepted', resolved_at = NOW() WHERE id = ?")
            ->execute([$appId]);
    } else {
        // Create application
        $pdo->prepare('
            INSERT INTO hacker_applications
            (request_id, hacker_id, status, availability_note, resolved_at)
            VALUES (?, ?, "accepted", "Direct hire by customer", NOW())
        ')->execute([$bountyId, $hackerId]);
        $appId = $pdo->query(
            'SELECT id FROM hacker_applications WHERE request_id = ' . $pdo->quote($bountyId) .
            ' AND hacker_id = ' . $pdo->quote($hackerId) . ' LIMIT 1'
        )->fetchColumn();
    }

    // Reject all other applications for this bounty
    $pdo->prepare("
        UPDATE hacker_applications
        SET status = 'rejected', resolved_at = NOW()
        WHERE request_id = ? AND id != ?
    ")->execute([$bountyId, $appId]);

    // Create engagement
    $pdo->prepare('
        INSERT INTO engagements
        (public_id, customer_id, hacker_id, bounty_request_id, service_type,
         client_display_name, agreed_price_usd, deadline, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")
    ')->execute([
        $publicId,
        $user['id'],
        $hackerId,
        $bountyId,
        $bounty['service_key'],
        $bounty['customer_display'] ?: 'Customer',
        $bounty['price_amount'],
        $bounty['deadline'],
    ]);

    // Mark bounty as assigned
    $pdo->prepare("UPDATE bounty_requests SET status = 'assigned', assigned_hacker_id = ? WHERE id = ?")
        ->execute([$hackerId, $bountyId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('direct_hire: ' . $e->getMessage());
    json_error('Could not create engagement. Please try again.');
}

$engId = $pdo->query('SELECT id FROM engagements WHERE public_id = ' . $pdo->quote($publicId) . ' LIMIT 1')->fetchColumn();

// Notify hacker
notify($hackerId, 'engagement_created', 'New Engagement Assigned',
    'You have been hired for a ' . $bounty['service_label'] . ' engagement.',
    ['engagement_public_id' => $publicId, 'engagement_id' => $engId]);

audit('direct_hire', 'engagements', $engId, null,
    ['public_id' => $publicId, 'bounty_id' => $bountyId, 'hacker_id' => $hackerId]);

json_ok(['public_id' => $publicId, 'engagement_id' => $engId], 201);
