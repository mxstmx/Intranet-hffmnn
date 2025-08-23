<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Hofmann Intranet</a>
        <div class="d-flex">
            <span class="navbar-text me-3"><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
            <a class="btn btn-outline-danger" href="logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container">
    <h2 class="mb-4">Dashboard</h2>
    <div class="list-group">
        <?php if ($_SESSION['role'] === 'kunde'): ?>
            <a href="kunde_area.php" class="list-group-item list-group-item-action">Kundenbereich</a>
            <a href="bestellungen.php" class="list-group-item list-group-item-action">Bestellungen</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'], ['mitarbeiter','admin'])): ?>
            <a href="mitarbeiter_area.php" class="list-group-item list-group-item-action">Mitarbeiterbereich</a>
            <a href="steuermarken.php" class="list-group-item list-group-item-action">Steuermarken</a>
            <a href="bestellungen.php" class="list-group-item list-group-item-action">Bestellungen</a>
            <a href="offene_posten.php" class="list-group-item list-group-item-action">Offene Posten</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
