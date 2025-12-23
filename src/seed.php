<?php
require __DIR__ . '/../src/db.php';

$adminPass = getenv('SEED_ADMIN_PASSWORD') ?: 'Admin123!';
$techPass  = getenv('SEED_TECH_PASSWORD')  ?: 'Tech123!';

$adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
$techHash  = password_hash($techPass, PASSWORD_DEFAULT);

Database::query(
  "INSERT INTO users (username, email, password_hash, role) VALUES
   ('admin','admin@servicedesk.local',?, 'admin'),
   ('tech1','tech1@servicedesk.local',?, 'tech')
   ON DUPLICATE KEY UPDATE
     email=VALUES(email),
     password_hash=COALESCE(users.password_hash, VALUES(password_hash)),
     role=VALUES(role)",
  [$adminHash, $techHash]
);

echo "Seeded users\n";
