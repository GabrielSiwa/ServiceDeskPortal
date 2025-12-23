<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/src/env.php';

// Database credentials from environment variables
define('DB_HOST', env('MYSQLHOST', 'localhost'));
define('DB_PORT', (int)env('MYSQLPORT', 3306));
define('DB_NAME', env('MYSQLDATABASE', 'service_desk'));
define('DB_USER', env('MYSQLUSER', 'root'));
define('DB_PASS', env('MYSQLPASSWORD', ''));

define('SESSION_NAME', env('SESSION_NAME', 'ServiceDeskSession'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600));
$APP_ENV = env('APP_ENV', 'development');

const VALID_STATUSES = ['open', 'in_progress', 'resolved'];
const VALID_PRIORITIES = ['low', 'medium', 'high'];
const VALID_ROLES = ['admin', 'tech'];
