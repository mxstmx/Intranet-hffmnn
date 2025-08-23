<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name = $wg = $wj = $datum = '';
$anzahl = 0;
$selectedOrder = '';
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM steuermarken WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name   = $m['name'];
        $wg     = $m['warenwert_gesamt'];
        $wj     = $m['wert_je_marke'];
        $datum  = $m['datum'];
        $anzahl = $m['anzahl'];
        $stmt2 = $pdo->prepare('SELECT belegnummer FROM bestellungen WHERE steuermarke_id = :id LIMIT 1');
        $stmt2->execute([':id' => $id]);
        $selectedOrder = $stmt2->fetchColumn() ?: '';
    } else {
        $id = 0;
    }
}
// Orders for assignment come from JSON instead of the database
$orders = [];
$file = __DIR__ . '/json/bestellungen.json';
$json = file_exists($file) ? file_get_contents($file) : '';
if ($json) {
    $arr = json_decode($json, true) ?: [];
    foreach ($arr as $row) {
        $orders[] = [
            'belegnummer' => $row['Belegnummer'] ?? '',
            'betreff'     => $row['Metadaten']['Betreff'] ?? ''
        ];
    }
    usort($orders, fn($a,$b)=>strcmp($a['belegnummer'],$b['belegnummer']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $wj     = (float)($_POST['wert_je_marke'] ?? 0);
    $datum  = $_POST['datum'] ?? '';
    $anzahl = (int)($_POST['anzahl'] ?? 0);
    $wg     = $anzahl * $wj;
    $order  = $_POST['order'] ?? '';
    if ($name !== '') {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE steuermarken SET name=:name, warenwert_gesamt=:wg, wert_je_marke=:wj, datum=:datum, anzahl=:anzahl WHERE id=:id');
            $stmt->execute([':name'=>$name, ':wg'=>$wg, ':wj'=>$wj, ':datum'=>$datum, ':anzahl'=>$anzahl, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO steuermarken (name, warenwert_gesamt, wert_je_marke, datum, anzahl) VALUES (:name,:wg,:wj,:datum,:anzahl)');
            $stmt->execute([':name'=>$name, ':wg'=>$wg, ':wj'=>$wj, ':datum'=>$datum, ':anzahl'=>$anzahl]);
            $id = $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE bestellungen SET steuermarke_id=NULL, steuermarke_qty=0 WHERE steuermarke_id=:id')->execute([':id'=>$id]);
        if ($order !== '') {
            $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id=:sid, steuermarke_qty=:qty WHERE belegnummer=:bn');
            $stmt->execute([':sid'=>$id, ':qty'=>$anzahl, ':bn'=>$order]);
        }
        header('Location: dashboard.php?page=steuermarken');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $id ? 'Steuermarke bearbeiten' : 'Steuermarke anlegen'; ?> - Hoffmann Intranet</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/theme.min.css" />
    <style>html.minimenu .nxl-header{left:0}</style>
</head>
<body>
<?php include 'menu.php'; ?>
<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title"><h5 class="m-b-10">Steuermarken</h5></div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="dashboard.php?page=steuermarken">Steuermarken</a></li>
                    <li class="breadcrumb-item"><?php echo $id ? 'Bearbeiten' : 'Neu'; ?></li>
                </ul>
            </div>
        </div>
        <div class="container mt-4">
            <h2><?php echo $id ? 'Steuermarke bearbeiten' : 'Neue Steuermarke'; ?></h2>
            <form method="POST" class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Wert je Steuermarke</label>
                    <input type="number" step="0.01" name="wert_je_marke" class="form-control" value="<?php echo htmlspecialchars($wj); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stückzahl</label>
                    <input type="number" name="anzahl" class="form-control" value="<?php echo htmlspecialchars($anzahl); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Warenwert gesamt</label>
                    <input type="number" step="0.01" name="warenwert_gesamt" class="form-control" value="<?php echo htmlspecialchars($wg); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Datum</label>
                    <input type="date" name="datum" class="form-control" value="<?php echo htmlspecialchars($datum); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Bestellung</label>
                    <select name="order" class="form-select">
                        <option value="">-- wählen --</option>
                        <?php foreach ($orders as $o): ?>
                            <option value="<?php echo htmlspecialchars($o['belegnummer']); ?>" <?php echo $o['belegnummer']==$selectedOrder?'selected':''; ?>><?php echo htmlspecialchars($o['belegnummer'] . ' - ' . ($o['betreff'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Speichern</button>
                <a href="dashboard.php?page=steuermarken" class="btn btn-secondary">Abbrechen</a>
            </form>
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
<script>
function calcWG(){
    var wj=parseFloat(document.querySelector('input[name="wert_je_marke"]').value)||0;
    var qty=parseFloat(document.querySelector('input[name="anzahl"]').value)||0;
    document.querySelector('input[name="warenwert_gesamt"]').value=(wj*qty).toFixed(2);
}
document.querySelector('input[name="wert_je_marke"]').addEventListener('input',calcWG);
document.querySelector('input[name="anzahl"]').addEventListener('input',calcWG);
calcWG();
</script>
</body>
</html>
