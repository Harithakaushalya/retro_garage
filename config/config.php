<?php
/**
 * ==============================================================
 * SPEEDWELL CLASSIC RETRO GARAGE & AUTO PARTS STORE
 * Master Configuration File (config/config.php)
 * ==============================================================
 * Easy rebranding & deployment configuration for cPanel and XAMPP.
 */

// Prevent multiple inclusions
if (defined('APP_CONFIG_LOADED')) {
    return;
}
define('APP_CONFIG_LOADED', true);

// --------------------------------------------------------------
// 1. Error Reporting (Development vs Production)
// --------------------------------------------------------------
// Set to false in live production
define('APP_DEBUG', true);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// --------------------------------------------------------------
// 2. Database Connection Credentials
// --------------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'retro_garage');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --------------------------------------------------------------
// 3. Garage Branding & Business Identity
// --------------------------------------------------------------
define('APP_NAME', 'SPEEDWELL RETRO GARAGE');
define('APP_SHORT_NAME', 'SPEEDWELL');
define('APP_TAGLINE', 'Classic Works & Analog Speed Shop • Est. 1988');
define('GARAGE_PHONE', '+94 11 289 4400');
define('GARAGE_WHATSAPP', '94771122334'); // Format: CountryCode + Number (without + or spaces)
define('GARAGE_EMAIL', 'service@speedwellgarage.local');
define('GARAGE_ADDRESS', 'Bay 42, Old Heritage Motorway, Colombo 08');
define('GARAGE_HOURS', 'Mon - Sat: 08:00 - 18:30 (JDM Tuning & Service)');

// Currency Configuration
define('CURRENCY_SYMBOL', 'Rs.');
define('CURRENCY_CODE', 'LKR');

// --------------------------------------------------------------
// 4. Dynamic URL & Path Resolution
// --------------------------------------------------------------
// Determine Protocol
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? 'https://' : 'http://';

// Host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Script Directory Detection
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = dirname($scriptName);

// Strip /admin or /api from subfolder detection if executing within subdirectories
$cleanedDir = preg_replace('#/(admin|api)$#', '', $scriptDir);
$basePath = rtrim($cleanedDir, '/');

define('BASE_URL', $protocol . $host . $basePath);
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOADS_URL', BASE_URL . '/uploads');

// --------------------------------------------------------------
// 5. Session Initialization & Security
// --------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie params (Path must be '/' for reliable cookie matching across spaces/%20 and subfolders)
    $cookieParams = [
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// --------------------------------------------------------------
// 6. Timezone Setting
// --------------------------------------------------------------
date_default_timezone_set('Asia/Colombo');
