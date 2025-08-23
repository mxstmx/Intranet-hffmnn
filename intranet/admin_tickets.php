<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
require __DIR__ . '/config.php';

if (isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $assigned = $_POST['assigned_to'] ?? '';
    if ($title && $desc && $assigned) {
        $stmt = $pdo->prepare('INSERT INTO tickets (title, description, status, assigned_to, created_by, created_at) VALUES (:title, :description, :status, :assigned_to, :created_by, :created_at)');
        $stmt->execute([
            ':title' => $title,
            ':description' => $desc,
            ':status' => 'open',
            ':assigned_to' => $assigned,
            ':created_by' => $_SESSION['username'],
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'open';
    $stmt = $pdo->prepare('UPDATE tickets SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $status, ':id' => $id]);
}

$employees = $pdo->query("SELECT username FROM users WHERE role = 'mitarbeiter'")->fetchAll(PDO::FETCH_COLUMN);
$tickets = $pdo->query('SELECT id, title, description, status, assigned_to FROM tickets')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ticket-Admin - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2 class="mb-4">Ticketverwaltung</h2>
    <form method="POST" class="mb-4">
        <input type="hidden" name="create" value="1">
        <div class="mb-2">
            <input type="text" name="title" class="form-control" placeholder="Titel" required>
        </div>
        <div class="mb-2">
            <textarea name="description" class="form-control" placeholder="Beschreibung" required></textarea>
        </div>
        <div class="mb-2">
            <select name="assigned_to" class="form-select" required>
                <option value="">-- Mitarbeiter wählen --</option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?php echo htmlspecialchars($e); ?>"><?php echo htmlspecialchars($e); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Ticket erstellen</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Titel</th><th>Beschreibung</th><th>Mitarbeiter</th><th>Status</th><th>Aktion</th></tr></thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?php echo $t['id']; ?></td>
                <td><?php echo htmlspecialchars($t['title']); ?></td>
                <td><?php echo htmlspecialchars($t['description']); ?></td>
                <td><?php echo htmlspecialchars($t['assigned_to']); ?></td>
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
