<?php
require __DIR__ . '/../config.php';
$orderNo = $_GET['orderNo'] ?? '';
$order = null;
if ($orderNo !== '') {
    $file = __DIR__ . '/../json/bestellungen.json';
    $json = file_exists($file) ? file_get_contents($file) : '';
    if ($json) {
        $arr = json_decode($json, true) ?: [];
        foreach ($arr as $row) {
            if (($row['Belegnummer'] ?? '') === $orderNo) {
                $order = $row;
                break;
            }
        }
    }
}
if (!$order) {
    echo '<div class="nxl-content"><div class="container"><p>Bestellung nicht gefunden.</p></div></div>';
    return;
}
// existing assignment
$stmt = $pdo->prepare('SELECT steuermarke_id, steuermarke_qty FROM bestellungen WHERE belegnummer = :bn');
$stmt->execute([':bn' => $orderNo]);
$assign = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['steuermarke_id'=>null,'steuermarke_qty'=>0];
$marks = $pdo->query('SELECT id, name, wert_je_marke FROM steuermarken ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = $_POST['steuermarke_id'] ?? null;
    $qty = (int)($_POST['menge'] ?? 0);
    $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id = :sid, steuermarke_qty = :qty WHERE belegnummer = :bn');
    $stmt->execute([':sid'=>$sid, ':qty'=>$qty, ':bn'=>$orderNo]);
    header('Location: dashboard.php?page=bestellungen');
    exit();
}
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Bestellung bearbeiten</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php?page=bestellungen">Bestellungen</a></li>
                <li class="breadcrumb-item">Bearbeiten</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <h2><?php echo htmlspecialchars($order['Metadaten']['Betreff'] ?? ''); ?></h2>
        <form method="POST" class="mt-3">
            <div class="mb-3">
                <label class="form-label">Steuermarke</label>
                <select name="steuermarke_id" class="form-select">
                    <option value="">-- wählen --</option>
                    <?php foreach ($marks as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $assign['steuermarke_id']==$m['id']?'selected':''; ?>><?php echo htmlspecialchars($m['name']); ?> (<?php echo number_format($m['wert_je_marke'],2,',','.'); ?> €)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Menge Steuermarken</label>
                <input type="number" name="menge" class="form-control" value="<?php echo (int)$assign['steuermarke_qty']; ?>">
            </div>
            <button class="btn btn-primary" type="submit">Speichern</button>
            <a href="dashboard.php?page=bestellungen" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
</div>
<footer class="footer">
    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
        <span>Copyright ©</span>
        <script>document.write(new Date().getFullYear());</script>
    </p>
</footer>
