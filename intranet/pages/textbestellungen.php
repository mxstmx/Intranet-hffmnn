<?php
require __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kunde = $_SESSION['role'] === 'kunde' ? $_SESSION['username'] : trim($_POST['kunde'] ?? '');
    $text  = trim($_POST['text'] ?? '');
    if ($kunde && $text) {
        $stmt = $pdo->prepare('INSERT INTO textbestellungen (kunde, text) VALUES (:kunde, :text)');
        $stmt->execute([':kunde' => $kunde, ':text' => $text]);
    }
}
$entries = $pdo->query('SELECT id, kunde, text FROM textbestellungen ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Textbestellung</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Textbestellung</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
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
            <?php if (!$entries): ?>
                <tr><td colspan="3" class="text-center">Keine Einträge.</td></tr>
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
