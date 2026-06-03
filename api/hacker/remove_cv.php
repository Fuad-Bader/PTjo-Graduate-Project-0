<?php
/**
 * PTjo — Hacker: Remove the stored Resume / CV.
 * POST (no body needed). Deletes the file from disk and clears the columns.
 * Returns { ok: true }.
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$pdo = db();

$st = $pdo->prepare('SELECT cv_url FROM hacker_profiles WHERE user_id = ? LIMIT 1');
$st->execute([$user['id']]);
$prev = (string)$st->fetchColumn();

if ($prev !== '' && strpos($prev, '/uploads/cv/') === 0) {
    $dir      = realpath(__DIR__ . '/../../uploads/cv');
    $prevPath = realpath(__DIR__ . '/../..' . $prev);
    if ($dir && $prevPath && is_file($prevPath) && strpos($prevPath, $dir) === 0) {
        @unlink($prevPath);
    }
}

$st = $pdo->prepare('UPDATE hacker_profiles SET cv_url = NULL, cv_filename = NULL, cv_size_bytes = NULL WHERE user_id = ?');
$st->execute([$user['id']]);

audit('remove_cv', 'hacker_profiles', $user['id'], ['cv_url' => $prev], null);

json_ok();
