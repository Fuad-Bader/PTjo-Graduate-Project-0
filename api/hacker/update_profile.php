<?php
/**
 * PTjo — Hacker: Update profile
 * POST /api/hacker/update_profile.php
 * Body (JSON): display_name, professional_title, bio, location,
 *              years_experience, hourly_rate_usd, linkedin_url,
 *              github_url, portfolio_url, phone_e164, avatar_url,
 *              skills[], tools[], languages[]
 */

require_once __DIR__ . '/../../config/auth.php';

$user = require_role('hacker');
csrf_verify();

$body = json_input();

$fields = [
    'display_name'       => substr(trim($body['display_name']       ?? ''), 0, 255),
    'professional_title' => substr(trim($body['professional_title'] ?? ''), 0, 255),
    'bio'                => substr(trim($body['bio']                ?? ''), 0, 2000),
    'location'           => substr(trim($body['location']           ?? ''), 0, 255),
    'years_experience'   => substr(trim($body['years_experience']   ?? ''), 0, 64),
    'hourly_rate_usd'    => isset($body['hourly_rate_usd']) ? round((float)$body['hourly_rate_usd'], 2) : null,
    'linkedin_url'       => substr(trim($body['linkedin_url']       ?? ''), 0, 500),
    'github_url'         => substr(trim($body['github_url']         ?? ''), 0, 500),
    'portfolio_url'      => substr(trim($body['portfolio_url']      ?? ''), 0, 500),
    'phone_e164'         => substr(trim($body['phone_e164']         ?? ''), 0, 30),
    'avatar_url'         => substr(trim($body['avatar_url']         ?? ''), 0, 500),
];

if (empty($fields['display_name'])) json_error('display_name is required.');

$pdo = db();
$old = $pdo->prepare('SELECT * FROM hacker_profiles WHERE user_id = ?');
$old->execute([$user['id']]);
$oldData = $old->fetch();

try {
    $pdo->beginTransaction();

    $pdo->prepare('
        UPDATE hacker_profiles SET
            display_name=?, professional_title=?, bio=?, location=?,
            years_experience=?, hourly_rate_usd=?, linkedin_url=?,
            github_url=?, portfolio_url=?, phone_e164=?, avatar_url=?
        WHERE user_id=?
    ')->execute([
        $fields['display_name'], $fields['professional_title'], $fields['bio'],
        $fields['location'], $fields['years_experience'], $fields['hourly_rate_usd'],
        $fields['linkedin_url'], $fields['github_url'], $fields['portfolio_url'],
        $fields['phone_e164'], $fields['avatar_url'],
        $user['id'],
    ]);

    // Skills
    if (isset($body['skills']) && is_array($body['skills'])) {
        $pdo->prepare('DELETE FROM hacker_skills WHERE hacker_id = ?')->execute([$user['id']]);
        $ins = $pdo->prepare('INSERT IGNORE INTO hacker_skills (hacker_id, skill_code) VALUES (?, ?)');
        foreach (array_unique($body['skills']) as $sk) {
            $ins->execute([$user['id'], substr(trim($sk), 0, 80)]);
        }
    }

    // Tools
    if (isset($body['tools']) && is_array($body['tools'])) {
        $pdo->prepare('DELETE FROM hacker_tools WHERE hacker_id = ?')->execute([$user['id']]);
        $ins = $pdo->prepare('INSERT IGNORE INTO hacker_tools (hacker_id, tool_name) VALUES (?, ?)');
        foreach (array_unique($body['tools']) as $t) {
            $ins->execute([$user['id'], substr(trim($t), 0, 120)]);
        }
    }

    // Languages
    if (isset($body['languages']) && is_array($body['languages'])) {
        $pdo->prepare('DELETE FROM hacker_languages WHERE hacker_id = ?')->execute([$user['id']]);
        $ins = $pdo->prepare('INSERT IGNORE INTO hacker_languages (hacker_id, language) VALUES (?, ?)');
        foreach (array_unique($body['languages']) as $l) {
            $ins->execute([$user['id'], substr(trim($l), 0, 80)]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('update_profile (hacker): ' . $e->getMessage());
    json_error('Failed to update profile. Please try again.');
}

audit('update_profile', 'hacker_profiles', $user['id'], $oldData, $fields);
json_ok(['message' => 'Profile updated.']);
