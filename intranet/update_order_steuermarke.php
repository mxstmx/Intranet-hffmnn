<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}
require __DIR__ . '/config.php';
$order = $_POST['order'] ?? '';
$smId = $_POST['steuermarke_id'] ?? '';
$qty  = isset($_POST['menge']) ? (int)$_POST['menge'] : 0;
if ($order !== '') {
    // ensure order record exists
    $chk = $pdo->prepare('SELECT 1 FROM bestellungen WHERE belegnummer = :bn');
    $chk->execute([':bn'=>$order]);
    if (!$chk->fetchColumn()) {
        $pdo->prepare('INSERT INTO bestellungen (belegnummer) VALUES (:bn)')->execute([':bn'=>$order]);
    }
    $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id = :sm, steuermarke_qty = :qty WHERE belegnummer = :order');
    $stmt->execute([
        ':sm'   => $smId !== '' ? $smId : null,
        ':qty'  => $smId !== '' ? $qty : 0,
        ':order'=> $order
    ]);
    echo 'ok';
}
