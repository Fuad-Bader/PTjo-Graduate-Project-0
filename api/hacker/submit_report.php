<?php
/**
 * PTjo — Hacker: Submit a vulnerability report
 * POST /api/hacker/submit_report.php
 * Body (JSON): engagement_id, severity_label, priority, title, url,
 *              description, impact, recommendations, weakness,
 *              vuln_category, vuln_subcategory, vuln_variant,
 *              vuln_category_full, vuln_subcategory_full, vuln_variant_full,
 *              vulnerability_path, agreed_amount_usd
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body = json_input();

$engId = trim($body['engagement_id'] ?? '');

// Accept severity_label OR severity (bridge shorthand)
$severityLabel = substr(trim($body['severity_label'] ?? $body['severity'] ?? ''), 0, 64);

// Map severity label → priority 1–5 if priority not explicitly provided
$severityPriorityMap = ['Critical'=>1,'High'=>2,'Medium'=>3,'Low'=>4,'Info'=>5];
$priority = (int)($body['priority'] ?? $severityPriorityMap[$severityLabel] ?? 3);
if ($priority < 1) $priority = 1;
if ($priority > 5) $priority = 5;

$title = substr(trim($body['title'] ?? $body['affected_component'] ?? ''), 0, 255);

// url is required by schema; fall back gracefully for simplified bridge calls
$url = substr(trim($body['url'] ?? $body['affected_url'] ?? $body['affected_component'] ?? 'N/A'), 0, 2000);

$description = trim($body['description'] ?? '');
// Append steps_to_reproduce to description if provided separately
$steps = trim($body['steps_to_reproduce'] ?? $body['steps'] ?? '');
if ($steps) {
    $description .= ($description ? "\n\nSteps to Reproduce:\n" : 'Steps to Reproduce: ') . $steps;
}

$impact = trim($body['impact'] ?? '');

// Accept recommendations OR recommendation (bridge shorthand)
$recommendations = trim($body['recommendations'] ?? $body['recommendation'] ?? '');

$weakness = substr(trim($body['weakness'] ?? ''), 0, 500);
$amount   = round((float)($body['agreed_amount_usd'] ?? $body['agreed_amount'] ?? 0), 2);

// Taxonomy fields (optional)
$vulnCat     = substr(trim($body['vuln_category']         ?? ''), 0, 255);
$vulnSub     = substr(trim($body['vuln_subcategory']      ?? ''), 0, 255);
$vulnVar     = substr(trim($body['vuln_variant']          ?? ''), 0, 255);
$vulnCatFull = substr(trim($body['vuln_category_full']    ?? ''), 0, 255);
$vulnSubFull = substr(trim($body['vuln_subcategory_full'] ?? ''), 0, 255);
$vulnVarFull = substr(trim($body['vuln_variant_full']     ?? ''), 0, 255);
$vulnPath    = substr(trim($body['vulnerability_path']    ?? ''), 0, 500);

// Validation — minimums relaxed to accommodate simplified bridge submissions
$errors = [];
if (!$engId)                         $errors[] = 'engagement_id is required.';
if (!$severityLabel)                 $errors[] = 'severity_label is required.';
if (strlen($title) < 3)             $errors[] = 'title is required (min 3 chars).';
if (strlen($description) < 10)      $errors[] = 'description is required (min 10 chars).';
if (strlen($impact) < 5)            $errors[] = 'impact is required (min 5 chars).';
if (strlen($recommendations) < 5)   $errors[] = 'recommendations is required (min 5 chars).';
if ($errors) json_error(implode(' ', $errors));

$pdo = db();

// Verify engagement belongs to this hacker and is active
$st = $pdo->prepare("
    SELECT e.*, cp.display_name AS customer_display
    FROM engagements e
    LEFT JOIN customer_profiles cp ON cp.user_id = e.customer_id
    WHERE e.id = ? AND e.hacker_id = ?
    AND e.status IN ('accepted','in_progress','pending')
    LIMIT 1
");
$st->execute([$engId, $user['id']]);
$eng = $st->fetch();
if (!$eng) json_error('Engagement not found or not in an active state.', 404);

// Get hacker avatar
$st2 = $pdo->prepare('SELECT avatar_url FROM hacker_profiles WHERE user_id = ?');
$st2->execute([$user['id']]);
$hp = $st2->fetch();

$publicId = 'RPT-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));

$pdo->prepare('
    INSERT INTO vulnerability_reports
    (public_id, engagement_id, hacker_id, customer_id, service_type,
     severity_label, priority, title, url, description, impact, weakness,
     recommendations, agreed_amount_usd, vuln_category, vuln_subcategory, vuln_variant,
     vuln_category_full, vuln_subcategory_full, vuln_variant_full,
     vulnerability_path, hacker_avatar_url, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted")
')->execute([
    $publicId, $engId, $user['id'], $eng['customer_id'], $eng['service_type'],
    $severityLabel, $priority, $title, $url, $description, $impact, $weakness,
    $recommendations, $amount, $vulnCat, $vulnSub, $vulnVar,
    $vulnCatFull, $vulnSubFull, $vulnVarFull, $vulnPath,
    $hp['avatar_url'] ?? null,
]);

$reportId = $pdo->query('SELECT id FROM vulnerability_reports WHERE public_id = ' . $pdo->quote($publicId) . ' LIMIT 1')->fetchColumn();

// Auto-advance engagement to in_progress if still pending/accepted
if (in_array($eng['status'], ['pending','accepted'], true)) {
    $pdo->prepare("UPDATE engagements SET status = 'in_progress' WHERE id = ?")->execute([$engId]);
}

// Notify customer
notify($eng['customer_id'], 'report_submitted', 'New Vulnerability Report',
    'A new vulnerability report "' . $title . '" has been submitted for your review.',
    ['report_id' => $reportId, 'public_id' => $publicId, 'engagement_id' => $engId]);

audit('submit_report', 'vulnerability_reports', $reportId, null,
    ['public_id' => $publicId, 'severity' => $severityLabel]);

json_ok(['public_id' => $publicId, 'report_id' => $reportId], 201);
