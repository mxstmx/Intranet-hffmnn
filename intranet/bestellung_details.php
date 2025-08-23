<?php
session_start();
require __DIR__ . '/config.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
$orderNo = $_GET['id'] ?? '';
if (!$orderNo) {
    echo 'Keine Bestellung gewählt';
    exit();
}
$rate = 1.08; // 1 EUR = 1.08 USD
$currency = $_POST['currency'] ?? $_GET['currency'] ?? 'eur';
// Fetch parent
$stmt = $pdo->prepare('SELECT belegnummer, belegdatum, betreff, betrag FROM bestellungen WHERE belegnummer = ? AND belegart = 2200');
$stmt->execute([$orderNo]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$parent) {
    echo 'Bestellung nicht gefunden';
    exit();
}
// Fetch items
$stmt = $pdo->prepare('SELECT belegnummer, betreff, betrag FROM bestellungen WHERE vorbelegnummer = ? AND belegart = 2900');
$stmt->execute([$orderNo]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($items as $item) {
        $field = 'price_' . $item['belegnummer'];
        if (isset($_POST[$field])) {
            $val = (float)$_POST[$field];
            if ($currency === 'usd') {
                $val = $val / $rate;
            }
            $upd = $pdo->prepare('UPDATE bestellungen SET betrag = ? WHERE belegnummer = ?');
            $upd->execute([$val, $item['belegnummer']]);
        }
    }
    $sum = $pdo->prepare('SELECT SUM(betrag) FROM bestellungen WHERE vorbelegnummer = ? AND belegart = 2900');
    $sum->execute([$orderNo]);
    $total = $sum->fetchColumn();
    $upd = $pdo->prepare('UPDATE bestellungen SET betrag = ? WHERE belegnummer = ? AND belegart = 2200');
    $upd->execute([$total, $orderNo]);
    header('Location: bestellung_details.php?id=' . $orderNo . '&currency=' . $currency . '&saved=1');
    exit();
}
function formatAmount($val, $currency, $rate) {
    if ($currency === 'usd') {
        return number_format($val * $rate, 2, '.', '');
    }
    return number_format($val, 2, '.', '');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellung <?php echo htmlspecialchars($orderNo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="silva-template/assets/app.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card shadow p-4">
        <img src="img/hoffmann-logo-light.png" alt="Hoffmann" class="mb-4" style="max-width:200px;">
        <h2 class="mb-4">Bestellung <?php echo htmlspecialchars($orderNo); ?></h2>
        <?php if(isset($_GET['saved'])): ?>
            <div class="alert alert-success">Änderungen gespeichert</div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Währung</label>
                <select name="currency" id="currency" class="form-select">
                    <option value="eur" <?php if($currency==='eur') echo 'selected'; ?>>EUR</option>
                    <option value="usd" <?php if($currency==='usd') echo 'selected'; ?>>USD</option>
                </select>
            </div>
            <table class="table">
                <thead><tr><th>Artikel</th><th class="text-end">Preis</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($it['betreff']); ?></td>
                        <td class="text-end">
                            <input type="number" step="0.01" class="form-control text-end price-input" name="price_<?php echo $it['belegnummer']; ?>" value="<?php echo formatAmount($it['betrag'],$currency,$rate); ?>" data-eur="<?php echo htmlspecialchars($it['betrag']); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="bestellungen.php" class="btn btn-secondary">Zurück</a>
        </form>
    </div>
</div>
<script>
const RATE=1.08;
const cur=document.getElementById('currency');
cur.addEventListener('change',e=>{
  document.querySelectorAll('.price-input').forEach(inp=>{
    const eur=parseFloat(inp.dataset.eur);
    inp.value = e.target.value==='usd' ? (eur*RATE).toFixed(2) : eur.toFixed(2);
  });
});
</script>
</body>
</html>
