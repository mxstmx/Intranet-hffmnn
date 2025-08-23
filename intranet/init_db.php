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

$pdo->exec('CREATE TABLE IF NOT EXISTS textbestellungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kunde TEXT NOT NULL,
    text TEXT NOT NULL
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

// Load orders from JSON if table is empty
if (!$pdo->query('SELECT 1 FROM bestellungen LIMIT 1')->fetch()) {
    $json = @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/bestellungen.json');
    if (!$json) {
        $file = __DIR__ . '/bestellungen.json';
        $json = file_exists($file) ? file_get_contents($file) : '';
    }
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO bestellungen (kunde, artikel, menge) VALUES (:kunde, :artikel, :menge)');
        foreach ($data as $row) {
            $stmt->execute([
                ':kunde' => $row['kunde'] ?? '',
                ':artikel' => $row['artikel'] ?? '',
                ':menge' => (int)($row['menge'] ?? 0)
            ]);
        }
    }
}

// Load open items from JSON if table is empty
if (!$pdo->query('SELECT 1 FROM offene_posten LIMIT 1')->fetch()) {
    $json = @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json');
    if (!$json) {
        $file = __DIR__ . '/offene_posten.json';
        $json = file_exists($file) ? file_get_contents($file) : '';
    }
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (beschreibung, betrag) VALUES (:beschreibung, :betrag)');
        foreach ($data as $row) {
            $stmt->execute([
                ':beschreibung' => $row['beschreibung'] ?? '',
                ':betrag' => (float)($row['betrag'] ?? 0)
            ]);
        }
    }
}

echo "Database initialized.\n";
