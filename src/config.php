<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Application paths
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('SRC_ROOT', APP_ROOT . '/src');

// Database configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'service_desk'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_PORT', (int)env('DB_PORT', 3306));

// Session configuration
define('SESSION_NAME', env('SESSION_NAME', 'ServiceDeskSession'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600));

// Valid enumeration values
define('VALID_STATUSES', ['open', 'in_progress', 'resolved', 'closed']);
define('VALID_PRIORITIES', ['low', 'medium', 'high', 'critical']);
define('VALID_ROLES', ['admin', 'tech']);

?>
