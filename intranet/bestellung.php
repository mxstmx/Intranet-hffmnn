<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/../wp-content/plugins/hoffmann-kundenportal/lib/number-utils.php';
require_once __DIR__ . '/../wp-content/plugins/hoffmann-kundenportal/lib/produkte-metabox.php';

$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pid || get_post_type($pid) !== 'bestellungen') {
    die('Bestellung nicht gefunden');
}

$title = get_the_title($pid);
$exchange_rate = hoffmann_to_float(get_post_meta($pid, 'wechselkurs', true));
if (!$exchange_rate) { $exchange_rate = 1.0; }

$data = get_post_meta($pid, 'produkte', true);
if (!is_array($data)) {
    $data = json_decode($data, true);
}
$products = [];
if (is_array($data)) {
    foreach ($data as $item) {
        if (!is_array($item)) { continue; }
        $art = $item['Artikelnummer'] ?? '';
        if (!$art) { continue; }
        $products[$art] = [
            'bezeichnung' => $item['Bezeichnung'] ?? '',
            'ordered'     => isset($item['Menge']) ? (int)$item['Menge'] : 0,
            'preis'       => isset($item['Einzelpreis']) ? (float)$item['Einzelpreis'] : 0.0,
            'delivered'   => 0,
        ];
    }
}

$children = get_posts([
    'post_type'      => 'bestellungen',
    'post_parent'    => $pid,
    'posts_per_page' => -1,
    'tax_query'      => [[
        'taxonomy' => 'bestellart',
        'field'    => 'name',
        'terms'    => '2900',
    ]],
]);

$total_delivered_qty = 0;
$total_air = 0;
$total_air_usd = 0;
$total_zoll = 0;
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
                $products[$art] = [
                    'bezeichnung' => $p['Bezeichnung'] ?? '',
                    'ordered'     => 0,
                    'preis'       => isset($p['Einzelpreis']) ? (float)$p['Einzelpreis'] : 0.0,
                    'delivered'   => 0,
                ];
            }
            $products[$art]['delivered'] += $qty;
            $total_delivered_qty += $qty;
        }
    }
}

$total_ordered = 0;
$total_delivered = 0;
$total_warenwert_usd = 0;
foreach ($products as &$info) {
    $total_ordered   += $info['ordered'];
    $total_delivered += $info['delivered'];
    $total_warenwert_usd += $info['ordered'] * $info['preis'];
}
unset($info);
$total_warenwert = $total_warenwert_usd / $exchange_rate;

$stm_posts = get_posts([
    'post_type'  => 'steuermarken',
    'numberposts'=> -1,
    'meta_key'   => 'bestellung_id',
    'meta_value' => $pid,
]);
$total_stm = 0;
foreach ($stm_posts as $s) {
    $total_stm += (float) get_post_meta($s->ID, 'wert', true);
}

$lieferscheine = get_posts([
    'post_type'   => 'bestellungen',
    'post_parent' => $pid,
    'numberposts' => -1,
    'tax_query'   => [[
        'taxonomy' => 'bestellart',
        'field'    => 'name',
        'terms'    => '2900',
    ]],
]);

