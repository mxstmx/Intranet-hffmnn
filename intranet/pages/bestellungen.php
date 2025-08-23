<?php
require __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderNo'])) {
    $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id = :sid WHERE belegnummer = :bn');
    $sid = $_POST['steuermarke_id'] !== '' ? (int)$_POST['steuermarke_id'] : null;
    $stmt->execute([':sid' => $sid, ':bn' => $_POST['orderNo']]);
}
$data = json_decode(file_get_contents(__DIR__ . '/../json/bestellungen.json'), true) ?? [];
$orders = [];
foreach ($data as $entry) {
    if (($entry['Metadaten']['Belegart'] ?? '') !== '2200') { continue; }
    $orderNo = $entry['Belegnummer'];
    $orderedAt = $entry['Metadaten']['Belegdatum'] ?? '';
    $title = $entry['Metadaten']['Betreff'] ?? '';
    $totalNet = 0; $orderedQty = 0;
    foreach ($entry['Produkte'] as $prod) {
        $qty = (int)($prod['Menge'] ?? 0);
        $price = (float)($prod['Einzelpreis'] ?? 0);
        $orderedQty += $qty;
        $totalNet += $qty * $price;
    }
    if (!$totalNet) { $totalNet = (float)($entry['Metadaten']['BetragNetto'] ?? 0); }
    $orders[$orderNo] = [
        'title' => $title,
        'orderNo' => $orderNo,
        'orderedAt' => $orderedAt,
        'orderedQty' => $orderedQty,
        'deliveredQty' => 0,
        'totalNet' => $totalNet,
    ];
}
foreach ($data as $entry) {
    if (($entry['Metadaten']['Belegart'] ?? '') === '2900') {
        $parent = $entry['Metadaten']['Vorbelegnummer'] ?? '';
        if (isset($orders[$parent])) {
            $delivered = 0;
            foreach ($entry['Produkte'] as $prod) {
                $delivered += (int)($prod['Menge'] ?? 0);
            }
            $orders[$parent]['deliveredQty'] += $delivered;
        }
    }
}
foreach ($orders as &$o) {
    $o['tax'] = round($o['totalNet'] * 0.19, 2);
    $o['totalGross'] = round($o['totalNet'] + $o['tax'], 2);
    $o['deliveredPct'] = $o['orderedQty'] > 0 ? round($o['deliveredQty'] / max(1,$o['orderedQty']) * 100) : 0;
}
unset($o);
$assigned = [];
foreach ($pdo->query('SELECT belegnummer, steuermarke_id FROM bestellungen') as $row) {
    $assigned[$row['belegnummer']] = $row['steuermarke_id'];
}
$marks = $pdo->query('SELECT id, name FROM steuermarken')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Bestellungen</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Bestellungen</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <table class="table table-striped">
            <thead><tr><th>Titel</th><th>Bestellnr</th><th>Bestelldatum</th><th>Steuermarke</th><th class="text-end">Steuerbetrag</th><th class="text-end">Warenwert (brutto)</th><th class="text-end">Geliefert (%)</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?php echo htmlspecialchars($o['title']); ?></td>
                    <td><?php echo htmlspecialchars($o['orderNo']); ?></td>
                    <td><?php echo htmlspecialchars($o['orderedAt']); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="orderNo" value="<?php echo htmlspecialchars($o['orderNo']); ?>">
                            <select name="steuermarke_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value=""></option>
                                <?php foreach ($marks as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php if (($assigned[$o['orderNo']] ?? null)==$m['id']) echo 'selected'; ?>><?php echo htmlspecialchars($m['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="text-end"><?php echo number_format($o['tax'], 2, ',', '.'); ?> €</td>
                    <td class="text-end"><?php echo number_format($o['totalGross'], 2, ',', '.'); ?> €</td>
                    <td class="text-end"><?php echo $o['deliveredPct']; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="7" class="text-center">Keine Bestellungen vorhanden.</td></tr>
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
