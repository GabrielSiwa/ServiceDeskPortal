<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Authentication and session management
 */
class Auth
{
    /**\n     * Start session with configured name
     * 
     * @return void
     */
    public static function start(): void
    {
        session_name(SESSION_NAME);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    /**
     * Get current logged-in user data
     * 
     * @return array|null User array with id, username, role or null
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
        ];
    }

    /**
     * Authenticate user with username and password
     * 
     * @param string $username Username
     * @param string $password Plain password\n     * @return bool True if authentication successful
     */
    public static function login(string $username, string $password): bool
    {
        $user = Database::fetch(
            'SELECT id, username, password_hash, role FROM users WHERE username = ?',
            [$username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        return true;
    }

    /**
     * Destroy session (logout)
     * 
     * @return void
     */
    public static function logout(): void
    {
        session_destroy();
    }

    /**
     * Require login (redirect to login if not authenticated)
     * 
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /index.php?action=login');
            exit;
        }
    }

    /**
     * Require specific role (403 if role mismatch)
     * 
     * @param string $role Required role\n     * @return void
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();
        $user = self::user();
        if ($user['role'] !== $role) {
            http_response_code(403);
            die('Access denied.');
        }
    }

    /**
     * Generate CSRF token for session
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token (timing-safe comparison)
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Escape HTML special characters for output
     * 
     * @param mixed $data Data to escape
     * @return string Escaped string
     */
    public static function validateInput(mixed $data): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
}

Auth::start();

?>
