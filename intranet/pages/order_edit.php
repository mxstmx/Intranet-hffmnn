<style>
	.card {
		padding: 20px;
	}
</style>
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
$warenwertUsd = 0;
$totalQty = 0;
foreach ($order['Produkte'] as $prod) {
    $qty   = (int)($prod['Menge'] ?? 0);
    $price = (float)($prod['Einzelpreis'] ?? 0);
    $totalQty += $qty;
    $warenwertUsd += $qty * $price;
}
if (!$warenwertUsd) {
    $warenwertUsd = (float)($meta['BetragNetto'] ?? 0);
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
$airEuro = $rate ? $airUsd / $rate : $airUsd;
$warenwertEuro = $rate ? $warenwertUsd / $rate : $warenwertUsd;
$markVal = 0;
if ($assign['steuermarke_id']) {
    $m = $markMap[$assign['steuermarke_id']] ?? null;
    if ($m) {
        $markVal = (float)$m['wert_je_marke'];
        $stampsValue = $markVal * (int)$assign['steuermarke_qty'];
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
            'lf' => $meta2['LFBelegnummer'] ?? '',
            'datum' => $meta2['Belegdatum'] ?? '',
            'produkte' => $row['Produkte'] ?? []
        ];
    }
}
$lsNums = array_column($lieferscheine, 'nr');
if ($lsNums) {
    $placeholders = implode(',', array_fill(0, count($lsNums), '?'));
    $stmt = $pdo->prepare("SELECT belegnummer, zoll_eur, aircargo_usd FROM bestellungen WHERE belegnummer IN ($placeholders)");
    $stmt->execute($lsNums);
    $costMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $costMap[$row['belegnummer']] = $row;
    }
    foreach ($lieferscheine as &$ls) {
        $c = $costMap[$ls['nr']] ?? ['zoll_eur'=>0,'aircargo_usd'=>0];
        $ls['zoll'] = (float)$c['zoll_eur'];
        $ls['air'] = (float)$c['aircargo_usd'];
    }
    unset($ls);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rate = (float)($_POST['wechselkurs'] ?? 1);
    $stmt = $pdo->prepare('UPDATE bestellungen SET wechselkurs = :rate WHERE belegnummer = :bn');
    $stmt->execute([':rate'=>$rate, ':bn'=>$orderNo]);
    header('Location: dashboard.php?page=order_edit&orderNo=' . urlencode($orderNo));
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
					<tr><th>Warenwert</th><td class="text-end">Stückpreis</td><td class="text-end">€ <?php echo number_format($warenwertEuro,2,',','.'); ?></td><td class="text-end">$ <?php echo number_format($warenwertUsd,2,',','.'); ?></td></tr>
                    <tr><th>Steuermarkenwert<?php echo $assignedName? ' ('.htmlspecialchars($assignedName).')':''; ?></th><td class="text-end">Stückpreis</td><td class="text-end">€ <?php echo number_format($stampsValue,2,',','.'); ?></td><td class="text-end">-</td></tr>
                    <tr><th>Zollkosten</th><td class="text-end">Stückpreis</td><td class="text-end" id="total-zoll">€ <?php echo number_format($zoll,2,',','.'); ?></td><td class="text-end">&ndash;</td></tr>
                    <tr><th>Aircargo</th><td class="text-end">Stückpreis</td><td class="text-end" id="total-air-eur">€ <?php echo number_format($airEuro,2,',','.'); ?></td><td class="text-end" id="total-air-usd">$ <?php echo number_format($airUsd,2,',','.'); ?></td></tr>
                    <?php
                        $warenStk = $totalQty ? $warenwertEuro / $totalQty : 0;
                        $airStk = $totalQty ? $airEuro / $totalQty : 0;
                        $zollStk = $totalQty ? $zoll / $totalQty : 0;
                        $stkPreis = $warenStk + $airStk + $zollStk + $markVal;
                    ?>
                    <tr><th>Stückpreis</th><td class="text-end" id="stkpreis">€ <?php echo number_format($stkPreis,2,',','.'); ?></td><td class="text-end">&ndash;</td></tr>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <h2>Produkte</h2>
            <div class="body table">
                <table class="table">
                    <thead><tr><th>Artikel</th><th class="text-end">Menge</th><th class="text-end">Preis $</th><th class="text-end">Summe $</th></tr></thead>
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
                    <ul id="ls-list" class="list-unstyled">
                    <?php foreach ($lieferscheine as $ls): ?>
                        <li class="d-flex justify-content-between align-items-center mb-2" data-nr="<?php echo htmlspecialchars($ls['nr']); ?>">
                            <span><?php echo htmlspecialchars($ls['nr']); ?> / LF <?php echo htmlspecialchars($ls['lf']); ?> (<?php echo htmlspecialchars($ls['datum']); ?>)</span>
                            <span>Zoll: <span class="ls-zoll"><?php echo number_format($ls['zoll'],2,',','.'); ?></span> € &nbsp;|&nbsp; Air: <span class="ls-air"><?php echo number_format($ls['air'],2,',','.'); ?></span> $ <a href="#" class="edit-ls ms-2" data-nr="<?php echo htmlspecialchars($ls['nr']); ?>" data-zoll="<?php echo htmlspecialchars($ls['zoll']); ?>" data-air="<?php echo htmlspecialchars($ls['air']); ?>">✎</a></span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Keine Lieferscheine vorhanden.</p>
                <?php endif; ?>
            </div>
        </div>
        <form method="POST" class="mt-3">
            <div class="mb-3">
                <label class="form-label">Wechselkurs (USD → EUR)</label>
                <input type="number" step="0.0001" name="wechselkurs" class="form-control" value="<?php echo htmlspecialchars($rate); ?>">
            </div>
            <button class="btn btn-primary" type="submit">Speichern</button>
            <a href="dashboard.php?page=bestellungen" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
