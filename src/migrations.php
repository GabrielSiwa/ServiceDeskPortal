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
                password_hash VARCHAR(255) NOT NULL,
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

        // Create assets table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS assets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                asset_type VARCHAR(50) NOT NULL,
                serial_number VARCHAR(100) UNIQUE,
                location VARCHAR(100),
                status ENUM('active', 'inactive', 'retired') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "[SUCCESS] Assets table created/exists\n";

        // Seed demo users
        try {
            $pdo->exec("
                INSERT INTO users (username, email, password_hash, role) VALUES
                ('admin', 'admin@servicedesk.local', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DxYXte', 'admin'),
                ('tech1', 'tech1@servicedesk.local', '\$2y\$10\$V4ODQv6m7yRFQa0/pxLsHeJ8qb3d4KwDYaVxVSc9PZVbn5XdiK742', 'tech')
                ON DUPLICATE KEY UPDATE id=id
            ");
            echo "[SUCCESS] Demo users seeded\n";
        } catch (PDOException $e) {
            echo "[WARNING] Could not seed users: " . $e->getMessage() . "\n";
        }

        // Seed demo assets
        try {
            $pdo->exec("
                INSERT INTO assets (name, asset_type, serial_number, location, status) VALUES
                ('Office Printer', 'printer', 'PRN-001-2024', 'Floor 1 - Reception', 'active'),
                ('Server Rack', 'server', 'SRV-042-2024', 'Server Room', 'active'),
                ('Dell Laptop', 'computer', 'DLL-567-2024', 'Desk 5', 'active')
                ON DUPLICATE KEY UPDATE id=id
            ");
            echo "[SUCCESS] Demo assets seeded\n";
        } catch (PDOException $e) {
            echo "[WARNING] Could not seed assets: " . $e->getMessage() . "\n";
        }

        // Seed demo tickets
        try {
            $pdo->exec("
                INSERT INTO tickets (title, description, priority, status, user_id, assigned_to) VALUES
                ('Printer jam in reception', 'Paper jam in office printer, needs clearing', 'medium', 'open', 1, 2),
                ('Server backup verification', 'Check last backup logs and verify integrity', 'high', 'in_progress', 1, 2),
                ('Laptop not starting', 'Dell laptop at desk 5 will not power on', 'high', 'open', 1, NULL)
                ON DUPLICATE KEY UPDATE id=id
            ");
            echo "[SUCCESS] Demo tickets seeded\n";
        } catch (PDOException $e) {
            echo "[WARNING] Could not seed tickets: " . $e->getMessage() . "\n";
        }

        echo "Database migrations completed successfully!\n";
    } catch (PDOException $e) {
        echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (php_sapi_name() === 'cli') {
    runMigrations();
}