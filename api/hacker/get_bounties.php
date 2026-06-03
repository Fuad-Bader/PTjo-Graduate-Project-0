<?php
/**
 * PTjo — Hacker: Browse open bounty requests
 * GET /api/hacker/get_bounties.php[?skill=webapp]
 * Returns open bounties, filtered by service_key if provided.
 * Excludes bounties the hacker already applied to.
 */

require_once __DIR__ . '/../../config/auth.php';

$user  = require_role('hacker');
$skill = input('skill');
$pdo   = db();

$sql = '
    SELECT br.*,
           cp.display_name AS customer_name,
           cp.company_name,
           (SELECT COUNT(*) FROM hacker_applications ha WHERE ha.request_id = br.id) AS application_count,
           EXISTS(SELECT 1 FROM hacker_applications ha2
                  WHERE ha2.request_id = br.id AND ha2.hacker_id = ?) AS already_applied
    FROM bounty_requests br
    LEFT JOIN customer_profiles cp ON cp.user_id = br.customer_id
    WHERE br.status = "open"
      AND NOT EXISTS(SELECT 1 FROM hacker_dismissed_bounties hd
                     WHERE hd.bounty_id = br.id AND hd.hacker_id = ?)
';
$params = [$user['id'], $user['id']];

if ($skill) {
    $sql     .= ' AND br.service_key = ?';
    $params[] = $skill;
}

$sql .= ' ORDER BY br.created_at DESC LIMIT 50';

$st = $pdo->prepare($sql);
$st->execute($params);
$bounties = $st->fetchAll();

// Cast already_applied to bool
foreach ($bounties as &$b) {
    $b['already_applied'] = (bool)$b['already_applied'];
}

json_ok(['bounties' => $bounties]);
