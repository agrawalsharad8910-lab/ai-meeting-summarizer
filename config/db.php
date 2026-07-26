<?php
// ============================================================
// AI Meeting Minutes Summarizer - Database Configuration
// ============================================================

// Support environment variables (useful for Render, Docker, cloud hosting)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'ai_meeting_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbPort = getenv('DB_PORT') ?: '3306';

// Parse DATABASE_URL or MYSQL_URL if provided (e.g. mysql://user:pass@host:3306/dbname)
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    if ($parsed) {
        $dbHost = $parsed['host'] ?? $dbHost;
        $dbPort = isset($parsed['port']) ? (string)$parsed['port'] : $dbPort;
        $dbUser = $parsed['user'] ?? $dbUser;
        $dbPass = $parsed['pass'] ?? $dbPass;
        $dbName = !empty($parsed['path']) ? ltrim($parsed['path'], '/') : $dbName;
    }
}

if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
if (!defined('DB_NAME')) define('DB_NAME', $dbName);
if (!defined('DB_USER')) define('DB_USER', $dbUser);
if (!defined('DB_PASS')) define('DB_PASS', $dbPass);
if (!defined('DB_PORT')) define('DB_PORT', $dbPort);

/**
 * Get PDO Database Connection
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Return null so the API or page can offer demo/fallback mode if MySQL isn't running
        return null;
    }
}

/**
 * Return JSON Response Helper
 */
function sendJsonResponse($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

// Start Session safely across pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
