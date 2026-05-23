<?php
/**
 * Copy this file to `config.local.php` (same folder) and fill in your DB.
 * The real `config.local.php` is git-ignored — your credentials never leave
 * your machine / server.
 *
 * XAMPP default values:
 *   define('DB_USER', 'root');
 *   define('DB_PASS', '');
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'inganzo_mgmt');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL path the app is served from. If the folder is at
//   http://localhost/inganzongari/mgmt/   set this to '/inganzongari/mgmt'.
//   http://example.com/mgmt/              set this to '/mgmt'.
//   http://mgmt.example.com/              set this to ''.
define('APP_BASE', '/inganzongari/mgmt');

// 'production' (errors hidden) or 'development' (errors visible).
define('APP_ENV', 'production');
