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
<body class="bg-primary-subtle">
<div class="account-page">
  <div class="container-fluid p-0">
    <div class="row align-items-center g-0">
      <div class="col-xl-5">
        <div class="row">
          <div class="col-md-8 mx-auto">
            <div class="card p-4 mb-0 text-center">
              <img src="img/hoffmann-logo-light.png" alt="Hoffmann" class="mb-4" style="max-width:200px;">
              <h2>Sie wurden ausgeloggt</h2>
              <p class="mb-4">Vielen Dank für Ihren Besuch.</p>
              <a href="login.php" class="btn btn-primary">Zum Login</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-7">
        <div class="account-page-bg p-md-5 p-4">
          <div class="text-center">
            <div class="auth-image">
              <img src="silva-template/assets/source/images/svg/logout.svg" class="img-fluid" alt="Logout">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
