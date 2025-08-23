<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Hofmann Intranet Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="silva-template/assets/app.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card shadow p-4">
        <div class="text-center mb-4">
            <img src="img/hoffmann-logo-light.png" alt="Hoffmann" style="max-width:200px;">
        </div>
        <h2 class="mb-4 text-center">Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="login_process.php">
            <div class="mb-3">
                <label class="form-label">Benutzername</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Passwort</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Einloggen</button>
        </form>
        <p class="mt-3 text-center">Noch keinen Zugang? <a href="register.php">Registrieren</a></p>
    </div>
</div>
</body>
</html>
