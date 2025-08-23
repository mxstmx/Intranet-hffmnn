<?php
session_start();
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}
require __DIR__ . '/config.php';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'kunde';

    if ($username && $password) {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $role
            ]);
            $success = 'Nutzer erfolgreich angelegt.';
        } catch (PDOException $e) {
            $error = 'Benutzername bereits vergeben.';
        }
    } else {
        $error = 'Alle Felder ausfüllen.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoffmann Intranet Registrierung</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth-cover-wrapper">
        <div class="auth-cover-content-inner">
            <div class="auth-cover-content-wrapper">
                <div class="auth-img">
                    <img src="assets/images/auth-cover.png" alt="" class="img-fluid">
                </div>
            </div>
        </div>
        <div class="auth-cover-sidebar-inner">
            <div class="auth-cover-card-wrapper">
                <div class="auth-cover-card p-sm-5">
                    <div class="wd-50 mb-5">
                        <img src="assets/images/hoffmann-logo-light.png" alt="Hoffmann" class="img-fluid">
                    </div>
                    <h2 class="fs-20 fw-bolder mb-4">Registrieren</h2>
                    <h4 class="fs-13 fw-bold mb-2">Erstellen Sie einen Account</h4>
                    <p class="fs-12 fw-medium text-muted">Bitte füllen Sie die folgenden Felder aus.</p>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="register.php" class="w-100 mt-4 pt-2">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control" placeholder="Benutzername" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Passwort" required>
                        </div>
                        <div class="mb-3">
                            <select name="role" class="form-select">
                                <option value="kunde">Kunde</option>
                                <option value="mitarbeiter">Mitarbeiter</option>
                            </select>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-lg btn-primary w-100">Registrieren</button>
                        </div>
                    </form>
                    <div class="mt-5 text-muted">
                        <span>Bereits registriert?</span>
                        <a href="login.php" class="fw-bold">Zum Login</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>

