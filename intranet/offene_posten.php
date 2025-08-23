<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beschreibung = trim($_POST['beschreibung'] ?? '');
    $betrag = (float)($_POST['betrag'] ?? 0);
    if ($beschreibung && $betrag > 0) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (beschreibung, betrag) VALUES (:beschreibung, :betrag)');
        $stmt->execute([
            ':beschreibung' => $beschreibung,
            ':betrag' => $betrag
        ]);
    }
}
$items = $pdo->query('SELECT id, beschreibung, betrag FROM offene_posten')->fetchAll(PDO::FETCH_ASSOC);
if (!$items) {
    $file = __DIR__ . '/json/offene_posten.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (beschreibung, betrag) VALUES (:beschreibung, :betrag)');
        foreach ($data as $row) {
            $stmt->execute([
                ':beschreibung' => $row['beschreibung'] ?? '',
                ':betrag' => (float)($row['betrag'] ?? 0)
            ]);
        }
        $items = $pdo->query('SELECT id, beschreibung, betrag FROM offene_posten')->fetchAll(PDO::FETCH_ASSOC);
    }
}

$filterBeschreibung = trim($_GET['beschreibung'] ?? '');
$minBetrag = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$maxBetrag = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
if ($filterBeschreibung || $minBetrag !== null || $maxBetrag !== null) {
    $items = array_filter($items, function ($i) use ($filterBeschreibung, $minBetrag, $maxBetrag) {
        return (!$filterBeschreibung || stripos($i['beschreibung'], $filterBeschreibung) !== false)
            && ($minBetrag === null || $i['betrag'] >= $minBetrag)
            && ($maxBetrag === null || $i['betrag'] <= $maxBetrag);
    });
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Offene Posten - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Offene Posten</h2>
    <form method="POST" class="row g-2 mb-4">
        <div class="col-md">
            <input type="text" name="beschreibung" class="form-control" placeholder="Beschreibung" required>
        </div>
        <div class="col-md">
            <input type="number" step="0.01" name="betrag" class="form-control" placeholder="Betrag" required>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md">
            <input type="text" name="beschreibung" class="form-control" placeholder="Beschreibung filtern" value="<?php echo htmlspecialchars($filterBeschreibung); ?>">
        </div>
        <div class="col-md">
            <input type="number" step="0.01" name="min" class="form-control" placeholder="Min Betrag" value="<?php echo $minBetrag !== null ? htmlspecialchars($minBetrag) : ''; ?>">
        </div>
        <div class="col-md">
            <input type="number" step="0.01" name="max" class="form-control" placeholder="Max Betrag" value="<?php echo $maxBetrag !== null ? htmlspecialchars($maxBetrag) : ''; ?>">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-secondary" type="submit">Filtern</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Beschreibung</th><th>Betrag</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['beschreibung']); ?></td>
                <td><?php echo number_format($item['betrag'], 2, ',', '.'); ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
