<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mitarbeiterbereich - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card shadow p-4">
        <h2 class="mb-4">Mitarbeiterbereich</h2>
        <p>Hier können Mitarbeiter interne Informationen abrufen.</p>
        <a href="dashboard.php" class="btn btn-secondary">Zurück zum Dashboard</a>
    </div>
</div>
</body>
</html>
