<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}
require __DIR__ . '/config.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'open';
$date = $_GET['date'] ?? '';

$query = 'SELECT id, title, status, assigned_to FROM tickets WHERE 1';
$params = [];
if ($status !== 'all') {
    $query .= ' AND status = ?';
    $params[] = $status;
}
if ($date !== '') {
    $query .= ' AND DATE(created_at) = ?';
    $params[] = $date;
}
if ($search !== '') {
    $query .= ' AND (title LIKE ? OR description LIKE ?)';
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}
$query .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($tickets);

