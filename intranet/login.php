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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoffmann Intranet Login</title>
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
                    <img src="assets/images/auth/auth-cover-login-bg.svg" alt="" class="img-fluid">
                </div>
            </div>
        </div>
        <div class="auth-cover-sidebar-inner">
            <div class="auth-cover-card-wrapper">
                <div class="auth-cover-card p-sm-5">
                    <div class="wd-50 mb-5">
                        <img src="assets/images/hoffmann-logo-light.png" alt="Hoffmann" class="img-fluid">
                    </div>
                    <h2 class="fs-20 fw-bolder mb-4">Login</h2>
                    <h4 class="fs-13 fw-bold mb-2">Melden Sie sich an</h4>
                    <p class="fs-12 fw-medium text-muted">Melden Sie sich an, um fortzufahren.</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="login_process.php" class="w-100 mt-4 pt-2">
                        <div class="mb-4">
                            <input type="text" name="username" class="form-control" placeholder="Benutzername" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Passwort" required>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="rememberMe">
                                <label class="custom-control-label c-pointer" for="rememberMe">Angemeldet bleiben</label>
                            </div>
                            <div>
                                <a href="auth-reset-cover.html" class="fs-11 text-primary">Passwort vergessen?</a>
                            </div>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-lg btn-primary w-100">Login</button>
                        </div>
                    </form>
                    <div class="mt-5 text-muted">
                        <span>Noch keinen Zugang?</span>
                        <a href="register.php" class="fw-bold">Registrieren</a>
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

