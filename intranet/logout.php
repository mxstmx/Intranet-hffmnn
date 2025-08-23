<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Abmeldung - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="silva-template/assets/app.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card shadow p-4 text-center">
        <img src="img/hoffmann-logo-light.png" alt="Hoffmann" class="mb-4" style="max-width:200px;">
        <h2>Sie wurden ausgeloggt</h2>
        <p class="mb-4">Vielen Dank für Ihren Besuch.</p>
        <a href="login.php" class="btn btn-primary">Zum Login</a>
    </div>
</div>
</body>
</html>
