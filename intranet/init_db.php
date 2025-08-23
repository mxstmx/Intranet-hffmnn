<?php
require __DIR__ . '/config.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL
)');

// Insert initial users if not present
$insert = $pdo->prepare('INSERT OR IGNORE INTO users (username, password, role) VALUES (:username, :password, :role)');
$insert->execute([
    ':username' => 'kunde1',
    ':password' => password_hash('pass123', PASSWORD_DEFAULT),
    ':role' => 'kunde'
]);
$insert->execute([
    ':username' => 'mitarbeiter1',
    ':password' => password_hash('admin456', PASSWORD_DEFAULT),
    ':role' => 'mitarbeiter'
]);

echo "Database initialized.\n";
