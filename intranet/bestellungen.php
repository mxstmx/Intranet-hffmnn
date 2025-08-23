<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/../wp-content/plugins/hoffmann-kundenportal/lib/number-utils.php';

// Query orders similar to plugin shortcode
$args = [
    'post_type'      => 'bestellungen',
    'post_parent'    => 0,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'tax_query'      => [
        [
            'taxonomy' => 'bestellart',
            'field'    => 'name',
            'terms'    => '2200',
        ],
    ],
];

$query = new WP_Query($args);
$data = [];
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $pid   = get_the_ID();
        $datum = get_post_meta($pid, 'belegdatum', true);
        $title = get_post_meta($pid, 'betreff', true);
        $prod_meta = get_post_meta($pid, 'produkte', true);
        if (!is_array($prod_meta)) {
            $prod_meta = json_decode($prod_meta, true);
        }
        $products = [];
        if (is_array($prod_meta)) {
            foreach ($prod_meta as $item) {
                if (!is_array($item)) { continue; }
                $art = $item['Artikelnummer'] ?? '';
                if (!$art) { continue; }
                $products[$art] = [
                    'ordered'   => isset($item['Menge']) ? (int)$item['Menge'] : 0,
                    'preis'     => isset($item['Einzelpreis']) ? (float)$item['Einzelpreis'] : 0.0,
                    'delivered' => 0,
                ];
            }
        }
        $children = get_posts([
            'post_type'      => 'bestellungen',
            'post_parent'    => $pid,
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => 'bestellart',
                    'field'    => 'name',
                    'terms'    => '2900',
                ],
            ],
        ]);
        $total_delivered_qty = 0;
        $total_air = 0; $total_air_usd = 0; $total_zoll = 0;
        foreach ($children as $child) {
            $cid = $child->ID;
            $total_air     += hoffmann_to_float(get_post_meta($cid, 'air_cargo_kosten', true));
            $total_air_usd += hoffmann_to_float(get_post_meta($cid, 'air_cargo_kosten_usd', true));
            $total_zoll    += hoffmann_to_float(get_post_meta($cid, 'zoll_abwicklung_kosten', true));
            $c_prod = get_post_meta($cid, 'produkte', true);
            if (!is_array($c_prod)) { $c_prod = json_decode($c_prod, true); }
            if (is_array($c_prod)) {
                foreach ($c_prod as $p) {
                    if (!is_array($p)) { continue; }
                    $art = $p['Artikelnummer'] ?? '';
                    $qty = isset($p['Menge']) ? (int)$p['Menge'] : 0;
                    if (!$art) { continue; }
                    if (!isset($products[$art])) {
                        $products[$art] = ['ordered' => 0, 'preis' => isset($p['Einzelpreis']) ? (float)$p['Einzelpreis'] : 0.0, 'delivered' => 0];
                    }
                    $products[$art]['delivered'] += $qty;
                    $total_delivered_qty += $qty;
                }
            }
        }
        $stm_posts = get_posts([
            'post_type'  => 'steuermarken',
            'numberposts'=> -1,
            'meta_key'   => 'bestellung_id',
            'meta_value' => $pid,
        ]);
        $total_stm = 0; $total_stm_qty = 0;
        foreach ($stm_posts as $s) {
            $total_stm += (float) get_post_meta($s->ID, 'wert', true);
            $total_stm_qty += (int) get_post_meta($s->ID, 'stueckzahl', true);
        }
        $total_ordered = 0; $total_warenwert_usd = 0;
        foreach ($products as $info) {
            $total_ordered += $info['ordered'];
            $total_warenwert_usd += $info['ordered'] * $info['preis'];
        }
        $exchange_rate = hoffmann_to_float(get_post_meta($pid, 'wechselkurs', true));
        if (!$exchange_rate) { $exchange_rate = 1.0; }
        $total_warenwert_eur = $total_warenwert_usd / $exchange_rate;
        $air_per_unit    = $total_ordered > 0 ? $total_air / $total_ordered : 0;
        $air_per_unit_usd= $total_ordered > 0 ? $total_air_usd / $total_ordered : 0;
        $zoll_per_unit   = $total_ordered > 0 ? $total_zoll / $total_ordered : 0;
        $stm_per_unit    = $total_stm_qty > 0 ? $total_stm / $total_stm_qty : 0;
        $delivered_pct   = $total_ordered > 0 ? round($total_delivered_qty / $total_ordered * 100) : 0;
        $data[] = [
            'title'     => $title,
            'orderNo'   => get_the_title(),
            'orderedAt' => $datum,
            'link'      => get_permalink($pid),
            'air'       => round($air_per_unit, 2),
            'airUsd'    => round($air_per_unit_usd, 2),
            'custom'    => round($zoll_per_unit, 2),
            'stamps'    => round($stm_per_unit, 2),
            'totalUsd'  => round($total_warenwert_usd, 2),
            'totalEur'  => round($total_warenwert_eur, 2),
            'delivered' => $delivered_pct,
        ];
    }
}
wp_reset_postdata();
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <title>Bestellungen - Hoffmann Intranet</title>
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
                <div class="page-header-title"><h5 class="m-b-10">Bestellungen</h5></div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item">Bestellungen</li>
                </ul>
            </div>
        </div>
        <div class="container mt-4">
            <div class="row g-2 mb-3">
                <div class="col-md-3"><input type="text" id="search" class="form-control" placeholder="Suchen: Titel oder Bestellnr…"></div>
                <div class="col-md-2"><input type="date" id="from" class="form-control"></div>
                <div class="col-md-2"><input type="date" id="to" class="form-control"></div>
                <div class="col-md-2">
                    <select id="sort" class="form-select">
                        <option value="date_desc">Neueste zuerst</option>
                        <option value="date_asc">Älteste zuerst</option>
                        <option value="value_desc">Warenwert ↓</option>
                        <option value="value_asc">Warenwert ↑</option>
                    </select>
                </div>
                <div class="col-md-1"><button id="reset" class="btn btn-secondary w-100">Reset</button></div>
                <div class="col-md-2"><button id="export" class="btn btn-primary w-100">CSV Export</button></div>
            </div>
            <table class="table table-striped" id="orderTable">
                <thead><tr><th>Titel</th><th>Bestellnr</th><th>Bestelldatum</th><th class="text-end">Stückpreis Aircargo</th><th class="text-end">Stückpreis Zoll</th><th class="text-end">Steuermarken</th><th class="text-end">Warenwert</th><th class="text-end">Geliefert (%)</th></tr></thead>
                <tbody></tbody>
            </table>
            <div id="rowsum" class="text-muted"></div>
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
<script>
const DATA = <?php echo wp_json_encode($data); ?>;
const EUR = new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
const USD = new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'});
const state = {q:'',from:'',to:'',sort:'date_desc'};
const tbody = document.querySelector('#orderTable tbody');
function within(d,from,to){const t=+new Date(d);return(!from||t>=+new Date(from))&&(!to||t<=+new Date(to)+86400000-1);}
function getFiltered(){
    let rows = DATA.filter(r=>{
        const txt = (r.title + ' ' + r.orderNo).toLowerCase();
        return (!state.q || txt.includes(state.q.toLowerCase())) && within(r.orderedAt,state.from,state.to);
    });
    rows.sort((a,b)=>{
        switch(state.sort){
            case 'date_asc': return +new Date(a.orderedAt) - +new Date(b.orderedAt);
            case 'value_desc': return b.totalEur - a.totalEur;
            case 'value_asc': return a.totalEur - b.totalEur;
            default: return +new Date(b.orderedAt) - +new Date(a.orderedAt);
        }
    });
    return rows;
}
function render(){
    const rows = getFiltered();
    tbody.innerHTML='';
    rows.forEach(r=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td>${r.title}</td><td><a href="${r.link}" target="_blank">${r.orderNo}</a></td><td>${new Date(r.orderedAt).toLocaleDateString('de-DE')}</td><td class="text-end">${EUR.format(r.air)}<br><span class="text-muted">${USD.format(r.airUsd)}</span></td><td class="text-end">${EUR.format(r.custom)}</td><td class="text-end">${EUR.format(r.stamps)}</td><td class="text-end">${EUR.format(r.totalEur)}<br><span class="text-muted">${USD.format(r.totalUsd)}</span></td><td class="text-end">${r.delivered}</td>`;
        tbody.appendChild(tr);
    });
    document.getElementById('rowsum').textContent = rows.length + ' Bestellungen angezeigt';
}
function exportCSV(){
    const rows = getFiltered();
    const header=['Titel','Bestellnr','Bestelldatum','Stückpreis Aircargo EUR','Stückpreis Aircargo USD','Stückpreis Zoll','Steuermarken','Warenwert USD','Warenwert EUR','Geliefert %'];
    const out=[header.join(';')].concat(rows.map(r=>[r.title,r.orderNo,r.orderedAt,r.air.toFixed(2).replace('.',','),r.airUsd.toFixed(2).replace('.',','),r.custom.toFixed(2).replace('.',','),r.stamps.toFixed(2).replace('.',','),r.totalUsd.toFixed(2).replace('.',','),r.totalEur.toFixed(2).replace('.',','),r.delivered].join(';'))).join('\n');
    const blob=new Blob([out],{type:'text/csv;charset=utf-8;'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');a.href=url;a.download='bestellungen_export.csv';a.click();URL.revokeObjectURL(url);
}

document.getElementById('search').addEventListener('input',e=>{state.q=e.target.value;render();});
document.getElementById('from').addEventListener('change',e=>{state.from=e.target.value;render();});
document.getElementById('to').addEventListener('change',e=>{state.to=e.target.value;render();});
document.getElementById('sort').addEventListener('change',e=>{state.sort=e.target.value;render();});
document.getElementById('reset').addEventListener('click',()=>{state.q='';state.from='';state.to='';state.sort='date_desc';document.getElementById('search').value='';document.getElementById('from').value='';document.getElementById('to').value='';document.getElementById('sort').value='date_desc';render();});
document.getElementById('export').addEventListener('click',exportCSV);
render();
</script>
</body>
</html>
