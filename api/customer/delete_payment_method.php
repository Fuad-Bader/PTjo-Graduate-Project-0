<?php
/**
 * PTjo — Customer: Delete a payment method
 * POST /api/customer/delete_payment_method.php
 * Body (JSON): { "payment_method_id": "<uuid>" }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body = json_input();
$pmId = trim($body['payment_method_id'] ?? '');
if (!$pmId) json_error('payment_method_id is required.');

$pdo = db();
$st  = $pdo->prepare('SELECT id FROM payment_methods WHERE id = ? AND customer_id = ? LIMIT 1');
$st->execute([$pmId, $user['id']]);
if (!$st->fetch()) json_error('Payment method not found.', 404);

$pdo->prepare('DELETE FROM payment_methods WHERE id = ?')->execute([$pmId]);

json_ok(['message' => 'Payment method deleted.']);
