<?php
/**
 * PTjo — Hacker: Delete a certification
 * POST /api/hacker/delete_certification.php
 * Body (JSON): { "certification_id": "<uuid>" }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body   = json_input();
$certId = trim($body['certification_id'] ?? '');
if (!$certId) json_error('certification_id is required.');

$pdo = db();
$st  = $pdo->prepare('SELECT id FROM certifications WHERE id = ? AND hacker_id = ? LIMIT 1');
$st->execute([$certId, $user['id']]);
if (!$st->fetch()) json_error('Certification not found.', 404);

$pdo->prepare('DELETE FROM certifications WHERE id = ?')->execute([$certId]);

json_ok(['message' => 'Certification deleted.']);
