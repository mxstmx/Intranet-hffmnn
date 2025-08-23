<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoffmann Intranet Logout</title>
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
                <div class="auth-cover-card p-sm-5 text-center">
                    <div class="wd-50 mb-5">
                        <img src="assets/images/hoffmann-logo-light.png" alt="Hoffmann" class="img-fluid">
                    </div>
                    <h2 class="fs-20 fw-bolder mb-4">Sie wurden ausgeloggt</h2>
                    <p class="fs-12 fw-medium text-muted mb-4">Vielen Dank für Ihren Besuch.</p>
                    <a href="login.php" class="btn btn-lg btn-primary w-100">Zum Login</a>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>

