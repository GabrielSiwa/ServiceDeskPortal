<?php
require __DIR__ . '/src/db.php';

// 1. Create schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
foreach (explode(';', $schema) as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        try {
            Database::connect()->exec($statement);
        } catch (Exception $e) {
            // Ignore errors (e.g., table already exists)
            echo "[Schema] " . $e->getMessage() . "\n";
        }
    }
}
echo "Schema created\n";

// 2. Seed users
require __DIR__ . '/src/seed.php';
