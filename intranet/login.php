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
    <title>Hoffmann Intranet Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="silva-template/assets/app.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-primary-subtle">
<div class="account-page">
  <div class="container-fluid p-0">
    <div class="row align-items-center g-0">
      <div class="col-xl-5">
        <div class="row">
          <div class="col-md-8 mx-auto">
            <div class="card p-4 mb-0">
              <div class="card-body">
                <div class="mb-0 border-0 p-md-5 p-lg-0 p-4">
                  <div class="mb-4 text-center">
                    <img src="img/hoffmann-logo-light.png" alt="Hoffmann" class="mx-auto" style="max-width:200px;">
                  </div>
                  <div class="auth-title-section mb-3 text-center">
                    <h3 class="text-dark fs-20 fw-medium mb-2">Willkommen zurück</h3>
                    <p class="text-dark fs-14 mb-0">Melden Sie sich an, um fortzufahren.</p>
                  </div>
                  <?php if ($error): ?>
                      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                  <?php endif; ?>
                  <form method="POST" action="login_process.php" class="pt-0">
                    <div class="form-group mb-3">
                      <label class="form-label">Benutzername</label>
                      <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label">Passwort</label>
                      <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                      <button type="submit" class="btn btn-primary">Anmelden</button>
                    </div>
                  </form>
                  <div class="text-center text-muted mt-4">
                    <p class="mb-0">Noch keinen Zugang? <a class="text-primary ms-2 fw-medium" href="register.php">Registrieren</a></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-7">
        <div class="account-page-bg p-md-5 p-4">
          <div class="text-center">
            <div class="auth-image">
              <img src="img/auth-cover.svg" class="mx-auto img-fluid" alt="Login Bild">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
