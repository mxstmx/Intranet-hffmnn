<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}
require __DIR__ . '/config.php';
$ls = $_POST['lieferschein'] ?? '';
$zoll = isset($_POST['zoll']) ? (float)$_POST['zoll'] : 0;
$air = isset($_POST['air']) ? (float)$_POST['air'] : 0;
if ($ls === '') {
    echo json_encode(['success' => false]);
    exit();
}
// update lieferschein record
$stmt = $pdo->prepare('UPDATE bestellungen SET zoll_eur = :zoll, aircargo_usd = :air WHERE belegnummer = :bn');
$stmt->execute([':zoll' => $zoll, ':air' => $air, ':bn' => $ls]);
// fetch parent order number
$stmt = $pdo->prepare('SELECT vorbelegnummer FROM bestellungen WHERE belegnummer = :bn');
$stmt->execute([':bn' => $ls]);
$orderNo = $stmt->fetchColumn();
$totals = ['zoll' => 0, 'air' => 0];
if ($orderNo) {
    $stmt = $pdo->prepare('SELECT SUM(zoll_eur) AS zoll, SUM(aircargo_usd) AS air FROM bestellungen WHERE vorbelegnummer = :ord');
    $stmt->execute([':ord' => $orderNo]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['zoll' => 0, 'air' => 0];
    $upd = $pdo->prepare('UPDATE bestellungen SET zoll_eur = :zoll, aircargo_usd = :air WHERE belegnummer = :ord');
    $upd->execute([':zoll' => $totals['zoll'], ':air' => $totals['air'], ':ord' => $orderNo]);
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'totals' => $totals]);