</div>
<div id="lsModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;max-width:400px;width:100%;">
        <form id="lsForm">
            <h3>Lieferschein bearbeiten</h3>
            <input type="hidden" id="lsNr">
            <div class="mb-3">
                <label class="form-label">Zollgebühr (EUR)</label>
                <input type="number" step="0.01" id="lsZoll" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Aircargo Gebühr (USD)</label>
                <input type="number" step="0.01" id="lsAir" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <button type="button" id="lsCancel" class="btn btn-secondary">Abbrechen</button>
        </form>
    </div>
</div>
<script>
const lsModal=document.getElementById('lsModal');
const rate=<?php echo $rate ?: 1; ?>;
const totalQty=<?php echo (int)$totalQty; ?>;
const warenEuro=<?php echo $warenwertEuro; ?>;
const markVal=<?php echo $markVal; ?>;
document.querySelectorAll('.edit-ls').forEach(btn=>{
    btn.addEventListener('click',e=>{
        e.preventDefault();
        document.getElementById('lsNr').value=btn.dataset.nr;
        document.getElementById('lsZoll').value=btn.dataset.zoll;
        document.getElementById('lsAir').value=btn.dataset.air;
        lsModal.style.display='flex';
    });
});
document.getElementById('lsCancel').addEventListener('click',()=>lsModal.style.display='none');
document.getElementById('lsForm').addEventListener('submit',e=>{
    e.preventDefault();
    const nr=document.getElementById('lsNr').value;
    const zoll=document.getElementById('lsZoll').value||0;
    const air=document.getElementById('lsAir').value||0;
    fetch('update_lieferschein_costs.php',{method:'POST',headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({lieferschein:nr,zoll:zoll,air:air})})
    .then(r=>r.json())
    .then(res=>{
        if(res.success){
            lsModal.style.display='none';
            const row=document.querySelector(`li[data-nr="${nr}"]`);
            row.querySelector('.ls-zoll').textContent=parseFloat(zoll).toFixed(2).replace('.',',');
            row.querySelector('.ls-air').textContent=parseFloat(air).toFixed(2).replace('.',',');
            const t=res.totals;
            const airUsd=parseFloat(t.air);
            const airEur=airUsd/ (rate||1);
            const zollSum=parseFloat(t.zoll);
            document.getElementById('total-zoll').textContent='€ '+zollSum.toFixed(2).replace('.',',');
            document.getElementById('total-air-usd').textContent='$ '+airUsd.toFixed(2).replace('.',',');
            document.getElementById('total-air-eur').textContent='€ '+airEur.toFixed(2).replace('.',',');
            const stk= totalQty ? (warenEuro/totalQty)+(airEur/totalQty)+(zollSum/totalQty)+markVal : 0;
            document.getElementById('stkpreis').textContent='€ '+stk.toFixed(2).replace('.',',');
        }
    });
});
</script>
<footer class="footer">
    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
        <span>Copyright ©</span>
        <script>document.write(new Date().getFullYear());</script>
    </p>
</footer>
