<?php
/**
 * ==============================================================
 * Database Connection Wrapper (PDO)
 * ==============================================================
 * Strict prepared statements, UTF-8 encoding, and clean exception handling.
 */

require_once __DIR__ . '/../config/config.php';

function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if database doesn't exist or connection failed
            $errorMsg = $e->getMessage();
            if (APP_DEBUG) {
                $instruction = '<div style="font-family: monospace; background:#161618; color:#F3ECD8; padding:30px; border:3px solid #D93829; max-width:800px; margin:50px auto; box-shadow:6px 6px 0 #000;">'
                    . '<h2 style="color:#D93829; margin-top:0;">[RETRO GARAGE DB ERROR] Database Connection Failed</h2>'
                    . '<p><strong>Details:</strong> ' . htmlspecialchars($errorMsg) . '</p>'
                    . '<hr style="border:1px dashed #E8A317; margin:20px 0;">'
                    . '<h4 style="color:#E8A317;">HOW TO RESOLVE (XAMPP / cPanel):</h4>'
                    . '<ol style="line-height:1.8;">'
                    . '<li>Open <strong>phpMyAdmin</strong> (e.g., <a href="http://localhost/phpmyadmin" style="color:#2A9D8F;">http://localhost/phpmyadmin</a>).</li>'
                    . '<li>Create a new database named: <strong style="color:#2A9D8F;">' . DB_NAME . '</strong> with collation <code>utf8mb4_unicode_ci</code>.</li>'
                    . '<li>Import the file: <code>database.sql</code> from the application root directory.</li>'
                    . '<li>If your MySQL user/password differs from <code>root</code> / <code>""</code>, adjust <code>config/config.php</code>.</li>'
                    . '</ol>'
                    . '<p style="color:#888; font-size:12px;">SPEEDWELL CLASSIC RETRO GARAGE SYSTEM</p>'
                    . '</div>';
                die($instruction);
            } else {
                die('Service temporarily unavailable. Please contact the workshop counter.');
            }
        }
    }

    return $pdo;
}
