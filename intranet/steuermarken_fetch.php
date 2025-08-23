<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}
require __DIR__ . '/config.php';
$search = $_GET['search'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$sql = "SELECT s.id,s.name,s.warenwert_gesamt,s.wert_je_marke,s.datum,s.anzahl,
        IFNULL(SUM(b.steuermarke_qty),0) as used_qty,
        GROUP_CONCAT(b.betreff, ', ') AS betreffe
        FROM steuermarken s LEFT JOIN bestellungen b ON b.steuermarke_id = s.id";
$conds = [];
$params = [];
if ($search !== '') {
    $conds[] = 's.name LIKE :search';
    $params[':search'] = "%$search%";
}
if ($from !== '') {
    $conds[] = 's.datum >= :from';
    $params[':from'] = $from;
}
if ($to !== '') {
    $conds[] = 's.datum <= :to';
    $params[':to'] = $to;
}
if ($conds) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}
$sql .= ' GROUP BY s.id ORDER BY s.datum DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
header('Content-Type: application/json');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
