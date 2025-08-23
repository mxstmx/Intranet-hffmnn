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
</head>
<body>
<h2>Willkommen, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
<p>Du bist als <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong> eingeloggt.</p>
<nav>
    <ul>
        <?php if ($_SESSION['role'] === 'kunde'): ?>
            <li><a href="kunde_area.php">Kundenbereich</a></li>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'mitarbeiter'): ?>
            <li><a href="mitarbeiter_area.php">Mitarbeiterbereich</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
</body>
</html>
