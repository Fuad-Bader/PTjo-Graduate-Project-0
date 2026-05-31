<?php
/**
 * PTjo — Database connection (MySQL 8 / PDO)
 * Place this file outside the web root in production.
 * Edit the constants below to match your environment.
 */

define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'ptjo');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Do NOT expose DB credentials in the response
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Database unavailable. Please try again later.']);
            exit;
        }
    }
    return $pdo;
}
