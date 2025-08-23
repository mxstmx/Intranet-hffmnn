<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artikel = trim($_POST['artikel'] ?? '');
    $bestand = (int)($_POST['bestand'] ?? 0);
    if ($artikel && $bestand >= 0) {
        $stmt = $pdo->prepare('INSERT INTO bestand (artikel, bestand) VALUES (:artikel, :bestand)');
        $stmt->execute([
            ':artikel' => $artikel,
            ':bestand' => $bestand
        ]);
    }
}
$items = $pdo->query('SELECT id, artikel, bestand FROM bestand')->fetchAll(PDO::FETCH_ASSOC);
if (!$items) {
    $file = __DIR__ . '/json/bestand.json';
    $json = file_exists($file) ? file_get_contents($file) : @file_get_contents('https://dashboard.hoffmann-hd.de/wp-content/uploads/json/bestand.json');
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stmt = $pdo->prepare('INSERT INTO bestand (artikel, bestand) VALUES (:artikel, :bestand)');
        foreach ($data as $row) {
            $stmt->execute([
                ':artikel' => $row['artikel'] ?? '',
                ':bestand' => (int)($row['bestand'] ?? 0)
            ]);
        }
        $items = $pdo->query('SELECT id, artikel, bestand FROM bestand')->fetchAll(PDO::FETCH_ASSOC);
    }
}

$filterArtikel = trim($_GET['artikel'] ?? '');
if ($filterArtikel) {
    $items = array_filter($items, function ($i) use ($filterArtikel) {
        return stripos($i['artikel'], $filterArtikel) !== false;
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
        <div class="col-md">
            <input type="text" name="artikel" class="form-control" placeholder="Artikel" required>
        </div>
        <div class="col-md">
            <input type="number" name="bestand" class="form-control" placeholder="Bestand" required>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md">
            <input type="text" name="artikel" class="form-control" placeholder="Artikel filtern" value="<?php echo htmlspecialchars($filterArtikel); ?>">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-secondary" type="submit">Filtern</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Artikel</th><th>Bestand</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['artikel']); ?></td>
                <td><?php echo $item['bestand']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
