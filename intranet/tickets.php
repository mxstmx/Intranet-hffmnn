<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require __DIR__ . '/config.php';
$username = htmlspecialchars($_SESSION['username']);

$tickets = $pdo->query('SELECT id, title, status, assigned_to FROM tickets ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <title>Tickets - Hoffmann Intranet</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/theme.min.css" />
</head>
<body>
<?php include 'menu.php'; ?>
<main class="nxl-container">
    <div class="nxl-content">
        <div class="container mt-4">
            <div class="d-flex justify-content-between mb-3">
                <h2>Tickets</h2>
                <a href="ticket_form.php" class="btn btn-primary">Neues Ticket</a>
            </div>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Titel</th><th>Status</th><th>Zugewiesen an</th><th>Aktionen</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><?php echo $t['id']; ?></td>
                        <td><?php echo htmlspecialchars($t['title']); ?></td>
                        <td><?php echo htmlspecialchars($t['status']); ?></td>
                        <td><?php echo htmlspecialchars($t['assigned_to']); ?></td>
                        <td>
                            <a href="ticket_form.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <a href="ticket_delete.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ticket wirklich löschen?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tickets): ?>
                    <tr><td colspan="5" class="text-center">Keine Tickets vorhanden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer">
        <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
            <span>Copyright ©</span>
            <script>document.write(new Date().getFullYear());</script>
        </p>
    </footer>
</main>
<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>
