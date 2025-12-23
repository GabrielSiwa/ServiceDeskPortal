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
            // Ignore errors (e.g., table already exists, duplicate inserts)
            // Only log critical errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "[Schema] " . $e->getMessage() . "\n";
            }
        }
    }
}
echo "Schema ready\n";

// 2. Seed users
require __DIR__ . '/src/seed.php';
