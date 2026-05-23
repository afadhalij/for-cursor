<?php
/**
 * Inganzo Ngari Management System — central configuration.
 *
 * Override any of these by creating a sibling file `config.local.php`
 * (git-ignored) and defining the same constants there.
 *
 *   <?php
 *   define('DB_HOST', 'localhost');
 *   define('DB_NAME', 'inganzo_mgmt');
 *   define('DB_USER', 'root');
 *   define('DB_PASS', '');
 */

declare(strict_types=1);

// Allow a local override file to win.
if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// ---------- Database ----------
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'inganzo_mgmt');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ---------- Application ----------
if (!defined('APP_NAME')) define('APP_NAME', 'Inganzo Ngari');
if (!defined('APP_BASE')) define('APP_BASE', '/mgmt');      // URL path the app is served from
if (!defined('APP_ENV'))  define('APP_ENV',  'production');  // 'production' | 'development'

// ---------- Uploads ----------
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', APP_BASE . '/uploads');
if (!defined('MAX_UPLOAD_BYTES')) define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------- Error reporting ----------
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// ---------- Time ----------
date_default_timezone_set('Africa/Kigali');
