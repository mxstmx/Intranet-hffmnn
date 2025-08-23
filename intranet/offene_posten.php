<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['mitarbeiter','admin'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Offene Posten - Hofmann Intranet</title>
<link rel="stylesheet" href="style.css">
<style>
.opv-table-wrapper { overflow-x: auto; }
@media (max-width: 600px) {
    .opv-table-wrapper th, .opv-table-wrapper td { white-space: nowrap; }
}
#offene-posten-filter { margin:20px 0; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
<div class="container">
<h2 class="mb-3">Offene Posten</h2>
<div id="offene-posten-filter">
    <label for="op-kunde-select">Kunde:</label>
    <select id="op-kunde-select"><option value="">Alle Kunden</option></select>
    <button id="op-download-btn" class="btn btn-secondary ms-3">PDF herunterladen</button>
</div>
<div class="opv-table-wrapper">
<table id="offene-posten-tabelle" class="table table-striped">
    <thead>
        <tr>
            <th>Kunde</th>
            <th>Rechnungsnr</th>
            <th>Datum</th>
            <th>Produkttyp</th>
            <th class="text-end">Betrag Gesamt</th>
            <th class="text-end">Bisher gezahlt</th>
            <th class="text-end">Noch zu zahlen</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>
<div id="op-summe" class="mt-2">
    <strong>Gesamt offener Betrag:</strong> <span id="op-summe-wert">0,00 €</span>
</div>
<a href="dashboard.php" class="btn btn-secondary mt-4">Zurück</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const remote = 'https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json';
    const local  = 'json/offene_posten.json';
    let alleDaten = {};
    const kundeSelect = document.getElementById('op-kunde-select');
    const tbody = document.querySelector('#offene-posten-tabelle tbody');
    const sumField = document.getElementById('op-summe-wert');
    function renderKundenDropdown(){
        const kunden = Object.keys(alleDaten).sort();
        kunden.forEach(name => {
            const opt=document.createElement('option');
            opt.value=name; opt.textContent=name; kundeSelect.appendChild(opt);
        });
    }
    function renderTabelle(filterName=''){
        tbody.innerHTML='';
        let summe=0;
        const kunden = filterName ? [filterName] : Object.keys(alleDaten);
        kunden.forEach(name=>{
            alleDaten[name].forEach(eintrag=>{
                const tr=document.createElement('tr');
                tr.innerHTML=`<td>${name}</td><td>${eintrag.Rechnungsnr}</td><td>${eintrag.Datum}</td><td>${eintrag.Produkttyp}</td><td class="text-end">${parseFloat(eintrag["Betrag Gesamt"]).toLocaleString('de-DE')} €</td><td class="text-end">${parseFloat(eintrag["Bisher gezahlt"]).toLocaleString('de-DE')} €</td><td class="text-end">${parseFloat(eintrag["Noch zu zahlen"]).toLocaleString('de-DE')} €</td>`;
                tbody.appendChild(tr);
                summe += parseFloat(eintrag["Noch zu zahlen"]);
            });
        });
        sumField.textContent = summe.toLocaleString('de-DE',{minimumFractionDigits:2}) + ' €';
    }
    kundeSelect.addEventListener('change', ()=>{renderTabelle(kundeSelect.value);});
    function load(url){
        return fetch(url).then(r=>r.json()).catch(()=>null);
    }
    load(remote).then(data=>{if(!data) return load(local); return data;}).then(data=>{
        if(!data){tbody.innerHTML='<tr><td colspan="7">Fehler beim Laden.</td></tr>';return;}
        alleDaten=data; renderKundenDropdown(); renderTabelle();
    });
    document.getElementById('op-download-btn').addEventListener('click',()=>{
        const { jsPDF } = window.jspdf;
        const doc=new jsPDF({orientation:'landscape'});
        const head=[["Kunde","Rechnungsnr","Datum","Produkttyp","Betrag Gesamt","Bisher gezahlt","Noch zu zahlen"]];
        const body=[];
        document.querySelectorAll('#offene-posten-tabelle tbody tr').forEach(row=>{
            const cells=row.querySelectorAll('td');
            const rowData=[];
            cells.forEach((cell,idx)=>{let text=cell.textContent;if(idx>=4)text={content:text,styles:{halign:'right'}};rowData.push(text);});
            body.push(rowData);
        });
        doc.autoTable({head,body});
        const date=new Date().toISOString().split('T')[0];
        doc.save(`OPV-Kunden-${date}.pdf`);
    });
});
</script>
</body>
</html>
