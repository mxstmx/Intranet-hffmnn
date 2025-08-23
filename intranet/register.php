<?php
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
    <title>Registrierung - Hofmann Intranet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">Registrierung</h2>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Benutzername</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Passwort</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Rolle</label>
                <select name="role" class="form-select">
                    <option value="kunde">Kunde</option>
                    <option value="mitarbeiter">Mitarbeiter</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrieren</button>
        </form>
        <p class="mt-3 text-center"><a href="login.php">Zum Login</a></p>
    </div>
</div>
</body>
</html>
