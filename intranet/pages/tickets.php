<?php
require __DIR__ . '/../config.php';
// Standardmäßig nur offene Tickets anzeigen
$stmt = $pdo->prepare('SELECT id, title, status, assigned_to FROM tickets WHERE status = ? ORDER BY id DESC');
$stmt->execute(['offen']);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Tickets</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Tickets</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <div class="row mb-3 g-2">
            <div class="col-md-4"><input type="text" id="search" class="form-control" placeholder="Suchen..."></div>
            <div class="col-md-3"><input type="date" id="dateFilter" class="form-control"></div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-select">
                    <option value="offen" selected>Offen</option>
                    <option value="in_bearbeitung">In Bearbeitung</option>
                    <option value="geschlossen">Geschlossen</option>
                    <option value="all">Alle</option>
                </select>
            </div>
            <div class="col-md-2 text-end"><a href="ticket_form.php" class="btn btn-primary w-100">Neues Ticket</a></div>
        </div>
        <table class="table table-striped" id="ticketTable">
            <thead><tr><th>ID</th><th>Titel</th><th>Status</th><th>Zugewiesen an</th><th>Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?php echo $t['id']; ?></td>
                    <td><?php echo htmlspecialchars($t['title']); ?></td>
                    <td><?php echo htmlspecialchars($t['status']); ?></td>
                    <td><?php echo htmlspecialchars($t['assigned_to']); ?></td>
                    <td>
                        <a href="ticket_form.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <a href="ticket_delete.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ticket wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tickets): ?>
                <tr><td colspan="5" class="text-center">Keine Tickets vorhanden.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="footer">
    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
        <span>Copyright ©</span>
        <script>document.write(new Date().getFullYear());</script>
    </p>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const search = document.getElementById('search');
    const dateFilter = document.getElementById('dateFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tbody = document.querySelector('#ticketTable tbody');

    function loadTickets(){
        const params = new URLSearchParams({
            search: search.value,
            date: dateFilter.value,
            status: statusFilter.value
        });
        fetch('tickets_fetch.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.length === 0){
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Keine Tickets vorhanden.</td></tr>';
                    return;
                }
                data.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${t.id}</td><td>${t.title}</td><td>${t.status}</td><td>${t.assigned_to}</td><td><a href="ticket_form.php?id=${t.id}" class="btn btn-sm btn-secondary">Bearbeiten</a> <a href="ticket_delete.php?id=${t.id}" class="btn btn-sm btn-danger" onclick="return confirm('Ticket wirklich löschen?');">Löschen</a></td>`;
                    tbody.appendChild(tr);
                });
            });
    }

    search.addEventListener('input', loadTickets);
    dateFilter.addEventListener('change', loadTickets);
    statusFilter.addEventListener('change', loadTickets);
    loadTickets();
});
</script>
