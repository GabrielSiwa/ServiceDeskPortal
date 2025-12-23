<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Database connection and query execution utility
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get or create PDO connection
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $mysqlUrl = env('MYSQL_URL');
        if ($mysqlUrl) {
            $parsed = parse_url($mysqlUrl);
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $parsed['host'] ?? 'localhost',
                $parsed['port'] ?? 3306,
                ltrim($parsed['path'] ?? '/service_desk', '/')
            );
            $user = $parsed['user'] ?? 'root';
            $pass = $parsed['pass'] ?? '';
            self::$pdo = new PDO($dsn, $user, $pass);
        } else {
            // Fallback to individual variables for local dev
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                env('MYSQLHOST', 'localhost'),
                env('MYSQLPORT', 3306),
                env('MYSQLDATABASE', 'service_desk')
            );
            self::$pdo = new PDO($dsn, env('MYSQLUSER', 'root'), env('MYSQLPASSWORD', ''));
        }

        return self::$pdo;
    }

    /**
     * Execute prepared statement
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Bind parameters
     * @return PDOStatement
     * @throws PDOException
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch single row
     * 
     * @param string $sql SQL query
     * @param array $params Bind parameters
     * @return array|false
     */
    public static function fetch(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Bind parameters
     * @return array
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert record and return ID
     * 
     * @param string $sql INSERT statement
     * @param array $params Bind parameters
     * @return string Last inserted ID
     */
    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::connect()->lastInsertId();
    }

    /**
     * Execute UPDATE/DELETE and return affected rows
     * 
     * @param string $sql SQL statement
     * @param array $params Bind parameters
     * @return int Rows affected
     */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }
}

?>
