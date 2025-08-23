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
</head>
<body>
<h2>Registrierung</h2>
<?php if ($success): ?><p style="color:green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
    <label>Benutzername: <input type="text" name="username" required></label><br><br>
    <label>Passwort: <input type="password" name="password" required></label><br><br>
    <label>Rolle:
        <select name="role">
            <option value="kunde">Kunde</option>
            <option value="mitarbeiter">Mitarbeiter</option>
        </select>
    </label><br><br>
    <button type="submit">Registrieren</button>
</form>
<p><a href="login.php">Zum Login</a></p>
</body>
</html>
