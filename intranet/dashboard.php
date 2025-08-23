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
<div class="container py-4">
    <h2 class="mb-4">Dashboard</h2>
    <div class="row g-4">
        <?php if ($_SESSION['role'] === 'kunde'): ?>
            <div class="col-md-4">
                <a class="tile-link" href="kunde_area.php">
                    <div class="card text-center h-100"><div class="card-body">Kundenbereich</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="bestellungen.php">
                    <div class="card text-center h-100"><div class="card-body">Bestellungen</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="textbestellungen.php">
                    <div class="card text-center h-100"><div class="card-body">Textbestellung</div></div>
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'], ['mitarbeiter','admin'])): ?>
            <div class="col-md-4">
                <a class="tile-link" href="mitarbeiter_area.php">
                    <div class="card text-center h-100"><div class="card-body">Mitarbeiterbereich</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="steuermarken.php">
                    <div class="card text-center h-100"><div class="card-body">Steuermarken</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="bestellungen.php">
                    <div class="card text-center h-100"><div class="card-body">Bestellungen</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="textbestellungen.php">
                    <div class="card text-center h-100"><div class="card-body">Textbestellung</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="offene_posten.php">
                    <div class="card text-center h-100"><div class="card-body">Offene Posten</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="bestand.php">
                    <div class="card text-center h-100"><div class="card-body">Bestand</div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="tile-link" href="<?php echo $_SESSION['role']==='admin' ? 'admin_tickets.php' : 'tickets.php'; ?>">
                    <div class="card text-center h-100"><div class="card-body">Tickets</div></div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