$air_per_unit     = $total_ordered > 0 ? $total_air / $total_ordered : 0;
$air_per_unit_usd = $total_ordered > 0 ? $total_air_usd / $total_ordered : 0;
$zoll_per_unit    = $total_ordered > 0 ? $total_zoll / $total_ordered : 0;
$stm_per_unit     = $total_ordered > 0 ? $total_stm / $total_ordered : 0;
$landed_total     = $total_warenwert + $total_air + $total_zoll + $total_stm;
$landed_per_unit  = $total_ordered > 0 ? $landed_total / $total_ordered : 0;
$supplier         = get_post_meta($pid, 'namezeile2', true);
$eta              = get_post_meta($pid, 'belegdatum', true);
$eta              = $eta ? date('Y-m-d', strtotime($eta)) : '';
$betreff          = get_post_meta($pid, 'betreff', true);
$deliv_percent    = $total_ordered > 0 ? ($total_delivered / $total_ordered) * 100 : 0;

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <title>Bestellung <?php echo esc_html($title); ?> - Hoffmann Intranet</title>
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
                <div class="page-header-title"><h5 class="m-b-10">Bestellung</h5></div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="bestellungen.php">Bestellungen</a></li>
                    <li class="breadcrumb-item">Detail</li>
                </ul>
            </div>
        </div>
        <style>
        .grid{display:grid;gap:20px}.grid-4{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
        .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
        .card h2{font-size:14px;font-weight:600;color:#6b7280;margin-bottom:8px}
        .card .value{font-size:20px;font-weight:bold}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:10px;font-size:14px;text-align:left}
        th{background:#f3f4f6;text-transform:uppercase;font-size:12px}
        tr:nth-child(even){background:#f9fafb}
        .status{display:inline-block;padding:2px 6px;border-radius:6px;font-size:12px}
        .status.offen{background:#e5e7eb;color:#374151}
        .status.teil{background:#fef3c7;color:#92400e}
        .status.voll{background:#dcfce7;color:#166534}
        .chart-placeholder{height:200px;display:flex;align-items:center;justify-content:center;color:#6b7280;border:2px dashed #d1d5db;border-radius:12px}
        .hoffmann-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;z-index:1000}
        .hoffmann-popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.2);display:none;z-index:1001;max-width:90%;width:400px}
        .hoffmann-popup .popup-close{position:absolute;top:5px;right:10px;background:none;border:none;font-size:20px;cursor:pointer}
        </style>
        <h1 style="font-size:28px;">Bestellübersicht</h1>
        <div class="subtitle">Order <strong><?php echo esc_html($title); ?></strong> · Betreff <strong><?php echo esc_html($betreff); ?></strong> · Lieferant <strong><?php echo esc_html($supplier); ?></strong> · ETA <strong><?php echo esc_html($eta); ?></strong></div>
        <div class="grid grid-4">
            <div class="card"><h2>Warenwert (bestellt)</h2><div class="value"><?php echo esc_html(number_format_i18n($total_warenwert,2)); ?> €<br><span class="text-muted">$<?php echo esc_html(number_format_i18n($total_warenwert_usd,2)); ?></span></div></div>
            <div class="card"><h2>Aircargo gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_air,2)); ?> €<br><span class="text-muted">$<?php echo esc_html(number_format_i18n($total_air_usd,2)); ?></span></div></div>
            <div class="card"><h2>Zollabwicklung gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_zoll,2)); ?> €</div></div>
            <div class="card"><h2>Steuermarken gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_stm,2)); ?> €</div></div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 2fr;margin-top:20px;">
            <div class="card">
                <h2>Lieferstatus</h2>
                <div class="chart-placeholder"><canvas id="hoffmann-bestellung-pie"></canvas></div>
                <p style="font-size:14px;margin-top:10px;">Geliefert: <?php echo esc_html(number_format($deliv_percent,2,',','.')); ?>% (<?php echo esc_html(number_format_i18n($total_delivered)); ?> / <?php echo esc_html(number_format_i18n($total_ordered)); ?> Stk)</p>
            </div>
            <div class="card">
                <h2>Kosten-Zusammenfassung</h2>
                <p>Bestellung Stckzahl: <strong><?php echo esc_html(number_format_i18n($total_ordered)); ?> Stück</strong></p>
                <p>Warenwert gesamt: <strong><?php echo esc_html(number_format_i18n($total_warenwert,2)); ?> €</strong> (<span>$<?php echo esc_html(number_format_i18n($total_warenwert_usd,2)); ?></span>)</p>
                <p>Aircargo gesamt: <strong><?php echo esc_html(number_format_i18n($total_air,2)); ?> €</strong> (<span>$<?php echo esc_html(number_format_i18n($total_air_usd,2)); ?></span>)</p>
                <p>Aircargo Stückpreis: <strong><?php echo esc_html(number_format_i18n($air_per_unit,2)); ?> €</strong> (<span>$<?php echo esc_html(number_format_i18n($air_per_unit_usd,2)); ?></span>)</p>
                <p>Zollabwicklung gesamt: <strong><?php echo esc_html(number_format_i18n($total_zoll,2)); ?> €</strong></p>
                <p>Zollabwicklung Stückpreis: <strong><?php echo esc_html(number_format_i18n($zoll_per_unit,2)); ?> €</strong></p>
                <p>Steuermarken gesamt: <strong><?php echo esc_html(number_format_i18n($total_stm,2)); ?> €</strong></p>
                <p>Landed Cost gesamt: <strong><?php echo esc_html(number_format_i18n($landed_total,2)); ?> €</strong></p>
                <p>Stückpreis: <strong><?php echo esc_html(number_format_i18n($landed_per_unit,2)); ?> €</strong></p>
                <p>Wechselkurs: <strong><?php echo esc_html($exchange_rate); ?></strong></p>
            </div>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr;margin-top:20px;">
            <div class="card">
                <h2>Lieferscheine</h2>
                <?php if ($lieferscheine): ?>
                    <table>
                        <thead><tr><th>Titel</th><th>Datum</th><th>Zollabwicklung</th><th>Aircargo</th></tr></thead>
                        <tbody>
                        <?php foreach ($lieferscheine as $ls):
                            $ls_id   = $ls->ID;
                            $ls_date = get_post_meta($ls_id,'belegdatum', true);
                            $lf_no   = get_post_meta($ls_id,'lfbelegnummer', true);
                            $air_v   = get_post_meta($ls_id,'air_cargo_kosten',true);
                            $air_v_usd = get_post_meta($ls_id,'air_cargo_kosten_usd',true);
                            if (!$air_v && $air_v_usd) {
                                $parent_id_l = wp_get_post_parent_id($ls_id);
                                $rate_l = $parent_id_l ? hoffmann_to_float(get_post_meta($parent_id_l,'wechselkurs',true)) : 1.0;
                                if (!$rate_l) { $rate_l = 1.0; }
                                $air_v = $air_v_usd / $rate_l;
                            }
                            $zoll_v  = get_post_meta($ls_id,'zoll_abwicklung_kosten',true);
                        ?>
                            <tr>
                                <td><?php echo esc_html(get_the_title($ls)); if($lf_no) echo '<br>'.esc_html($lf_no); ?></td>
                                <td><?php echo esc_html(date_i18n('Y-m-d', strtotime($ls_date))); ?></td>
                                <td><?php echo esc_html(hoffmann_format_currency($zoll_v)); ?> €</td>
                                <td><?php echo esc_html(number_format((float)$air_v, 2, ',', '.')); ?> €<br><span class="text-muted">$<?php echo esc_html(hoffmann_format_currency($air_v_usd)); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Keine Lieferscheine vorhanden.</p>
                <?php endif; ?>
            </div>
            <div class="card">
                <h2>Steuermarken</h2>
                <?php if ($stm_posts): ?>
                    <table>
                        <thead><tr><th>Titel</th><th>Datum</th><th>Wert</th><th>Stückzahl</th></tr></thead>
                        <tbody>
                        <?php foreach ($stm_posts as $stm):
                            $stm_title = get_the_title($stm);
                            $stm_date  = get_post_meta($stm->ID,'bestelldatum', true);
                            $stm_wert  = hoffmann_format_currency(get_post_meta($stm->ID,'wert', true));
                        ?>
                            <tr>
                                <td><?php echo esc_html($stm_title); ?></td>
                                <td><?php echo esc_html($stm_date); ?></td>
                                <td><?php echo esc_html($stm_wert); ?> €</td>
                                <td><?php echo esc_html(number_format_i18n(get_post_meta($stm->ID,'stueckzahl', true))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Keine Steuermarken vorhanden.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card" style="margin-top:20px;">
            <h2>Produkte der Bestellung</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>SKU</th>
                        <th>Bestellt</th>
                        <th>Geliefert</th>
                        <th>EK $/Stk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $art => $info):
                        $status = 'offen';
                        if ($info['delivered'] >= $info['ordered'] && $info['ordered'] > 0) { $status = 'voll'; }
                        elseif ($info['delivered'] > 0) { $status = 'teil'; }
                    ?>
                    <tr>
                        <td><?php echo esc_html($info['bezeichnung']); ?><br><span class="status <?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td>
                        <td><?php echo esc_html($art); ?></td>
                        <td><?php echo esc_html(number_format_i18n($info['ordered'])); ?></td>
                        <td><?php echo esc_html(number_format_i18n($info['delivered'])); ?></td>
                        <td>$<?php echo esc_html(hoffmann_format_currency($info['preis'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var ctx=document.getElementById('hoffmann-bestellung-pie').getContext('2d');
    new Chart(ctx,{type:'pie',data:{labels:['Geliefert','Offen'],datasets:[{data:[<?php echo (int)$total_delivered; ?>,<?php echo (int)max(0,$total_ordered-$total_delivered); ?>],backgroundColor:['#4caf50','#ddd']}]},options:{responsive:true}});
});
</script>
</body>
</html>

