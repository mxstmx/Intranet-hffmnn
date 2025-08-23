<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_name'])) {
        $name = trim($_POST['add_name']);
        $wg = (float)($_POST['add_warenwert'] ?? 0);
        $wj = (float)($_POST['add_wert_je'] ?? 0);
        $datum = $_POST['add_datum'] ?? null;
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO steuermarken (name, warenwert_gesamt, wert_je_marke, datum) VALUES (:name,:wg,:wj,:datum)');
            $stmt->execute([':name' => $name, ':wg' => $wg, ':wj' => $wj, ':datum' => $datum]);
        }
    } elseif (isset($_POST['action'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($_POST['action'] === 'update' && $id) {
            $name = trim($_POST['name'] ?? '');
            $wg = (float)($_POST['warenwert_gesamt'] ?? 0);
            $wj = (float)($_POST['wert_je_marke'] ?? 0);
            $datum = $_POST['datum'] ?? null;
            if ($name !== '') {
                $stmt = $pdo->prepare('UPDATE steuermarken SET name=:name, warenwert_gesamt=:wg, wert_je_marke=:wj, datum=:datum WHERE id=:id');
                $stmt->execute([':name'=>$name, ':wg'=>$wg, ':wj'=>$wj, ':datum'=>$datum, ':id'=>$id]);
            }
        } elseif ($_POST['action'] === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM steuermarken WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
    }
}
$marks = $pdo->query('SELECT id, name, warenwert_gesamt, wert_je_marke, datum FROM steuermarken ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
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
            <div class="col-md-3">
                <input type="text" name="add_name" class="form-control" placeholder="Name" required>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="add_warenwert" class="form-control" placeholder="Warenwert gesamt">
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="add_wert_je" class="form-control" placeholder="Wert je Marke">
            </div>
            <div class="col-md-2">
                <input type="date" name="add_datum" class="form-control">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" type="submit">Hinzufügen</button>
            </div>
        </form>
        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Name</th><th>Warenwert gesamt</th><th>Wert je Steuermarke</th><th>Datum</th><th>Aktion</th></tr></thead>
            <tbody>
            <?php foreach ($marks as $m): ?>
                <tr>
                    <td>
                        <?php echo $m['id']; ?>
                        <input type="hidden" name="id" form="sm<?php echo $m['id']; ?>" value="<?php echo $m['id']; ?>">
                    </td>
                    <td><input type="text" name="name" form="sm<?php echo $m['id']; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($m['name']); ?>"></td>
                    <td><input type="number" step="0.01" name="warenwert_gesamt" form="sm<?php echo $m['id']; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($m['warenwert_gesamt']); ?>"></td>
                    <td><input type="number" step="0.01" name="wert_je_marke" form="sm<?php echo $m['id']; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($m['wert_je_marke']); ?>"></td>
                    <td><input type="date" name="datum" form="sm<?php echo $m['id']; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($m['datum']); ?>"></td>
                    <td>
                        <form id="sm<?php echo $m['id']; ?>" method="post" class="d-inline"></form>
                        <button form="sm<?php echo $m['id']; ?>" name="action" value="update" class="btn btn-sm btn-primary">Speichern</button>
                        <button form="sm<?php echo $m['id']; ?>" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Steuermarke löschen?');">Löschen</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$marks): ?>
                <tr><td colspan="6" class="text-center">Keine Steuermarken vorhanden.</td></tr>
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
