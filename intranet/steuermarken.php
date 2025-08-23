<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare('INSERT INTO steuermarken (name) VALUES (:name)');
        $stmt->execute([':name' => $name]);
    }
}
$marks = $pdo->query('SELECT id, name FROM steuermarken')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Steuermarken - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Steuermarken</h2>
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" name="name" class="form-control" placeholder="Bezeichnung" required>
            <button class="btn btn-primary" type="submit">Hinzufügen</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Name</th></tr></thead>
        <tbody>
            <?php foreach ($marks as $mark): ?>
            <tr><td><?php echo $mark['id']; ?></td><td><?php echo htmlspecialchars($mark['name']); ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
