<?php
require __DIR__ . '/config.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL
)');

$pdo->exec('DROP TABLE IF EXISTS steuermarken');
$pdo->exec('CREATE TABLE steuermarken (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    warenwert_gesamt REAL DEFAULT 0,
    wert_je_marke REAL DEFAULT 0,
    datum TEXT,
    anzahl INTEGER DEFAULT 0
)');
$pdo->exec("INSERT INTO steuermarken (id,name,warenwert_gesamt,wert_je_marke,datum,anzahl) VALUES
    (1,'19%',0,0,DATE('now'),0),
    (2,'7%',0,0,DATE('now'),0)");

$pdo->exec('DROP TABLE IF EXISTS bestand');
$pdo->exec('CREATE TABLE bestand (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gruppe TEXT,
    artikelnummer TEXT,
    artikel TEXT,
    bestand INTEGER,
    reserviert INTEGER,
    bestellt INTEGER,
    information TEXT
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    status TEXT NOT NULL,
    assigned_to TEXT NOT NULL,
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL
)');

$pdo->exec('DROP TABLE IF EXISTS bestellungen');
$pdo->exec('CREATE TABLE bestellungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    belegnummer TEXT,
    belegdatum TEXT,
    belegart TEXT,
    vorbelegnummer TEXT,
    betreff TEXT,
    betrag REAL,
    steuermarke_id INTEGER,
    steuermarke_qty INTEGER DEFAULT 0,
    zoll_eur REAL DEFAULT 0,
    aircargo_usd REAL DEFAULT 0,
    wechselkurs REAL DEFAULT 1
)');

$pdo->exec('DROP TABLE IF EXISTS offene_posten');
$pdo->exec('CREATE TABLE offene_posten (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kunde TEXT,
    rechnungsnr TEXT,
    datum TEXT,
    produkttyp TEXT,
    betrag_gesamt REAL,
    bisher_gezahlt REAL,
    noch_zu_zahlen REAL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS textbestellungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kunde TEXT NOT NULL,
    text TEXT NOT NULL
)');

// Insert initial users if not present
$insert = $pdo->prepare('INSERT OR IGNORE INTO users (username, password, role) VALUES (:username, :password, :role)');
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

// Load customers from JSON
$json = @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/kunden.json');
if (!$json) {
    $file = __DIR__ . '/kunden.json';
    $json = file_exists($file) ? file_get_contents($file) : '';
}
$customers = json_decode($json, true);
if (is_array($customers)) {
    foreach ($customers as $c) {
        if (!empty($c['username']) && !empty($c['password'])) {
            $insert->execute([
                ':username' => $c['username'],
                ':password' => password_hash($c['password'], PASSWORD_DEFAULT),
                ':role' => $c['role'] ?? 'kunde'
            ]);
        }
    }
}

// Load orders from JSON if table is empty
if (!$pdo->query('SELECT 1 FROM bestellungen LIMIT 1')->fetch()) {
    $file = __DIR__ . '/json/bestellungen.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/bestellungen.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO bestellungen (belegnummer, belegdatum, belegart, vorbelegnummer, betreff, betrag, zoll_eur, aircargo_usd, wechselkurs) VALUES (:belegnummer, :belegdatum, :belegart, :vorbelegnummer, :betreff, :betrag, 0, 0, 1)');
        foreach ($data as $row) {
            $meta = $row['Metadaten'] ?? [];
            $stmt->execute([
                ':belegnummer'    => $row['Belegnummer'] ?? '',
                ':belegdatum'     => $meta['Belegdatum'] ?? '',
                ':belegart'       => $meta['Belegart'] ?? '',
                ':vorbelegnummer' => $meta['Vorbelegnummer'] ?? '',
                ':betreff'        => $meta['Betreff'] ?? '',
                ':betrag'         => (float)($meta['BetragNetto'] ?? 0)
            ]);
        }
    }
}

// Load open items from JSON if table is empty
if (!$pdo->query('SELECT 1 FROM offene_posten LIMIT 1')->fetch()) {
    $file = __DIR__ . '/json/offene_posten.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (kunde, rechnungsnr, datum, produkttyp, betrag_gesamt, bisher_gezahlt, noch_zu_zahlen) VALUES (:kunde, :rechnungsnr, :datum, :produkttyp, :betrag_gesamt, :bisher_gezahlt, :noch_zu_zahlen)');
        foreach ($data as $kunde => $rows) {
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $stmt->execute([
                        ':kunde' => $kunde,
                        ':rechnungsnr' => $row['Rechnungsnr'] ?? '',
                        ':datum' => $row['Datum'] ?? '',
                        ':produkttyp' => $row['Produkttyp'] ?? '',
                        ':betrag_gesamt' => (float)($row['Betrag Gesamt'] ?? 0),
                        ':bisher_gezahlt' => (float)($row['Bisher gezahlt'] ?? 0),
                        ':noch_zu_zahlen' => (float)($row['Noch zu zahlen'] ?? 0)
                    ]);
                }
            }
        }
    }
}

// Load inventory from JSON if table is empty
if (!$pdo->query('SELECT 1 FROM bestand LIMIT 1')->fetch()) {
    $file = __DIR__ . '/json/bestand.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/bestand.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO bestand (gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information) VALUES (:gruppe, :artikelnummer, :artikel, :bestand, :reserviert, :bestellt, :information)');
        foreach ($data as $groupCode => $group) {
            $groupName = $group['Warengruppenbezeichnung'] ?? $groupCode;
            if (!empty($group['Artikel']) && is_array($group['Artikel'])) {
                foreach ($group['Artikel'] as $item) {
                    $stmt->execute([
                        ':gruppe' => $groupName,
                        ':artikelnummer' => $item['Artikelnummer'] ?? '',
                        ':artikel' => $item['Artikelbezeichnung'] ?? '',
                        ':bestand' => (int)($item['Bestand'] ?? 0),
                        ':reserviert' => (int)($item['Reserviert'] ?? 0),
                        ':bestellt' => (int)($item['Bestellt'] ?? 0),
                        ':information' => $item['Information'] ?? ''
                    ]);
                }
            }
        }
    }
}

echo "Database initialized.\n";
