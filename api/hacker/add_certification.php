<?php
/**
 * PTjo — Hacker: Add a certification
 * POST /api/hacker/add_certification.php
 * Body (JSON): { "name": "...", "issuer": "...", "issued_on": "YYYY-MM-DD",
 *               "credential_id": "...", "image_url": "..." }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body  = json_input();
$name  = substr(trim($body['name']          ?? ''), 0, 255);
$issuer = substr(trim($body['issuer']       ?? ''), 0, 255);
$issuedOn = trim($body['issued_on']         ?? '');
$credId   = substr(trim($body['credential_id'] ?? ''), 0, 255);
$imgUrl   = substr(trim($body['image_url']     ?? ''), 0, 500);

if (!$name)   json_error('name is required.');
if (!$issuer) json_error('issuer is required.');
if ($issuedOn && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedOn)) {
    json_error('issued_on must be YYYY-MM-DD.');
}

$pdo = db();
$pdo->prepare('
    INSERT INTO certifications (hacker_id, name, issuer, issued_on, credential_id, image_url)
    VALUES (?, ?, ?, ?, ?, ?)
')->execute([$user['id'], $name, $issuer, $issuedOn ?: null, $credId ?: null, $imgUrl ?: null]);

$certId = $pdo->query('SELECT id FROM certifications WHERE hacker_id = ' . $pdo->quote($user['id']) . ' ORDER BY created_at DESC LIMIT 1')->fetchColumn();

json_ok(['certification_id' => $certId, 'message' => 'Certification added.'], 201);
