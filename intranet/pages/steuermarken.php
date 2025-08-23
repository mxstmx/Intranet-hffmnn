<?php
session_start();
if(!isset($_SESSION['username'])){ header('Location: login.php'); exit(); }
require __DIR__ . '/../config.php';
?>
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Steuermarken</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Steuermarken</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6"><canvas id="valueChart"></canvas></div>
            <div class="col-md-6"><canvas id="distributionChart"></canvas></div>
        </div>
        <div class="row mb-3 g-2">
            <div class="col-md-3"><input type="text" id="search" class="form-control" placeholder="Suchen..."></div>
            <div class="col-md-3"><input type="date" id="fromDate" class="form-control"></div>
            <div class="col-md-3"><input type="date" id="toDate" class="form-control"></div>
            <div class="col-md-3 text-end"><a href="steuermarke_form.php" class="btn btn-primary w-100">Neue Steuermarke</a></div>
        </div>
        <table class="table table-striped" id="markTable">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Warenwert gesamt</th><th>Wert je Marke</th><th>Datum</th><th>Stückzahl</th><th>Zugeordnet</th><th>Aktionen</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<footer class="footer">
    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
        <span>Copyright ©</span>
        <script>document.write(new Date().getFullYear());</script>
    </p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const search=document.getElementById('search');
    const fromDate=document.getElementById('fromDate');
    const toDate=document.getElementById('toDate');
    const tbody=document.querySelector('#markTable tbody');
    let barChart,pieChart;

    const formatCurrency = val => {
        const num = parseFloat(val);
        if (isNaN(num)) return '';
        return num.toLocaleString('de-DE',{style:'currency',currency:'EUR'});
    };

    function renderCharts(data){
        const labels=data.map(d=>d.wert_je_marke);
        const quantities=data.map(d=>parseInt(d.anzahl,10));
        if(barChart){ barChart.destroy(); }
        if(pieChart){ pieChart.destroy(); }
        barChart=new Chart(document.getElementById('valueChart'),{
            type:'bar',
            data:{labels:labels,datasets:[{label:'Stückzahl',data:quantities,backgroundColor:'#0d6efd'}]}
        });
        pieChart=new Chart(document.getElementById('distributionChart'),{
            type:'pie',
            data:{labels:labels,datasets:[{data:quantities,backgroundColor:['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#20c997','#fd7e14','#6c757d']}]} 
        });
    }

    function loadMarks(){
        const params=new URLSearchParams({search:search.value,from:fromDate.value,to:toDate.value});
        fetch('steuermarken_fetch.php?'+params.toString())
            .then(r=>r.json())
            .then(data=>{
                tbody.innerHTML='';
                if(data.length===0){
                    tbody.innerHTML='<tr><td colspan="8" class="text-center">Keine Steuermarken vorhanden.</td></tr>';
                    renderCharts([]);
                    return;
                }
                data.forEach(m=>{
                    const tr=document.createElement('tr');
                    tr.innerHTML=`<td>${m.id}</td><td>${m.name}</td><td>${formatCurrency(m.warenwert_gesamt)}</td><td>${formatCurrency(m.wert_je_marke)}</td><td>${m.datum||''}</td><td>${m.anzahl}</td><td>${m.betreffe||''}</td><td><a href="steuermarke_form.php?id=${m.id}" class="btn btn-sm btn-secondary">Bearbeiten</a> <a href="steuermarke_delete.php?id=${m.id}" class="btn btn-sm btn-danger" onclick="return confirm('Steuermarke löschen?');">Löschen</a></td>`;
                    tbody.appendChild(tr);
                });
                renderCharts(data);
            });
    }
    search.addEventListener('input',loadMarks);
    fromDate.addEventListener('change',loadMarks);
    toDate.addEventListener('change',loadMarks);
    loadMarks();
});
</script>
