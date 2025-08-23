<?php
require __DIR__ . '/../config.php';

// Load order data from JSON
$raw = file_get_contents(__DIR__ . '/../json/bestellungen.json');
$data = json_decode($raw, true) ?? [];
$orders = [];

// Collect orders (Belegart 2200) and basic metrics
foreach ($data as $entry) {
    if (($entry['Metadaten']['Belegart'] ?? '') !== '2200') {
        continue;
    }
    $orderNo   = $entry['Belegnummer'];
    $orderedAt = $entry['Metadaten']['Belegdatum'] ?? '';
    $title     = $entry['Metadaten']['Betreff'] ?? '';

    $orderedQty = 0;
    $totalNet   = 0.0;
    foreach ($entry['Produkte'] as $prod) {
        $qty   = (int)($prod['Menge'] ?? 0);
        $price = (float)($prod['Einzelpreis'] ?? 0);
        $orderedQty += $qty;
        $totalNet   += $qty * $price;
    }
    if (!$totalNet) {
        $totalNet = (float)($entry['Metadaten']['BetragNetto'] ?? 0);
    }

    $orders[$orderNo] = [
        'title'       => $title,
        'orderNo'     => $orderNo,
        'orderedAt'   => $orderedAt,
        'orderedQty'  => $orderedQty,
        'deliveredQty'=> 0,
        // Placeholder cost data to mirror the plugin fields
        'air'         => 0,
        'airUsd'      => 0,
        'custom'      => 0,
        'stamps'      => 0,
        'totalEur'    => round($totalNet, 2),
        'totalUsd'    => round($totalNet, 2),
    ];
}

// Aggregate delivered quantities from Lieferscheine (Belegart 2900)
foreach ($data as $entry) {
    if (($entry['Metadaten']['Belegart'] ?? '') === '2900') {
        $parent = $entry['Metadaten']['Vorbelegnummer'] ?? '';
        if (isset($orders[$parent])) {
            foreach ($entry['Produkte'] as $prod) {
                $orders[$parent]['deliveredQty'] += (int)($prod['Menge'] ?? 0);
            }
        }
    }
}

// Finalize delivered percentage
foreach ($orders as &$o) {
    $o['delivered'] = $o['orderedQty'] > 0
        ? round($o['deliveredQty'] / max(1, $o['orderedQty']) * 100)
        : 0;
}
unset($o);

$json = json_encode(array_values($orders));
?>

