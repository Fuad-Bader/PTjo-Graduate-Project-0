<?php
/**
 * PTjo — Customer: Add a payment method (demo — stores last4 only)
 * POST /api/customer/add_payment_method.php
 * Body (JSON): { "brand": "Visa", "last4": "4242",
 *               "exp_month": 12, "exp_year": 2027,
 *               "cardholder_name": "...", "label": "...", "is_default": true }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('customer');
csrf_verify();

$body   = json_input();
$brand  = substr(trim($body['brand']           ?? ''), 0, 80);
$last4  = trim($body['last4']                  ?? '');
$expM   = (int)($body['exp_month']             ?? 0);
$expY   = (int)($body['exp_year']              ?? 0);
$name   = substr(trim($body['cardholder_name'] ?? ''), 0, 255);
$label  = substr(trim($body['label']           ?? ''), 0, 255);
$isDef  = (bool)($body['is_default']           ?? false);

if (!$brand)                           json_error('brand is required.');
if (!preg_match('/^\d{4}$/', $last4))  json_error('last4 must be exactly 4 digits.');
if ($expM < 1 || $expM > 12)          json_error('Invalid exp_month.');
if ($expY < date('Y'))                 json_error('Card is expired.');

$pdo = db();

// Unset existing default if needed
if ($isDef) {
    $pdo->prepare('UPDATE payment_methods SET is_default = 0 WHERE customer_id = ?')
        ->execute([$user['id']]);
}

$pdo->prepare('
    INSERT INTO payment_methods (customer_id, brand, last4, exp_month, exp_year, cardholder_name, label, is_default)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
')->execute([$user['id'], $brand, $last4, $expM, $expY, $name ?: null, $label ?: null, $isDef]);

$pmId = $pdo->query('SELECT id FROM payment_methods WHERE customer_id = ' . $pdo->quote($user['id']) . ' ORDER BY created_at DESC LIMIT 1')->fetchColumn();

json_ok(['payment_method_id' => $pmId, 'message' => 'Payment method added.'], 201);
