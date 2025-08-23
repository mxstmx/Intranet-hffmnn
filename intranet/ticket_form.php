<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
require __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = $description = $status = $assigned_to = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($ticket = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $title = $ticket['title'];
        $description = $ticket['description'];
        $status = $ticket['status'];
        $assigned_to = $ticket['assigned_to'];
    } else {
        $id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'offen';
    $assigned_to = $_POST['assigned_to'] ?? '';
    if ($title && $description && $assigned_to) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE tickets SET title=:title, description=:description, status=:status, assigned_to=:assigned_to WHERE id=:id');
            $stmt->execute([':title'=>$title, ':description'=>$description, ':status'=>$status, ':assigned_to'=>$assigned_to, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO tickets (title, description, status, assigned_to, created_by, created_at) VALUES (:title,:description,:status,:assigned_to,:created_by,:created_at)');
            $stmt->execute([
                ':title'=>$title,
                ':description'=>$description,
                ':status'=>$status,
                ':assigned_to'=>$assigned_to,
                ':created_by'=>$_SESSION['username'],
                ':created_at'=>date('Y-m-d H:i:s')
            ]);
            $id = $pdo->lastInsertId();
        }
        header('Location: tickets.php');
        exit();
    }
}

$employees = $pdo->query("SELECT username FROM users WHERE role='mitarbeiter' OR role='admin' ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $id ? 'Ticket bearbeiten' : 'Ticket erstellen'; ?> - Hoffmann Intranet</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/theme.min.css" />
    <style>html.minimenu .nxl-header{left:0}</style>
</head>
<body>
<?php include 'menu.php'; ?>
<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title"><h5 class="m-b-10">Tickets</h5></div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="tickets.php">Tickets</a></li>
                    <li class="breadcrumb-item"><?php echo $id ? 'Bearbeiten' : 'Neu'; ?></li>
                </ul>
            </div>
        </div>
        <div class="container mt-4">
            <h2><?php echo $id ? 'Ticket bearbeiten' : 'Neues Ticket'; ?></h2>
            <form method="POST" class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Titel</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php
                        $statuses = ['offen' => 'Offen', 'in_bearbeitung' => 'In Bearbeitung', 'geschlossen' => 'Geschlossen'];
                        foreach ($statuses as $val => $label) {
                            $sel = $status === $val ? 'selected' : '';
                            echo "<option value='$val' $sel>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Zugewiesen an</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">-- auswählen --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo htmlspecialchars($emp); ?>" <?php echo $emp === $assigned_to ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Speichern</button>
                <a href="tickets.php" class="btn btn-secondary">Abbrechen</a>
            </form>
        </div>
    </div>
    <footer class="footer">
        <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
            <span>Copyright ©</span>
            <script>document.write(new Date().getFullYear());</script>
        </p>
    </footer>
</main>
<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>
