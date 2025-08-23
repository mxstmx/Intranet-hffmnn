<?php
require __DIR__ . '/../config.php';
$items = $pdo->query('SELECT gruppe, artikelnummer, artikel, bestand, reserviert, bestellt, information FROM bestand')->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query('SELECT DISTINCT gruppe FROM bestand ORDER BY gruppe')->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Bestand</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Bestand</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <div class="row mb-3 g-2">
            <div class="col-md-4"><input type="text" id="search" class="form-control" placeholder="Suchen..."></div>
            <div class="col-md-4">
                <select id="groupFilter" class="form-select">
                    <option value="">Alle Gruppen</option>
                    <?php foreach ($groups as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <table class="table table-striped" id="bestandTable">
            <thead><tr><th>Gruppe</th><th>Artikelnummer</th><th>Artikel</th><th class="text-end">Bestand</th><th class="text-end">Reserviert</th><th class="text-end">Bestellt</th><th>Info</th></tr></thead>
            <tbody>
            <?php foreach ($items as $i): ?>
                <tr>
                    <td><?php echo htmlspecialchars($i['gruppe']); ?></td>
                    <td><?php echo htmlspecialchars($i['artikelnummer']); ?></td>
                    <td><?php echo htmlspecialchars($i['artikel']); ?></td>
                    <td class="text-end"><?php echo (int)$i['bestand']; ?></td>
                    <td class="text-end"><?php echo (int)$i['reserviert']; ?></td>
                    <td class="text-end"><?php echo (int)$i['bestellt']; ?></td>
                    <td><?php echo htmlspecialchars($i['information']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="7" class="text-center">Keine Daten vorhanden.</td></tr>
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
    const groupFilter = document.getElementById('groupFilter');
    const rows = document.querySelectorAll('#bestandTable tbody tr');
    function filter(){
        const term = search.value.toLowerCase();
        const grp = groupFilter.value;
        rows.forEach(r => {
            const matchText = r.textContent.toLowerCase().includes(term);
            const matchGroup = !grp || r.children[0].textContent === grp;
            r.style.display = matchText && matchGroup ? '' : 'none';
        });
    }
    search.addEventListener('input', filter);
    groupFilter.addEventListener('change', filter);
});
</script>
