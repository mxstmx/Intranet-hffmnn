<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kunde = $_SESSION['role'] === 'kunde' ? $_SESSION['username'] : trim($_POST['kunde'] ?? '');
    $artikel = trim($_POST['artikel'] ?? '');
    $menge = (int)($_POST['menge'] ?? 0);
    if ($kunde && $artikel && $menge > 0) {
        $stmt = $pdo->prepare('INSERT INTO bestellungen (kunde, artikel, menge) VALUES (:kunde, :artikel, :menge)');
        $stmt->execute([
            ':kunde' => $kunde,
            ':artikel' => $artikel,
            ':menge' => $menge
        ]);
    }
}
$orders = $pdo->query('SELECT id, kunde, artikel, menge FROM bestellungen')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellungen - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Bestellungen</h2>
    <form method="POST" class="row g-2 mb-4">
        <?php if ($_SESSION['role'] !== 'kunde'): ?>
            <div class="col-md">
                <input type="text" name="kunde" class="form-control" placeholder="Kunde" required>
            </div>
        <?php endif; ?>
        <div class="col-md">
            <input type="text" name="artikel" class="form-control" placeholder="Artikel" required>
        </div>
        <div class="col-md">
            <input type="number" name="menge" class="form-control" placeholder="Menge" required>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Kunde</th><th>Artikel</th><th>Menge</th></tr></thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['kunde']); ?></td>
                <td><?php echo htmlspecialchars($order['artikel']); ?></td>
                <td><?php echo $order['menge']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
