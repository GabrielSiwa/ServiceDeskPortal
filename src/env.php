<?php
declare(strict_types=1);

/**
 * Load environment variables from .env file
 * 
 * @param string|null $filePath Path to .env file (defaults to project root)
 * @return void
 */
function loadEnv(?string $filePath = null): void
{
    if (!$filePath) {
        $filePath = dirname(__DIR__) . '/.env';
    }

    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!empty($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Get environment variable with fallback to default
 * 
 * @param string $key Environment variable name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

loadEnv();

?>
