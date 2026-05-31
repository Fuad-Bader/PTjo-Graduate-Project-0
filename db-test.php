<?php
/**
 * PTjo — DB Diagnostic Script
 * Visit: http://127.0.0.1/PTjo/db-test.php
 * DELETE after use.
 */

// Safety key
if (($_GET['key'] ?? '') !== 'ptjo_test_2024') {
    die(json_encode(['error' => 'Missing key. Add ?key=ptjo_test_2024 to URL.']));
}

header('Content-Type: application/json');

$results = [];

// 1. Test PDO connection
try {
    $dsn = 'mysql:host=localhost;port=3306;dbname=ptjo;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $results['connection'] = 'OK';
} catch (PDOException $e) {
    $results['connection'] = 'FAILED: ' . $e->getMessage();
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// 2. Check database exists
$db = $pdo->query("SELECT DATABASE()")->fetchColumn();
$results['database'] = $db ?: 'NOT SELECTED';

// 3. Check all expected tables exist
$expected_tables = [
    'users','customer_profiles','hacker_profiles','wallets',
    'wallet_ledger_entries','payment_methods','service_offerings',
    'bounty_requests','hacker_applications','engagements',
    'vulnerability_reports','report_attachments','reviews',
    'notifications','hacker_skills','hacker_tools','hacker_languages',
    'certifications','audit_log'
];
$existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$missing = array_diff($expected_tables, $existing);
$results['tables']['expected'] = count($expected_tables);
$results['tables']['found']    = count($existing);
$results['tables']['missing']  = array_values($missing);
$results['tables']['status']   = empty($missing) ? 'OK' : 'MISSING: ' . implode(', ', $missing);

// 4. Check view exists
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
$results['views']['v_hacker_review_stats'] = in_array('v_hacker_review_stats', $views) ? 'OK' : 'MISSING';

// 5. Check column details for critical tables
$col_checks = [
    'users'                 => ['id','email','password_hash','role','is_active'],
    'customer_profiles'     => ['user_id','display_name','company_name','phone_e164','avatar_url'],
    'hacker_profiles'       => ['user_id','handle','display_name','bio','skills' => false,
                                'professional_title','location','years_experience',
                                'hourly_rate_usd','linkedin_url','github_url','public_slug'],
    'wallets'               => ['id','user_id','type','balance'],
    'payment_methods'       => ['id','customer_id','brand','last4','exp_month','exp_year',
                                'cardholder_name','label','is_default'],
    'bounty_requests'       => ['id','public_id','customer_id','service_key','service_label',
                                'price_amount','scope_details','status','deadline','priority_text'],
    'hacker_applications'   => ['id','request_id','hacker_id','status','availability_note'],
    'engagements'           => ['id','public_id','customer_id','hacker_id','bounty_request_id',
                                'service_type','client_display_name','agreed_price_usd',
                                'status','deadline'],
    'vulnerability_reports' => ['id','public_id','engagement_id','hacker_id','customer_id',
                                'service_type','severity_label','priority','title','url',
                                'description','impact','weakness','recommendations',
                                'agreed_amount_usd','status','edit_note','vuln_category',
                                'vuln_subcategory','vuln_variant','hacker_avatar_url'],
    'notifications'         => ['id','user_id','channel','type','title','body','payload','read_at'],
    'certifications'        => ['id','hacker_id','name','issuer','issued_on','credential_id'],
    'hacker_skills'         => ['hacker_id','skill_code'],
    'audit_log'             => ['id','actor_user_id','action','entity_table','entity_id'],
];

foreach ($col_checks as $table => $cols) {
    $actual = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
    $missing_cols = [];
    foreach ($cols as $k => $col) {
        // handle associative entries with false value (optional)
        if (is_int($k)) {
            if (!in_array($col, $actual)) $missing_cols[] = $col;
        }
    }
    $results['columns'][$table] = empty($missing_cols) ? 'OK' : 'MISSING COLS: ' . implode(', ', $missing_cols);
}

// 6. Test a simple write + read cycle (users table)
try {
    $testEmail = 'db_test_' . time() . '@ptjo-test.invalid';
    $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'customer')")
        ->execute([$testEmail, password_hash('test', PASSWORD_BCRYPT)]);
    $uid = $pdo->query("SELECT id FROM users WHERE email = " . $pdo->quote($testEmail) . " LIMIT 1")->fetchColumn();
    if ($uid) {
        // Insert profile
        $pdo->prepare("INSERT INTO customer_profiles (user_id, display_name) VALUES (?, ?)")
            ->execute([$uid, 'DB Test User']);
        // Insert wallet
        $pdo->prepare("INSERT INTO wallets (user_id, type) VALUES (?, 'customer')")->execute([$uid]);
        // Read back
        $profile = $pdo->query("SELECT cp.display_name, w.balance
            FROM customer_profiles cp JOIN wallets w ON w.user_id = cp.user_id
            WHERE cp.user_id = " . $pdo->quote($uid))->fetch();
        // Clean up
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $results['write_read_cycle'] = ($profile && $profile['display_name'] === 'DB Test User')
            ? 'OK — insert/select/delete all work'
            : 'PARTIAL FAIL: profile=' . json_encode($profile);
    } else {
        $results['write_read_cycle'] = 'FAIL: could not retrieve inserted user id';
    }
} catch (Throwable $e) {
    $results['write_read_cycle'] = 'FAIL: ' . $e->getMessage();
}

// 7. Test hacker write cycle
try {
    $testEmail2 = 'hacker_test_' . time() . '@ptjo-test.invalid';
    $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'hacker')")
        ->execute([$testEmail2, password_hash('test', PASSWORD_BCRYPT)]);
    $hid = $pdo->query("SELECT id FROM users WHERE email = " . $pdo->quote($testEmail2) . " LIMIT 1")->fetchColumn();
    if ($hid) {
        $handle = 'test_hacker_' . substr($hid, 0, 8);
        $pdo->prepare("INSERT INTO hacker_profiles (user_id, handle, display_name) VALUES (?, ?, ?)")
            ->execute([$hid, $handle, 'Test Hacker']);
        $pdo->prepare("INSERT INTO wallets (user_id, type) VALUES (?, 'hacker')")->execute([$hid]);
        $pdo->prepare("INSERT INTO hacker_skills (hacker_id, skill_code) VALUES (?, 'webapp')")->execute([$hid]);
        $skill = $pdo->query("SELECT skill_code FROM hacker_skills WHERE hacker_id = " . $pdo->quote($hid))->fetchColumn();
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$hid]);
        $results['hacker_write_cycle'] = ($skill === 'webapp')
            ? 'OK — hacker profile + skills insert/select/delete work'
            : 'FAIL: skill not found';
    }
} catch (Throwable $e) {
    $results['hacker_write_cycle'] = 'FAIL: ' . $e->getMessage();
}

// 8. Test FK constraints work
try {
    $pdo->prepare("INSERT INTO customer_profiles (user_id, display_name) VALUES ('00000000-0000-0000-0000-000000000000', 'ghost')")->execute([]);
    $results['fk_constraint'] = 'FAIL: FK not enforced (should have thrown)';
} catch (PDOException $e) {
    $results['fk_constraint'] = strpos($e->getMessage(), '1452') !== false
        ? 'OK — FK constraint enforced'
        : 'UNEXPECTED: ' . $e->getMessage();
}

// 9. UUID default generation
try {
    $testEmail3 = 'uuid_test_' . time() . '@ptjo-test.invalid';
    $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'customer')")
        ->execute([$testEmail3, 'x']);
    $uid3 = $pdo->query("SELECT id FROM users WHERE email=" . $pdo->quote($testEmail3) . " LIMIT 1")->fetchColumn();
    $results['uuid_default'] = (strlen($uid3) === 36 && substr_count($uid3, '-') === 4)
        ? "OK — UUID generated: $uid3"
        : "FAIL — unexpected id: $uid3";
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid3]);
} catch (Throwable $e) {
    $results['uuid_default'] = 'FAIL: ' . $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
