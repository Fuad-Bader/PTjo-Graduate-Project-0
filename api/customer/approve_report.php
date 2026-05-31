<?php
/**
 * PTjo — Customer: Approve or request edits on a vulnerability report
 * POST /api/customer/approve_report.php
 * Body (JSON): { "report_id": "<uuid>", "action": "approve"|"request_edit",
 *               "edit_note": "...", "payment_method": "...", "payment_card_label": "..." }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body         = json_input();
$reportId     = trim($body['report_id']          ?? '');
$editNote     = trim($body['edit_note']          ?? '');
$paymentMethod = trim($body['payment_method']    ?? '');
$cardLabel    = trim($body['payment_card_label'] ?? '');

// Accept either action:'approve'|'request_edit' OR approved:true|false (bridge shorthand)
$action = trim($body['action'] ?? '');
if (!$action) {
    if (isset($body['approved'])) {
        $action = $body['approved'] ? 'approve' : 'request_edit';
    }
}

if (!$reportId) json_error('report_id is required.');
if (!in_array($action, ['approve','request_edit'], true)) json_error('action must be approve or request_edit.');

$pdo = db();
$st  = $pdo->prepare('SELECT * FROM vulnerability_reports WHERE id = ? AND customer_id = ? LIMIT 1');
$st->execute([$reportId, $user['id']]);
$report = $st->fetch();

if (!$report) json_error('Report not found.', 404);

$reviewableStatuses = ['submitted','under_review','edit_requested'];
if (!in_array($report['status'], $reviewableStatuses, true)) {
    json_error('Report cannot be actioned in its current status: ' . $report['status']);
}

if ($action === 'approve') {
    $pdo->prepare('
        UPDATE vulnerability_reports
        SET status = "approved", approved_at = NOW(),
            payment_method = ?, payment_card_label = ?
        WHERE id = ?
    ')->execute([
        substr($paymentMethod, 0, 120),
        substr($cardLabel, 0, 255),
        $reportId,
    ]);
    notify($report['hacker_id'], 'report_approved', 'Report Approved',
        'Your vulnerability report "' . $report['title'] . '" has been approved.',
        ['report_id' => $reportId, 'public_id' => $report['public_id']]);
    audit('approve_report', 'vulnerability_reports', $reportId, ['status' => $report['status']], ['status' => 'approved']);
    json_ok(['message' => 'Report approved.']);
}

// request_edit
if (empty($editNote)) json_error('edit_note is required when requesting edits.');
$pdo->prepare('
    UPDATE vulnerability_reports
    SET status = "edit_requested", edit_note = ?
    WHERE id = ?
')->execute([substr($editNote, 0, 2000), $reportId]);

notify($report['hacker_id'], 'report_edit_requested', 'Edit Requested on Report',
    'The customer has requested edits on "' . $report['title'] . '". Note: ' . $editNote,
    ['report_id' => $reportId]);
audit('request_edit_report', 'vulnerability_reports', $reportId, ['status' => $report['status']], ['status' => 'edit_requested']);

json_ok(['message' => 'Edit request sent to hacker.']);
