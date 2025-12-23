<?php

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';

$passwords = [
    'admin' => 'Admin123!',
    'tech1' => 'Tech123!'
];

foreach ($passwords as $username => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    Database::execute(
        'UPDATE users SET password_hash = ? WHERE username = ?',
        [$hash, $username]
    );
    
    echo "✓ Updated $username password\n";
}

echo "\nPassworde reset complete. Try logging in.\n";

?>
