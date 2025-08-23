<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gruppe = trim($_POST['gruppe'] ?? '');
    $artikelnummer = trim($_POST['artikelnummer'] ?? '');
    $artikel = trim($_POST['artikel'] ?? '');
    $bestand = (int)($_POST['bestand'] ?? 0);
    $reserviert = (int)($_POST['reserviert'] ?? 0);
    $bestellt = (int)($_POST['bestellt'] ?? 0);
    $info = trim($_POST['information'] ?? '');
    if ($artikel) {
        $stmt = $pdo->prepare('INSERT INTO bestand (gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information) VALUES (:gruppe, :artikelnummer, :artikel, :bestand, :reserviert, :bestellt, :information)');
        $stmt->execute([
            ':gruppe' => $gruppe,
            ':artikelnummer' => $artikelnummer,
            ':artikel' => $artikel,
            ':bestand' => $bestand,
            ':reserviert' => $reserviert,
            ':bestellt' => $bestellt,
            ':information' => $info
        ]);
    }
}
$items = $pdo->query('SELECT id, gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information FROM bestand')->fetchAll(PDO::FETCH_ASSOC);
if (!$items) {
    $file = __DIR__ . '/json/bestand.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/bestand.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO bestand (gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information) VALUES (:gruppe, :artikelnummer, :artikel, :bestand, :reserviert, :bestellt, :information)');
        foreach ($data as $code => $group) {
            $groupName = $group['Warengruppenbezeichnung'] ?? $code;
            if (!empty($group['Artikel']) && is_array($group['Artikel'])) {
                foreach ($group['Artikel'] as $row) {
                    $stmt->execute([
                        ':gruppe' => $groupName,
                        ':artikelnummer' => $row['Artikelnummer'] ?? '',
                        ':artikel' => $row['Artikelbezeichnung'] ?? '',
                        ':bestand' => (int)($row['Bestand'] ?? 0),
                        ':reserviert' => (int)($row['Reserviert'] ?? 0),
                        ':bestellt' => (int)($row['Bestellt'] ?? 0),
                        ':information' => $row['Information'] ?? ''
                    ]);
                }
            }
        }
        $items = $pdo->query('SELECT id, gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information FROM bestand')->fetchAll(PDO::FETCH_ASSOC);
    }
}

$filterGruppe = trim($_GET['gruppe'] ?? '');
$filterArtikel = trim($_GET['artikel'] ?? '');
if ($filterGruppe || $filterArtikel) {
    $items = array_filter($items, function ($i) use ($filterGruppe, $filterArtikel) {
        return (!$filterGruppe || stripos($i['gruppe'], $filterGruppe) !== false)
            && (!$filterArtikel || stripos($i['artikel'], $filterArtikel) !== false);
    });
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestand - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Bestand</h2>
    <form method="POST" class="row g-2 mb-4">
        <div class="col-md-2">
            <input type="text" name="gruppe" class="form-control" placeholder="Warengruppe">
        </div>
        <div class="col-md-2">
            <input type="text" name="artikelnummer" class="form-control" placeholder="Artikelnummer">
        </div>
        <div class="col-md">
            <input type="text" name="artikel" class="form-control" placeholder="Artikel" required>
        </div>
        <div class="col-md-1">
            <input type="number" name="bestand" class="form-control" placeholder="Bestand" required>
        </div>
        <div class="col-md-1">
            <input type="number" name="reserviert" class="form-control" placeholder="Reserviert">
        </div>
        <div class="col-md-1">
            <input type="number" name="bestellt" class="form-control" placeholder="Bestellt">
        </div>
        <div class="col-md">
            <input type="text" name="information" class="form-control" placeholder="Info">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md">
            <input type="text" name="gruppe" class="form-control" placeholder="Warengruppe filtern" value="<?php echo htmlspecialchars($filterGruppe); ?>">
        </div>
        <div class="col-md">
            <input type="text" name="artikel" class="form-control" placeholder="Artikel filtern" value="<?php echo htmlspecialchars($filterArtikel); ?>">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-secondary" type="submit">Filtern</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Warengruppe</th><th>Artikelnummer</th><th>Artikel</th><th>Bestand</th><th>Reserviert</th><th>Bestellt</th><th>Info</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['gruppe']); ?></td>
                <td><?php echo htmlspecialchars($item['artikelnummer']); ?></td>
                <td><?php echo htmlspecialchars($item['artikel']); ?></td>
                <td><?php echo $item['bestand']; ?></td>
                <td><?php echo $item['reserviert']; ?></td>
                <td><?php echo $item['bestellt']; ?></td>
                <td><?php echo htmlspecialchars($item['information']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
