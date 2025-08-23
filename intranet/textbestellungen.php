<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kunde = $_SESSION['role'] === 'kunde' ? $_SESSION['username'] : trim($_POST['kunde'] ?? '');
    $text = trim($_POST['text'] ?? '');
    if ($kunde && $text) {
        $stmt = $pdo->prepare('INSERT INTO textbestellungen (kunde, text) VALUES (:kunde, :text)');
        $stmt->execute([
            ':kunde' => $kunde,
            ':text' => $text
        ]);
    }
}

$entries = $pdo->query('SELECT id, kunde, text FROM textbestellungen')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Textbestellung - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Textbestellung</h2>
    <form method="POST" class="row g-2 mb-4">
        <?php if ($_SESSION['role'] !== 'kunde'): ?>
            <div class="col-md">
                <input type="text" name="kunde" class="form-control" placeholder="Kunde" required>
            </div>
        <?php endif; ?>
        <div class="col-md">
            <input type="text" name="text" class="form-control" placeholder="Bestellungstext" required>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Kunde</th><th>Text</th></tr></thead>
        <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
                <td><?php echo $e['id']; ?></td>
                <td><?php echo htmlspecialchars($e['kunde']); ?></td>
                <td><?php echo htmlspecialchars($e['text']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
