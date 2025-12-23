<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Database credentials from environment variables
$DB_HOST = env('MYSQLHOST', 'localhost');
$DB_PORT = (int)env('MYSQLPORT', 3306);
$DB_NAME = env('MYSQLDATABASE', 'service_desk');
$DB_USER = env('MYSQLUSER', 'root');
$DB_PASS = env('MYSQLPASSWORD', '');

$SESSION_NAME = env('SESSION_NAME', 'ServiceDeskSession');
$SESSION_TIMEOUT = (int)env('SESSION_TIMEOUT', 3600);
$APP_ENV = env('APP_ENV', 'development');

const VALID_STATUSES = ['open', 'in_progress', 'resolved'];
const VALID_PRIORITIES = ['low', 'medium', 'high'];
const VALID_ROLES = ['admin', 'tech'];
