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
</head>
<body>
<h2>Login - Hofmann Intranet</h2>
<?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST" action="login_process.php">
    <label>Benutzername: <input type="text" name="username" required></label><br><br>
    <label>Passwort: <input type="password" name="password" required></label><br><br>
    <button type="submit">Einloggen</button>
</form>
<p>Noch keinen Zugang? <a href="register.php">Registrieren</a></p>
</body>
</html>
