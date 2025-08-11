<?php
// Bootstrap: load config, start session, setup PDO and helpers

$config = require __DIR__ . '/../config/config.php';

// Timezone
if (!empty($config['site']['timezone'])) {
    date_default_timezone_set($config['site']['timezone']);
}

// Error reporting
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/logs/php-error.log');
error_reporting(E_ALL);

// Sessions with secure defaults
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
];
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

// PDO connection
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db']['host'], $config['db']['name'], $config['db']['charset']);
try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    app_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Database connection error.';
    exit;
}

// Make available via function
global $APP_PDO, $APP_CONFIG;
$APP_PDO = $pdo;
$APP_CONFIG = $config;