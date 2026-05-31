<?php
/**
 * PTjo — Hacker: Upload a report attachment
 * POST /api/hacker/upload_attachment.php (multipart/form-data)
 * Fields: report_id, file (the uploaded file)
 * Returns: { ok, attachment: { id, file_name, size_bytes, mime_type } }
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$reportId = trim($_POST['report_id'] ?? '');
if (!$reportId) json_error('report_id is required.');

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['file']['error'] ?? -1;
    json_error('File upload failed (error code ' . $code . ').');
}

$pdo = db();

// Verify the report belongs to this hacker
$st = $pdo->prepare('SELECT id FROM vulnerability_reports WHERE id = ? AND hacker_id = ? LIMIT 1');
$st->execute([$reportId, $user['id']]);
if (!$st->fetch()) json_error('Report not found or access denied.', 403);

$file     = $_FILES['file'];
$origName = basename($file['name']);
$mime     = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'application/octet-stream');
$size     = (int)$file['size'];

// 10 MB limit
if ($size > 10 * 1024 * 1024) json_error('File exceeds the 10 MB size limit.');

// Allowed types
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'text/plain', 'text/csv',
    'application/zip', 'application/x-zip-compressed',
    'application/json',
];
if (!in_array($mime, $allowedMimes, true)) {
    json_error('File type not allowed: ' . $mime);
}

$uploadDir = __DIR__ . '/../../uploads/attachments/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$safeExt     = preg_replace('/[^a-z0-9]/', '', $ext);
$storageKey  = bin2hex(random_bytes(16)) . ($safeExt ? '.' . $safeExt : '');
$destPath    = $uploadDir . $storageKey;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_error('Could not save file to disk.');
}

$checksum = hash_file('sha256', $destPath);

$id = bin2hex(random_bytes(16));
$pdo->prepare('
    INSERT INTO report_attachments (id, report_id, file_name, mime_type, size_bytes, storage_key, checksum_sha256)
    VALUES (?, ?, ?, ?, ?, ?, ?)
')->execute([$id, $reportId, $origName, $mime, $size, $storageKey, $checksum]);

json_ok([
    'attachment' => [
        'id'         => $id,
        'file_name'  => $origName,
        'size_bytes' => $size,
        'mime_type'  => $mime,
    ],
], 201);
