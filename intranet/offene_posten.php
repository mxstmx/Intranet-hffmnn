<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kunde = trim($_POST['kunde'] ?? '');
    $rechnungsnr = trim($_POST['rechnungsnr'] ?? '');
    $datum = trim($_POST['datum'] ?? '');
    $produkttyp = trim($_POST['produkttyp'] ?? '');
    $betrag = (float)($_POST['betrag_gesamt'] ?? 0);
    $bisher = (float)($_POST['bisher_gezahlt'] ?? 0);
    $noch = (float)($_POST['noch_zu_zahlen'] ?? 0);
    if ($kunde && $rechnungsnr) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (kunde, rechnungsnr, datum, produkttyp, betrag_gesamt, bisher_gezahlt, noch_zu_zahlen) VALUES (:kunde, :rechnungsnr, :datum, :produkttyp, :betrag, :bisher, :noch)');
        $stmt->execute([
            ':kunde' => $kunde,
            ':rechnungsnr' => $rechnungsnr,
            ':datum' => $datum,
            ':produkttyp' => $produkttyp,
            ':betrag' => $betrag,
            ':bisher' => $bisher,
            ':noch' => $noch
        ]);
    }
}
$items = $pdo->query('SELECT id, kunde, rechnungsnr, datum, produkttyp, betrag_gesamt, bisher_gezahlt, noch_zu_zahlen FROM offene_posten')->fetchAll(PDO::FETCH_ASSOC);
if (!$items) {
    $file = __DIR__ . '/json/offene_posten.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO offene_posten (kunde, rechnungsnr, datum, produkttyp, betrag_gesamt, bisher_gezahlt, noch_zu_zahlen) VALUES (:kunde, :rechnungsnr, :datum, :produkttyp, :betrag, :bisher, :noch)');
        foreach ($data as $kunde => $rows) {
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $stmt->execute([
                        ':kunde' => $kunde,
                        ':rechnungsnr' => $row['Rechnungsnr'] ?? '',
                        ':datum' => $row['Datum'] ?? '',
                        ':produkttyp' => $row['Produkttyp'] ?? '',
                        ':betrag' => (float)($row['Betrag Gesamt'] ?? 0),
                        ':bisher' => (float)($row['Bisher gezahlt'] ?? 0),
                        ':noch' => (float)($row['Noch zu zahlen'] ?? 0)
                    ]);
                }
            }
        }
        $items = $pdo->query('SELECT id, kunde, rechnungsnr, datum, produkttyp, betrag_gesamt, bisher_gezahlt, noch_zu_zahlen FROM offene_posten')->fetchAll(PDO::FETCH_ASSOC);
    }
}

$filterKunde = trim($_GET['kunde'] ?? '');
$filterProdukttyp = trim($_GET['produkttyp'] ?? '');
$minBetrag = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$maxBetrag = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
if ($filterKunde || $filterProdukttyp || $minBetrag !== null || $maxBetrag !== null) {
    $items = array_filter($items, function ($i) use ($filterKunde, $filterProdukttyp, $minBetrag, $maxBetrag) {
        return (!$filterKunde || stripos($i['kunde'], $filterKunde) !== false)
            && (!$filterProdukttyp || stripos($i['produkttyp'], $filterProdukttyp) !== false)
            && ($minBetrag === null || $i['betrag_gesamt'] >= $minBetrag)
            && ($maxBetrag === null || $i['betrag_gesamt'] <= $maxBetrag);
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
            <input type="text" name="kunde" class="form-control" placeholder="Kunde" required>
        </div>
        <div class="col-md">
            <input type="text" name="rechnungsnr" class="form-control" placeholder="Rechnungsnr" required>
        </div>
        <div class="col-md">
            <input type="date" name="datum" class="form-control" placeholder="Datum">
        </div>
        <div class="col-md">
            <input type="text" name="produkttyp" class="form-control" placeholder="Produkttyp">
        </div>
        <div class="col-md-1">
            <input type="number" step="0.01" name="betrag_gesamt" class="form-control" placeholder="Betrag">
        </div>
        <div class="col-md-1">
            <input type="number" step="0.01" name="bisher_gezahlt" class="form-control" placeholder="Gezahlt">
        </div>
        <div class="col-md-1">
            <input type="number" step="0.01" name="noch_zu_zahlen" class="form-control" placeholder="Offen">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md">
            <input type="text" name="kunde" class="form-control" placeholder="Kunde filtern" value="<?php echo htmlspecialchars($filterKunde); ?>">
        </div>
        <div class="col-md">
            <input type="text" name="produkttyp" class="form-control" placeholder="Produkttyp filtern" value="<?php echo htmlspecialchars($filterProdukttyp); ?>">
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
        <thead><tr><th>ID</th><th>Kunde</th><th>Rechnungsnr</th><th>Datum</th><th>Produkttyp</th><th>Betrag Gesamt</th><th>Bisher gezahlt</th><th>Noch zu zahlen</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['kunde']); ?></td>
                <td><?php echo htmlspecialchars($item['rechnungsnr']); ?></td>
                <td><?php echo htmlspecialchars($item['datum']); ?></td>
                <td><?php echo htmlspecialchars($item['produkttyp']); ?></td>
                <td><?php echo number_format($item['betrag_gesamt'], 2, ',', '.'); ?> €</td>
                <td><?php echo number_format($item['bisher_gezahlt'], 2, ',', '.'); ?> €</td>
                <td><?php echo number_format($item['noch_zu_zahlen'], 2, ',', '.'); ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
