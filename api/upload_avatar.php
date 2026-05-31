<?php
/**
 * PTjo — Upload a profile avatar (customer or hacker).
 * POST multipart/form-data with a file field "avatar".
 * Stores the file under /uploads/avatars/ and saves its (short) web path in
 * the caller's profile row, so it persists in the DB and shows on every page.
 * Returns { ok: true, avatar_url: "/uploads/avatars/<file>" }.
 */

require_once __DIR__ . '/../config/auth.php';

$user = require_role('customer', 'hacker');
csrf_verify();

if (empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    json_error('No file uploaded.');
}

$f = $_FILES['avatar'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_error('Upload failed (code ' . $f['error'] . ').');
if ($f['size'] > 3 * 1024 * 1024) json_error('Image too large (max 3MB).');

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

$dir = __DIR__ . '/../uploads/avatars';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
if (!is_dir($dir) || !is_writable($dir)) json_error('Avatar storage is not writable.', 500);

$filename = 'av_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest     = $dir . '/' . $filename;

if (!move_uploaded_file($f['tmp_name'], $dest)) {
    json_error('Could not save the uploaded file.', 500);
}
@chmod($dest, 0644);

$webPath = '/uploads/avatars/' . $filename;

// Whitelisted by the require_role check above — never raw user input.
$table = ($user['role'] === 'hacker') ? 'hacker_profiles' : 'customer_profiles';

$pdo = db();

// Best-effort cleanup of a previously-uploaded avatar file.
try {
    $st = $pdo->prepare("SELECT avatar_url FROM {$table} WHERE user_id = ? LIMIT 1");
    $st->execute([$user['id']]);
    $prev = (string)$st->fetchColumn();
    if ($prev !== '' && strpos($prev, '/uploads/avatars/') === 0) {
        $prevPath = realpath(__DIR__ . '/..' . $prev);
        if ($prevPath && is_file($prevPath) && strpos($prevPath, realpath($dir)) === 0) {
            @unlink($prevPath);
        }
    }
} catch (Throwable $e) { /* non-fatal */ }

$st = $pdo->prepare("UPDATE {$table} SET avatar_url = ? WHERE user_id = ?");
$st->execute([$webPath, $user['id']]);

// Guard against a silent no-op: if no profile row matched, the avatar would
// "upload" yet vanish on the next page load. The filename is always unique, so
// a zero row-count here means the profile row is genuinely missing, not unchanged.
if ($st->rowCount() === 0) {
    @unlink($dest);
    json_error('Could not save avatar: profile not found.', 500);
}

audit('upload_avatar', $table, $user['id'], null, ['avatar_url' => $webPath]);

json_ok(['avatar_url' => $webPath]);
