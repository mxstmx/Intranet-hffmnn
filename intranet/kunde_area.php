<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'kunde') {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kundenbereich - Hofmann Intranet</title>
</head>
<body>
<h2>Kundenbereich</h2>
<p>Hier können Kunden ihre Inhalte sehen.</p>
<a href="dashboard.php">Zurück zum Dashboard</a>
</body>
</html>
