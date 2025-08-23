<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

$username = $_SESSION['username'];

if (isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title && $desc) {
        $stmt = $pdo->prepare('INSERT INTO tickets (title, description, status, assigned_to, created_by, created_at) VALUES (:title, :description, :status, :assigned_to, :created_by, :created_at)');
        $stmt->execute([
            ':title' => $title,
            ':description' => $desc,
            ':status' => 'open',
            ':assigned_to' => $username,
            ':created_by' => $username,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'open';
    $stmt = $pdo->prepare('UPDATE tickets SET status = :status WHERE id = :id AND assigned_to = :user');
    $stmt->execute([':status' => $status, ':id' => $id, ':user' => $username]);
}

$tickets = $pdo->prepare('SELECT id, title, description, status FROM tickets WHERE assigned_to = :user');
$tickets->execute([':user' => $username]);
$items = $tickets->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Tickets - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Meine Tickets</h2>
    <form method="POST" class="mb-4">
        <input type="hidden" name="create" value="1">
        <div class="mb-2">
            <input type="text" name="title" class="form-control" placeholder="Titel" required>
        </div>
        <div class="mb-2">
            <textarea name="description" class="form-control" placeholder="Beschreibung" required></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Ticket erstellen</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Titel</th><th>Beschreibung</th><th>Status</th><th>Aktion</th></tr></thead>
        <tbody>
            <?php foreach ($items as $t): ?>
            <tr>
                <td><?php echo $t['id']; ?></td>
                <td><?php echo htmlspecialchars($t['title']); ?></td>
                <td><?php echo htmlspecialchars($t['description']); ?></td>
                <td><?php echo htmlspecialchars($t['status']); ?></td>
                <td>
                    <form method="POST" class="d-flex">
                        <input type="hidden" name="update" value="1">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <select name="status" class="form-select form-select-sm me-2">
                            <option value="open" <?php if($t['status']==='open') echo 'selected'; ?>>Offen</option>
                            <option value="in_progress" <?php if($t['status']==='in_progress') echo 'selected'; ?>>In Bearbeitung</option>
                            <option value="done" <?php if($t['status']==='done') echo 'selected'; ?>>Erledigt</option>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">Speichern</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
