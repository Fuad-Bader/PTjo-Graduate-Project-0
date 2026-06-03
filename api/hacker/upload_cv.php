<?php
/**
 * PTjo — Hacker: Upload a Resume / CV.
 * POST multipart/form-data with a file field "cv".
 * Stores the file under /uploads/cv/ and saves its (short) web path + original
 * name + size in the hacker's profile row, so it persists in the DB and reloads
 * on every page visit.
 * Returns { ok: true, cv_url, cv_filename, cv_size_bytes }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

if (empty($_FILES['cv']) || !is_uploaded_file($_FILES['cv']['tmp_name'])) {
    json_error('No file uploaded.');
}

$f = $_FILES['cv'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_error('Upload failed (code ' . $f['error'] . ').');
if ($f['size'] > 10 * 1024 * 1024) json_error('File too large (max 10MB).');

$origName = basename($f['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

// Validate the actual file content, not just the client-supplied name.
$mime = mime_content_type($f['tmp_name']) ?: ($f['type'] ?? '');
$allowed = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
];
if (!isset($allowed[$ext]) || !in_array($mime, $allowed[$ext], true)) {
    json_error('Only PDF, DOC, or DOCX files are allowed.');
}

$dir = __DIR__ . '/../../uploads/cv';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
// Self-heal: a dir created with a restrictive umask may not be writable by the
// web-server user. Try to relax it before giving up.
if (is_dir($dir) && !is_writable($dir)) @chmod($dir, 0777);
if (!is_dir($dir) || !is_writable($dir)) json_error('CV storage is not writable.', 500);

$filename = 'cv_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest     = $dir . '/' . $filename;

if (!move_uploaded_file($f['tmp_name'], $dest)) {
    json_error('Could not save the uploaded file.', 500);
}
@chmod($dest, 0644);

$webPath = '/uploads/cv/' . $filename;
$size    = (int)$f['size'];

$pdo = db();

// Best-effort cleanup of a previously-uploaded CV file.
try {
    $st = $pdo->prepare('SELECT cv_url FROM hacker_profiles WHERE user_id = ? LIMIT 1');
    $st->execute([$user['id']]);
    $prev = (string)$st->fetchColumn();
    if ($prev !== '' && strpos($prev, '/uploads/cv/') === 0) {
        $prevPath = realpath(__DIR__ . '/../..' . $prev);
        if ($prevPath && is_file($prevPath) && strpos($prevPath, realpath($dir)) === 0) {
            @unlink($prevPath);
        }
    }
} catch (Throwable $e) { /* non-fatal */ }

$st = $pdo->prepare('UPDATE hacker_profiles SET cv_url = ?, cv_filename = ?, cv_size_bytes = ? WHERE user_id = ?');
$st->execute([$webPath, $origName, $size, $user['id']]);

// Guard against a silent no-op: if no profile row matched, the CV would
// "upload" yet vanish on the next page load.
if ($st->rowCount() === 0) {
    @unlink($dest);
    json_error('Could not save CV: profile not found.', 500);
}

audit('upload_cv', 'hacker_profiles', $user['id'], null, ['cv_url' => $webPath, 'cv_filename' => $origName]);

json_ok(['cv_url' => $webPath, 'cv_filename' => $origName, 'cv_size_bytes' => $size]);
