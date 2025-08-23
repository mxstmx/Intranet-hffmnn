<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_name'])) {
        $name = trim($_POST['add_name']);
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO steuermarken (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
        }
    }
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare('DELETE FROM steuermarken WHERE id = :id');
        $stmt->execute([':id' => (int)$_POST['delete_id']]);
    }
}
$marks = $pdo->query('SELECT id, name FROM steuermarken ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Steuermarken</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Steuermarken</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <form method="post" class="row g-2 mb-3">
            <div class="col-md-8">
                <input type="text" name="add_name" class="form-control" placeholder="Neue Steuermarke" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Hinzufügen</button>
            </div>
        </form>
        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Name</th><th>Aktion</th></tr></thead>
            <tbody>
            <?php foreach ($marks as $m): ?>
                <tr>
                    <td><?php echo $m['id']; ?></td>
                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Steuermarke löschen?');" style="display:inline">
                            <input type="hidden" name="delete_id" value="<?php echo $m['id']; ?>">
                            <button class="btn btn-sm btn-danger">Löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$marks): ?>
                <tr><td colspan="3" class="text-center">Keine Steuermarken vorhanden.</td></tr>
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
