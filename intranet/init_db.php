<?php
require __DIR__ . '/config.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS steuermarken (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS bestellungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kunde TEXT NOT NULL,
    artikel TEXT NOT NULL,
    menge INTEGER NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS offene_posten (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beschreibung TEXT NOT NULL,
    betrag REAL NOT NULL
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
$insert->execute([
    ':username' => 'admin',
    ':password' => password_hash('admin', PASSWORD_DEFAULT),
    ':role' => 'admin'
]);

echo "Database initialized.\n";
