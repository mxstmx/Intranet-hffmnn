<?php
session_start();
require __DIR__ . '/config.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php?error=Keine+Zugriffsrechte');
    exit();
}

// Daten aus der Datenbank laden und nach Eltern/Kind gruppieren
$rows = $pdo->query('SELECT belegnummer, belegdatum, belegart, vorbelegnummer, betreff, betrag FROM bestellungen')->fetchAll(PDO::FETCH_ASSOC);
$orders = [];
foreach ($rows as $r) {
    if ($r['belegart'] === '2200') {
        $orders[$r['belegnummer']] = [
            'title'    => $r['betreff'],
            'orderNo'  => $r['belegnummer'],
            'orderedAt'=> $r['belegdatum'],
            'betrag'   => (float)$r['betrag'],
            'children' => []
        ];
    }
}
foreach ($rows as $r) {
    if ($r['belegart'] === '2900') {
        $parent = $r['vorbelegnummer'];
        $orders[$parent]['children'][] = [
            'title'    => $r['betreff'],
            'orderNo'  => $r['belegnummer'],
            'orderedAt'=> $r['belegdatum'],
            'betrag'   => (float)$r['betrag']
        ];
    }
}
$data = array_values($orders);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellungen - Hofmann Intranet</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root{--bg:#f7fafc;--fg:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#fff;--accent:#2563eb;--radius:14px;--shadow:0 6px 24px rgba(2,6,23,.06);--order-bg:#f3f4f6;--order-fg:#111827;}*{box-sizing:border-box}
    .hoffmann-orders-wrap{margin:36px auto;max-width:1200px;padding:0 16px}
    .hoffmann-orders-wrap h1{margin:0 0 6px;font-size:28px;font-weight:700}
    .hoffmann-orders-wrap .sub{color:var(--muted);font-size:14px}
    .hoffmann-orders-wrap .toolbar{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
    .hoffmann-orders-wrap .input,.hoffmann-orders-wrap select,.hoffmann-orders-wrap .btn{border:1px solid var(--line);background:#fff;border-radius:10px;height:40px;padding:8px 12px;font-size:14px}
    .hoffmann-orders-wrap .btn{cursor:pointer}
    .hoffmann-orders-wrap .btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
    .hoffmann-orders-wrap .grid{display:grid;gap:16px}
    .hoffmann-orders-wrap .grid-4{grid-template-columns:repeat(4,1fr)}
    @media(max-width:990px){.hoffmann-orders-wrap .grid-4{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:640px){.hoffmann-orders-wrap .grid-4{grid-template-columns:1fr}}
    .hoffmann-orders-wrap .kpi{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
    .hoffmann-orders-wrap .kpi .label{font-size:12px;color:var(--muted)}
    .hoffmann-orders-wrap .kpi .val{font-size:22px;font-weight:700;margin-top:6px}
    .hoffmann-orders-wrap .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}
    .hoffmann-orders-wrap .card h2{margin:0;padding:14px 16px;border-bottom:1px solid var(--line);font-size:15px;color:#374151}
    .hoffmann-orders-wrap .card .body{padding:16px}
    .hoffmann-orders-wrap .table{overflow:auto;border-radius:var(--radius);border:1px solid var(--line);background:#fff}
    .hoffmann-orders-wrap table{width:100%;border-collapse:collapse;font-size:14px}
    .hoffmann-orders-wrap th,.hoffmann-orders-wrap td{padding:12px 14px;border-top:1px solid var(--line);text-align:left}
    .hoffmann-orders-wrap thead th{background:#f3f4f6;text-transform:uppercase;font-size:11px;letter-spacing:.06em;color:#6b7280}
    .hoffmann-orders-wrap tbody tr:nth-child(odd){background:#fbfdff}
    .hoffmann-orders-wrap .right{text-align:right}
    .hoffmann-orders-wrap .orderNo{font-weight:700;background:var(--order-bg);color:var(--order-fg);padding:4px 8px;border-radius:6px;display:inline-block;text-decoration:none}
    .hoffmann-orders-wrap .orderNo:hover{background:var(--accent);color:#fff}
    .hoffmann-orders-wrap .muted{color:var(--muted);font-size:13px}
    .hoffmann-orders-wrap tr.child{display:none;background:#fff}
    .hoffmann-orders-wrap tr.child td:first-child{padding-left:32px}
    .hoffmann-orders-wrap .footer{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:16px}
    @media(max-width:990px){.hoffmann-orders-wrap .footer{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="hoffmann-orders-wrap">
  <header><h1>Bestellungen – Übersicht</h1><div class="sub">Produkte & Warenwert – mit Suche & Datumsfilter</div></header>
  <section class="toolbar">
    <input id="hoff-q" class="input" placeholder="Suchen: Titel oder Bestellnr…" />
    <label class="muted" style="display:flex;align-items:center;gap:8px">Von <input id="hoff-from" type="date" class="input" /></label>
    <label class="muted" style="display:flex;align-items:center;gap:8px">Bis <input id="hoff-to" type="date" class="input" /></label>
    <select id="hoff-sort" title="Sortierung"><option value="date_desc">Neueste zuerst</option><option value="date_asc">Älteste zuerst</option><option value="value_desc">Warenwert ↓</option><option value="value_asc">Warenwert ↑</option></select>
    <button id="hoff-reset" class="btn">Zurücksetzen</button>
  </section>
  <section class="grid grid-4" id="hoff-kpis">
    <div class="kpi"><div class="label">Summe Warenwert</div><div class="val" id="hoff-kpi-total">€ 0,00</div></div>
    <div class="kpi"><div class="label">Ø Stückpreis Aircargo</div><div class="val" id="hoff-kpi-air">€ 0,00</div></div>
    <div class="kpi"><div class="label">Ø Stückpreis Zollabwicklung</div><div class="val" id="hoff-kpi-custom">€ 0,00</div></div>
    <div class="kpi"><div class="label">Anzahl Bestellungen</div><div class="val" id="hoff-kpi-count">0</div></div>
  </section>
  <section class="card" style="margin-top:16px">
    <h2>Bestellliste</h2>
    <div class="body table">
      <table id="hoff-tbl"><thead><tr><th>Titel</th><th>Belegnr</th><th>Datum</th><th class="right">Betrag</th></tr></thead><tbody></tbody></table>
    </div>
    <div class="body muted" id="hoff-rowsum"></div>
  </section>
</div>
<div class="text-center mt-4"><a href="dashboard.php" class="btn btn-secondary">Zurück</a></div>
<script>
const EUR = new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
const DATA = <?php echo json_encode($data); ?>;
const state = {q:'',from:'',to:'',sort:'date_desc'};
function within(d,from,to){const t=+new Date(d);return(!from||t>=+new Date(from))&&(!to||t<=+new Date(to)+86400000-1);} 
function getFiltered(){
  let rows = DATA.filter(r=>{
    const txt=(r.title+" "+r.orderNo).toLowerCase();
    const matches=!state.q||txt.includes(state.q.toLowerCase());
    const inRange=within(r.orderedAt,state.from,state.to);
    return matches&&inRange;
  });
  rows.sort((a,b)=>{
    switch(state.sort){
      case 'date_asc': return +new Date(a.orderedAt)-+new Date(b.orderedAt);
      case 'value_desc': return b.betrag-a.betrag;
      case 'value_asc': return a.betrag-b.betrag;
      default: return +new Date(b.orderedAt)-+new Date(a.orderedAt);
    }
  });
  return rows;
}
function render(){
  const rows=getFiltered();
  const tbody=document.querySelector('#hoff-tbl tbody');
  tbody.innerHTML='';
  let sumTotal=0;
  rows.forEach(r=>{
    sumTotal+=r.betrag;
    const tr=document.createElement('tr');
    tr.classList.add('parent');
    tr.dataset.id=r.orderNo;
    tr.innerHTML=`<td><strong>${r.title}</strong></td><td><span class="orderNo">${r.orderNo}</span></td><td>${new Date(r.orderedAt).toLocaleDateString('de-DE')}</td><td class="right">${EUR.format(r.betrag)}</td>`;
    tbody.appendChild(tr);
    r.children.forEach(c=>{
      const cr=document.createElement('tr');
      cr.classList.add('child',`child-${r.orderNo}`);
      cr.innerHTML=`<td>↳ ${c.title}</td><td><span class="orderNo">${c.orderNo}</span></td><td>${new Date(c.orderedAt).toLocaleDateString('de-DE')}</td><td class="right">${EUR.format(c.betrag)}</td>`;
      tbody.appendChild(cr);
    });
  });
  document.getElementById('hoff-rowsum').textContent=`${rows.length} Bestellungen angezeigt`;
  document.getElementById('hoff-kpi-total').textContent=EUR.format(sumTotal);
  document.getElementById('hoff-kpi-air').textContent=EUR.format(0);
  document.getElementById('hoff-kpi-custom').textContent=EUR.format(0);
  document.getElementById('hoff-kpi-count').textContent=rows.length.toString();
  document.querySelectorAll('tr.parent').forEach(row=>{
    row.addEventListener('click',()=>{
      const id=row.dataset.id;
      document.querySelectorAll(`.child-${id}`).forEach(ch=>{
        ch.style.display=ch.style.display==='table-row'?'none':'table-row';
      });
    });
  });
}
document.getElementById('hoff-q').addEventListener('input',e=>{state.q=e.target.value;render()});
document.getElementById('hoff-from').addEventListener('change',e=>{state.from=e.target.value;render()});
document.getElementById('hoff-to').addEventListener('change',e=>{state.to=e.target.value;render()});
document.getElementById('hoff-sort').addEventListener('change',e=>{state.sort=e.target.value;render()});
document.getElementById('hoff-reset').addEventListener('click',()=>{state.q='';state.from='';state.to='';state.sort='date_desc';document.getElementById('hoff-q').value='';document.getElementById('hoff-from').value='';document.getElementById('hoff-to').value='';document.getElementById('hoff-sort').value='date_desc';render();});
render();
</script>
</body>
</html>
