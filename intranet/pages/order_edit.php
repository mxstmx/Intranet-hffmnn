<?php
require __DIR__ . '/../config.php';
$orderNo = $_GET['orderNo'] ?? '';
$order = null;
if ($orderNo !== '') {
    $file = __DIR__ . '/../json/bestellungen.json';
    $json = file_exists($file) ? file_get_contents($file) : '';
    $allOrders = $json ? (json_decode($json, true) ?: []) : [];
    foreach ($allOrders as $row) {
        if (($row['Belegnummer'] ?? '') === $orderNo) {
            $order = $row;
        }
    }
}
if (!$order) {
    echo '<div class="nxl-content"><div class="container"><p>Bestellung nicht gefunden.</p></div></div>';
    return;
}
$meta   = $order['Metadaten'] ?? [];
$title  = $meta['Betreff'] ?? '';
$date   = $meta['Belegdatum'] ?? '';
$warenwert = 0;
foreach ($order['Produkte'] as $prod) {
    $qty   = (int)($prod['Menge'] ?? 0);
    $price = (float)($prod['Einzelpreis'] ?? 0);
    $warenwert += $qty * $price;
}
if (!$warenwert) {
    $warenwert = (float)($meta['BetragNetto'] ?? 0);
}
// existing assignment and marks
$stmt = $pdo->prepare('SELECT steuermarke_id, steuermarke_qty, zoll_eur, aircargo_usd, wechselkurs FROM bestellungen WHERE belegnummer = :bn');
$stmt->execute([':bn' => $orderNo]);
$assign = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['steuermarke_id'=>null,'steuermarke_qty'=>0,'zoll_eur'=>0,'aircargo_usd'=>0,'wechselkurs'=>1];
$marks = $pdo->query('SELECT id, name, wert_je_marke FROM steuermarken ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$markMap = [];
foreach ($marks as $m) { $markMap[$m['id']] = $m; }
$stampsValue = 0;
$assignedName = '';
$zoll = (float)$assign['zoll_eur'];
$airUsd = (float)$assign['aircargo_usd'];
$rate = (float)$assign['wechselkurs'];
$airEuro = $airUsd * $rate;
if ($assign['steuermarke_id']) {
    $m = $markMap[$assign['steuermarke_id']] ?? null;
    if ($m) {
        $stampsValue = $m['wert_je_marke'] * (int)$assign['steuermarke_qty'];
        $assignedName = $m['name'];
    }
}
// gather lieferscheine
$lieferscheine = [];
foreach ($allOrders as $row) {
    $meta2 = $row['Metadaten'] ?? [];
    if (($meta2['Belegart'] ?? '') === '2900' && ($meta2['Vorbelegnummer'] ?? '') === $orderNo) {
        $lieferscheine[] = [
            'nr' => $row['Belegnummer'] ?? '',
            'datum' => $meta2['Belegdatum'] ?? '',
            'produkte' => $row['Produkte'] ?? []
        ];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = $_POST['steuermarke_id'] ?? null;
    $qty = (int)($_POST['menge'] ?? 0);
    $zoll = (float)($_POST['zoll_eur'] ?? 0);
    $airUsd = (float)($_POST['aircargo_usd'] ?? 0);
    $rate = (float)($_POST['wechselkurs'] ?? 1);
    $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id = :sid, steuermarke_qty = :qty, zoll_eur = :zoll, aircargo_usd = :air, wechselkurs = :rate WHERE belegnummer = :bn');
    $stmt->execute([':sid'=>$sid, ':qty'=>$qty, ':zoll'=>$zoll, ':air'=>$airUsd, ':rate'=>$rate, ':bn'=>$orderNo]);
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
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <div class="mb-3 text-muted">Bestellnr <?php echo htmlspecialchars($orderNo); ?> vom <?php echo htmlspecialchars($date); ?></div>
        <div class="card mb-4">
            <h2>Kostenübersicht</h2>
            <div class="body">
                <table class="table">
                    <tr><th>Warenwert</th><td class="text-end">€ <?php echo number_format($warenwert,2,',','.'); ?></td><td class="text-end">$ <?php echo number_format($warenwert,2,',','.'); ?></td></tr>
                    <tr><th>Steuermarkenwert<?php echo $assignedName? ' ('.htmlspecialchars($assignedName).')':''; ?></th><td class="text-end" colspan="2">€ <?php echo number_format($stampsValue,2,',','.'); ?></td></tr>
                    <tr><th>Zollkosten</th><td class="text-end">€ <?php echo number_format($zoll,2,',','.'); ?></td><td class="text-end">&ndash;</td></tr>
                    <tr><th>Aircargo</th><td class="text-end">€ <?php echo number_format($airEuro,2,',','.'); ?></td><td class="text-end">$ <?php echo number_format($airUsd,2,',','.'); ?></td></tr>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <h2>Produkte</h2>
            <div class="body table">
                <table class="table">
                    <thead><tr><th>Artikel</th><th class="text-end">Menge</th><th class="text-end">Preis €</th><th class="text-end">Summe €</th></tr></thead>
                    <tbody>
                        <?php foreach ($order['Produkte'] as $p): $qty=(int)($p['Menge']??0); $price=(float)($p['Einzelpreis']??0); ?>
                            <tr><td><?php echo htmlspecialchars($p['Bezeichnung'] ?? ''); ?></td><td class="text-end"><?php echo $qty; ?></td><td class="text-end"><?php echo number_format($price,2,',','.'); ?></td><td class="text-end"><?php echo number_format($qty*$price,2,',','.'); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <h2>Lieferscheine</h2>
            <div class="body">
                <?php if ($lieferscheine): ?>
                    <ul>
                    <?php foreach ($lieferscheine as $ls): ?>
                        <li><?php echo htmlspecialchars($ls['nr']); ?> (<?php echo htmlspecialchars($ls['datum']); ?>)</li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Keine Lieferscheine vorhanden.</p>
                <?php endif; ?>
            </div>
        </div>
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
            <div class="mb-3">
                <label class="form-label">Zollgebühr (EUR, gesamt)</label>
                <input type="number" step="0.01" name="zoll_eur" class="form-control" value="<?php echo htmlspecialchars($zoll); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Aircargo Gebühr (USD, gesamt)</label>
                <input type="number" step="0.01" name="aircargo_usd" class="form-control" value="<?php echo htmlspecialchars($airUsd); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Wechselkurs (USD → EUR)</label>
                <input type="number" step="0.0001" name="wechselkurs" class="form-control" value="<?php echo htmlspecialchars($rate); ?>">
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
