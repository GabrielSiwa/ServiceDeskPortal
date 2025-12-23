<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function runMigrations() {
    // Debug: Print environment variables
    echo "=== DATABASE CONNECTION DEBUG ===\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_PORT: " . DB_PORT . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DB_PASS: " . (DB_PASS ? '***' : 'EMPTY') . "\n";
    echo "===================================\n\n";

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "[SUCCESS] Connected to MySQL server\n";

        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        echo "[SUCCESS] Database created/exists\n";

        // Use the database
        $pdo->exec("USE " . DB_NAME);

        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                role ENUM('admin', 'tech') DEFAULT 'tech',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "[SUCCESS] Users table created/exists\n";

        // Create tickets table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tickets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                status ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',
                priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                user_id INT NOT NULL,
                assigned_to INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (assigned_to) REFERENCES users(id)
            )
        ");
        echo "[SUCCESS] Tickets table created/exists\n";

        echo "Database migrations completed successfully!\n";
    } catch (PDOException $e) {
        echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (php_sapi_name() === 'cli') {
    runMigrations();
}