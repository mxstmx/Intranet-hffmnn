<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'mitarbeiter') {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mitarbeiterbereich - Hofmann Intranet</title>
</head>
<body>
<h2>Mitarbeiterbereich</h2>
<p>Hier können Mitarbeiter interne Informationen abrufen.</p>
<a href="dashboard.php">Zurück zum Dashboard</a>
</body>
</html>
