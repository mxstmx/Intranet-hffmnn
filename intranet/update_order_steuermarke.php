<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}
require __DIR__ . '/config.php';
$order = $_POST['order'] ?? '';
$smId = $_POST['steuermarke_id'] ?? '';
if ($order !== '') {
    $stmt = $pdo->prepare('UPDATE bestellungen SET steuermarke_id = :sm WHERE belegnummer = :order');
    $stmt->execute([':sm' => $smId !== '' ? $smId : null, ':order' => $order]);
    echo 'ok';
}