<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Bestellungen</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item">Bestellungen</li>
            </ul>
        </div>
    </div>
    <div class="container mt-4">
        <style>
        :root{--bg:#f7fafc;--fg:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#fff;--accent:#2563eb;--radius:14px;--shadow:0 6px 24px rgba(2,6,23,.06);--order-bg:#f3f4f6;--order-fg:#111827;}
        *{box-sizing:border-box}
        .hoffmann-orders-wrap{margin:36px 0}
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
        .hoffmann-orders-wrap .footer{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:16px}
        @media(max-width:990px){.hoffmann-orders-wrap .footer{grid-template-columns:1fr}}
        .hoffmann-orders-wrap .edit-btn{text-decoration:none;color:var(--accent);font-size:16px}
        </style>

        <div class="hoffmann-orders-wrap">
            <header>
                <h1>Bestellungen – Übersicht</h1>
                <div class="sub">Produkte, Stückpreise, Steuermarken &amp; Lieferstatus – mit Suche &amp; Datumsfilter</div>
            </header>
            <section class="toolbar">
                <input id="hoff-q" class="input" placeholder="Suchen: Titel oder Bestellnr…" />
                <label class="muted" style="display:flex;align-items:center;gap:8px">Von <input id="hoff-from" type="date" class="input" /></label>
                <label class="muted" style="display:flex;align-items:center;gap:8px">Bis <input id="hoff-to" type="date" class="input" /></label>
                <select id="hoff-sort" title="Sortierung">
                    <option value="date_desc">Neueste zuerst</option>
                    <option value="date_asc">Älteste zuerst</option>
                    <option value="value_desc">Warenwert ↓</option>
                    <option value="value_asc">Warenwert ↑</option>
                </select>
                <button id="hoff-reset" class="btn">Zurücksetzen</button>
                <button id="hoff-export" class="btn primary">CSV Export</button>
            </section>
            <section class="grid grid-4" id="hoff-kpis">
                <div class="kpi"><div class="label">Summe Warenwert</div><div class="val" id="hoff-kpi-total">€ 0,00 / $0.00</div></div>
                <div class="kpi"><div class="label">Ø Stückpreis Aircargo</div><div class="val" id="hoff-kpi-air">€ 0,00 / $0.00</div></div>
                <div class="kpi"><div class="label">Ø Stückpreis Zollabwicklung</div><div class="val" id="hoff-kpi-custom">€ 0,00</div></div>
                <div class="kpi"><div class="label">Anzahl Bestellungen</div><div class="val" id="hoff-kpi-count">0</div></div>
            </section>
            <section class="card" style="margin-top:16px">
                <h2>Bestellliste</h2>
                <div class="body table">
                    <table id="hoff-tbl">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Bestellnr</th>
                                <th>Bestelldatum</th>
                                <th class="right">Stückpreis Aircargo</th>
                                <th class="right">Stückpreis Zoll</th>
                                <th class="right">Steuermarken</th>
                                <th class="right">Warenwert</th>
                                <th class="right">Geliefert (%)</th>
                                <th class="right">Bearbeiten</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="body muted" id="hoff-rowsum"></div>
            </section>
            <section class="footer">
                <div class="card"><h2>Hinweis</h2><div class="body muted">Die Kosten setzen sich aus Aircargo, Zollabwicklung und Steuermarken zusammen. Gesamtwert = Summe aller Positionen × Stückzahl.</div></div>
                <div class="card"><h2>Datenquellen</h2><div class="body muted">ERP / JSON – mappen auf: <code>{ title, orderNo, orderedAt, air, airUsd, custom, stamps, totalUsd, totalEur, delivered }</code>.</div></div>
                <div class="card"><h2>Quick-Stats</h2><div class="body"><span id="hoff-qsTop"></span> <span id="hoff-qsAvg"></span></div></div>
            </section>
        </div>
        <script>
        const EUR = new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
        const USD = new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'});
        const fmtDate = d => new Date(d).toLocaleDateString('de-DE');
        const DATA = <?php echo $json; ?>;
        const state = {q:'',from:'',to:'',sort:'date_desc'};
        const moneyEur = n => EUR.format(n||0);
        const moneyUsd = n => USD.format(n||0);
        function within(d,from,to){
            const t = +new Date(d);
            return (!from||t>=+new Date(from))&&(!to||t<=+new Date(to)+86400000-1);
        }
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
                    case 'value_desc': return b.totalEur-a.totalEur;
                    case 'value_asc': return a.totalEur-b.totalEur;
                    default: return +new Date(b.orderedAt)-+new Date(a.orderedAt);
                }
            });
            return rows;
        }
        function render(){
            const rows=getFiltered();
            const tbody=document.querySelector('#hoff-tbl tbody');
            tbody.innerHTML='';
            let sumTotalEur=0,sumTotalUsd=0,airSum=0,airUsdSum=0,customSum=0;
            rows.forEach(r=>{
                sumTotalEur+=r.totalEur; sumTotalUsd+=r.totalUsd;
                airSum+=r.air; airUsdSum+=r.airUsd; customSum+=r.custom;
                const tr=document.createElement('tr');
                tr.innerHTML=`<td><strong>${r.title}</strong></td><td><span class="orderNo">${r.orderNo}</span></td><td>${fmtDate(r.orderedAt)}</td><td class="right">${moneyEur(r.air)}<br><span class="muted">${moneyUsd(r.airUsd)}</span></td><td class="right">${moneyEur(r.custom)}</td><td class="right">${moneyEur(r.stamps)}</td><td class="right">${moneyEur(r.totalEur)}<br><span class="muted">${moneyUsd(r.totalUsd)}</span></td><td class="right">${r.delivered}%</td><td class="right"><a href="dashboard.php?page=order_edit&orderNo=${r.orderNo}" class="edit-btn" title="Bearbeiten">✎</a></td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('hoff-rowsum').textContent=`${rows.length} Bestellungen angezeigt`;
            document.getElementById('hoff-kpi-total').textContent=`${moneyEur(sumTotalEur)} / ${moneyUsd(sumTotalUsd)}`;
            document.getElementById('hoff-kpi-air').textContent=`${moneyEur(rows.length?airSum/rows.length:0)} / ${moneyUsd(rows.length?airUsdSum/rows.length:0)}`;
            document.getElementById('hoff-kpi-custom').textContent=moneyEur(rows.length?customSum/rows.length:0);
            document.getElementById('hoff-kpi-count').textContent=rows.length.toString();
            const top=rows.reduce((m,r)=>r.totalEur>m.totalEur?r:m,rows[0]||{title:'—',totalEur:0});
            document.getElementById('hoff-qsTop').textContent=`Top-Order: ${top.title} (${moneyEur(top.totalEur)})`;
            document.getElementById('hoff-qsAvg').textContent=`Ø Warenwert: ${rows.length?moneyEur(sumTotalEur/rows.length):moneyEur(0)}`;
        }
        function exportCSV(){
            const rows=getFiltered();
            const header=['Titel','Bestellnr','Bestelldatum','Stückpreis Aircargo EUR','Stückpreis Aircargo USD','Stückpreis Zoll','Steuermarken','Warenwert USD','Warenwert EUR','Geliefert %'];
            const out=[header.join(';')].concat(rows.map(r=>[r.title,r.orderNo,r.orderedAt,r.air.toFixed(2).replace('.',','),r.airUsd.toFixed(2).replace('.',','),r.custom.toFixed(2).replace('.',','),r.stamps.toFixed(2).replace('.',','),r.totalUsd.toFixed(2).replace('.',','),r.totalEur.toFixed(2).replace('.',','),r.delivered].join(';'))).join('\n');
            const blob=new Blob([out],{type:'text/csv;charset=utf-8;'});
            const url=URL.createObjectURL(blob);
            const a=document.createElement('a');a.href=url;a.download='bestellungen_export.csv';a.click();URL.revokeObjectURL(url);
        }
        document.getElementById('hoff-q').addEventListener('input',e=>{state.q=e.target.value;render()});
        document.getElementById('hoff-from').addEventListener('change',e=>{state.from=e.target.value;render()});
        document.getElementById('hoff-to').addEventListener('change',e=>{state.to=e.target.value;render()});
        document.getElementById('hoff-sort').addEventListener('change',e=>{state.sort=e.target.value;render()});
        document.getElementById('hoff-reset').addEventListener('click',()=>{
            state.q='';state.from='';state.to='';state.sort='date_desc';
            document.getElementById('hoff-q').value='';
            document.getElementById('hoff-from').value='';
            document.getElementById('hoff-to').value='';
            document.getElementById('hoff-sort').value='date_desc';
            render();
        });
        document.getElementById('hoff-export').addEventListener('click',exportCSV);
        render();
        </script>
    </div>
</div>
<footer class="footer">
    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
        <span>Copyright ©</span>
        <script>document.write(new Date().getFullYear());</script>
    </p>
</footer>

