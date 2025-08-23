<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require __DIR__ . '/config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM steuermarken WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
header('Location: dashboard.php?page=steuermarken');
exit();
