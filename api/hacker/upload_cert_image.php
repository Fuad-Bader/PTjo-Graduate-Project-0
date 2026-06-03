<?php
/**
 * PTjo — Hacker: Upload a certification image.
 * POST multipart/form-data with a file field "image".
 * Stores the file under /uploads/certs/ and returns its (short) web path.
 * The path is then saved as the certification's image_url via add_certification.php,
 * so it persists in the DB instead of a giant (truncated) base64 data URL.
 * Returns { ok: true, image_url: "/uploads/certs/<file>" }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    json_error('No file uploaded.');
}

$f = $_FILES['image'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_error('Upload failed (code ' . $f['error'] . ').');
if ($f['size'] > 5 * 1024 * 1024) json_error('Image too large (max 5MB).');

// Validate it is a real image and pick a safe extension from the actual content.
$info = @getimagesize($f['tmp_name']);
if ($info === false) json_error('File is not a valid image.');
$mimeExt = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$mime = $info['mime'] ?? '';
if (!isset($mimeExt[$mime])) json_error('Unsupported image type. Use JPG, PNG, GIF or WEBP.');
$ext = $mimeExt[$mime];

$dir = __DIR__ . '/../../uploads/certs';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
// Self-heal: a dir created with a restrictive umask may not be writable by the
// web-server user. Try to relax it before giving up.
if (is_dir($dir) && !is_writable($dir)) @chmod($dir, 0777);
if (!is_dir($dir) || !is_writable($dir)) json_error('Certificate image storage is not writable.', 500);

$filename = 'cert_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest     = $dir . '/' . $filename;

if (!move_uploaded_file($f['tmp_name'], $dest)) {
    json_error('Could not save the uploaded file.', 500);
}
@chmod($dest, 0644);

$webPath = '/uploads/certs/' . $filename;

audit('upload_cert_image', 'certifications', null, null, ['image_url' => $webPath]);

json_ok(['image_url' => $webPath]);
