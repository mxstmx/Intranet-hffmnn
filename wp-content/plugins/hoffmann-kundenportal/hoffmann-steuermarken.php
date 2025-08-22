<?php
/*
Plugin Name: Hoffmann Steuermarken
Description: Verwaltert Steuermarken und Zuordnung zu Bestellungen.
Version: main-v1.0.0
Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/lib/number-utils.php';

function hoffmann_register_steuermarken_post_type() {
    register_post_type('steuermarken', array(
        'labels' => array(
            'name' => __('Steuermarken'),
            'singular_name' => __('Steuermarke'),
        ),
        'public' => true,
        'supports' => array('title', 'custom-fields'),
    ));
}
add_action('init', 'hoffmann_register_steuermarken_post_type');

function hoffmann_steuermarken_add_meta_box() {
    add_meta_box(
        'hoffmann_steuermarken_metabox',
        __('Details'),
        'hoffmann_steuermarken_metabox_render',
        'steuermarken',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'hoffmann_steuermarken_add_meta_box');

function hoffmann_steuermarken_metabox_render($post) {
    wp_nonce_field('hoffmann_steuermarken_meta', 'hoffmann_steuermarken_meta_nonce');
    $kategorie   = hoffmann_to_float(get_post_meta($post->ID, 'kategorie', true));
    $stueckzahl  = hoffmann_to_int(get_post_meta($post->ID, 'stueckzahl', true));
    $bestelldatum= get_post_meta($post->ID, 'bestelldatum', true);
    $order_id    = get_post_meta($post->ID, 'bestellung_id', true);
    $orders      = get_posts(array(
        'post_type'  => 'bestellungen',
        'numberposts'=> -1,
        'orderby'    => 'title',
        'order'      => 'ASC',
        'tax_query'  => array(array(
            'taxonomy' => 'bestellart',
            'field'    => 'name',
            'terms'    => '2200',
        ))
    ));
    $categories = array(
        '0.52' => '0,52 €',
        '1.04' => '1,04 €',
        '2.60' => '2,60 €',
    );
    $stueckzahl_disp = $stueckzahl ? $stueckzahl : '';
    $wert_calc = 0;
    if ($kategorie !== 0 && $stueckzahl) {
        $wert_calc = $kategorie * $stueckzahl;
    }
    $wert_disp = number_format($wert_calc, 2, ',', '.');
    ?>
    <p><label>Kategorie<br><select name="kategorie">
        <?php foreach($categories as $val => $label){ echo '<option value="'.esc_attr($val).'" '.selected($kategorie,$val,false).'>'.esc_html($label).'</option>'; } ?>
    </select></label></p>
    <p><label>Steuermarken-Stückzahl<br><input type="text" name="stueckzahl" value="<?php echo esc_attr($stueckzahl_disp); ?>"></label></p>
    <p>Steuermarken-Warenwert: <strong><?php echo esc_html($wert_disp); ?> €</strong></p>
    <p><label>Bestelldatum<br><input type="date" name="bestelldatum" value="<?php echo esc_attr($bestelldatum); ?>"></label></p>
    <p><label>Bestellung<br><select name="bestellung_id"><option value="">-</option>
    <?php foreach($orders as $o){ echo '<option value="'.esc_attr($o->ID).'" '.selected($order_id, $o->ID, false).'>'.esc_html($o->post_title).'</option>'; } ?>
    </select></label></p>
    <?php
}

function hoffmann_steuermarken_save_meta($post_id) {
    if (!isset($_POST['hoffmann_steuermarken_meta_nonce']) || !wp_verify_nonce($_POST['hoffmann_steuermarken_meta_nonce'], 'hoffmann_steuermarken_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $kategorie  = hoffmann_to_float($_POST['kategorie'] ?? '');
    $st_raw     = sanitize_text_field($_POST['stueckzahl'] ?? '0');
    $stueckzahl = hoffmann_to_int($st_raw);
    $wert       = ($kategorie !== '') ? $kategorie * $stueckzahl : 0;
    update_post_meta($post_id, 'kategorie', $kategorie);
    update_post_meta($post_id, 'stueckzahl', $stueckzahl);
    update_post_meta($post_id, 'wert', $wert);
    update_post_meta($post_id, 'bestelldatum', sanitize_text_field($_POST['bestelldatum'] ?? ''));
    $bestellung = intval($_POST['bestellung_id'] ?? 0);
    if ($bestellung && has_term('2200','bestellart',$bestellung)) {
        update_post_meta($post_id, 'bestellung_id', $bestellung);
    } else {
        update_post_meta($post_id, 'bestellung_id', 0);
    }
}
add_action('save_post_steuermarken', 'hoffmann_steuermarken_save_meta');

function hoffmann_steuermarken_title_placeholder($title, $post){
    if('steuermarken' === $post->post_type){
        $title = __('Steuermarken-Belegnummer');
    }
    return $title;
}
add_filter('enter_title_here','hoffmann_steuermarken_title_placeholder',10,2);

function hoffmann_steuermarken_columns($columns){
    if(isset($columns['title'])){
        $columns['title'] = __('Steuermarken-Belegnummer');
    }
    return $columns;
}
add_filter('manage_steuermarken_posts_columns','hoffmann_steuermarken_columns');

function hoffmann_steuermarken_shortcode() {
    if (!current_user_can('read')) {
        return '';
    }

    $posts = get_posts(array(
        'post_type'      => 'steuermarken',
        'numberposts'    => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    $rows = array();
    foreach ($posts as $p) {
        $order_id = get_post_meta($p->ID, 'bestellung_id', true);
        $rows[] = array(
            'id'           => $p->ID,
            'title'        => get_the_title($p),
            'orderNo'      => $order_id ? get_the_title($order_id) : '',
            'orderUrl'     => $order_id ? get_permalink($order_id) : '',
            'orderedAt'    => get_post_meta($p->ID, 'bestelldatum', true),
            'qty'          => intval(get_post_meta($p->ID, 'stueckzahl', true)),
            'unitValueEUR' => floatval(get_post_meta($p->ID, 'kategorie', true)),
            'editUrl'      => get_edit_post_link($p->ID, 'raw'),
        );
    }

    ob_start();
    ?>
    <div class="hoffmann-steuermarken">
      <header>
        <h1>Steuermarken – Übersicht</h1>
        <div class="sub">Bestellungen, Werte und Stückzahlen – mit Suche & Datumsfilter</div>
      </header>
      <section class="toolbar">
        <input id="q" class="input" placeholder="Suchen: Titel oder Bestellnr…" />
        <label class="muted" style="display:flex;align-items:center;gap:8px">Von <input id="from" type="date" class="input" /></label>
        <label class="muted" style="display:flex;align-items:center;gap:8px">Bis <input id="to" type="date" class="input" /></label>
        <select id="sort" title="Sortierung">
          <option value="date_desc">Neueste zuerst</option>
          <option value="date_asc">Älteste zuerst</option>
          <option value="qty_desc">Stückzahl ↓</option>
          <option value="qty_asc">Stückzahl ↑</option>
          <option value="value_desc">Gesamtwert ↓</option>
          <option value="value_asc">Gesamtwert ↑</option>
        </select>
        <button id="reset" class="btn">Zurücksetzen</button>
        <button id="export-dsv" class="btn primary">DSV Export</button>
        <button id="stm-add" class="btn">Steuermarke hinzufügen</button>
      </section>

      <section class="grid grid-4" id="kpis">
        <div class="kpi"><div class="label">Summe Stückzahl (alle Filter)</div><div class="val" id="kpi-qty">0</div></div>
        <div class="kpi"><div class="label">Gesamtwert</div><div class="val" id="kpi-total">€ 0,00</div></div>
        <div class="kpi"><div class="label">Ø Wert pro Marke</div><div class="val" id="kpi-avg">€ 0,00</div></div>
        <div class="kpi"><div class="label">Anzahl Bestellungen</div><div class="val" id="kpi-count">0</div></div>
      </section>

      <section class="card" style="margin-top:16px">
        <h2>Bestellliste</h2>
        <div class="body table">
          <table id="tbl">
            <thead>
              <tr>
                <th>Titel</th>
                <th>Bestellnr</th>
                <th>Bestelldatum</th>
                <th class="right">Stückzahl</th>
                <th class="right">Wert je Marke</th>
                <th class="right">Gesamtwert</th>
                <th>Aktionen</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="body muted" id="rowsum"></div>
      </section>

      <section class="footer">
        <div class="card"><h2>Hinweis</h2><div class="body muted">Wert je Marke kann sich je Produktart unterscheiden (z. B. Pods 1,04 €, EB600/EB800 0,52 €, ELFLIQ 2,60 €). Diese Übersicht rechnet dynamisch <em>Gesamtwert = Stückzahl × Wert je Marke</em>.</div></div>
        <div class="card"><h2>Datenquellen</h2><div class="body muted">Steuerportal / ERP – Export als CSV/JSON einspielen und mappen auf: <code>{ title, orderNo, orderedAt, qty, unitValueEUR }</code>.</div></div>
        <div class="card"><h2>Quick-Stats</h2><div class="body"><span id="qsTop"></span> <span id="qsAvg"></span></div></div>
      </section>
    </div>
    <div id="stm-overlay" class="hoffmann-overlay"></div>
    <div id="stm-popup" class="hoffmann-popup">
      <button class="popup-close">&times;</button>
      <iframe id="stm-frame" style="width:100%;height:80vh;border:0;"></iframe>
    </div>

    <style>
    :root{
      --bg:#f7fafc;--fg:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#ffffff;--accent:#2563eb;--good:#16a34a;--radius:14px;--shadow:0 6px 24px rgba(2,6,23,.06);
      --order-bg:#f3f4f6; --order-fg:#111827;
    }
    *{box-sizing:border-box}
    .hoffmann-steuermarken{margin:36px auto;max-width:1200px;padding:0 20px;background:var(--bg);font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--fg)}
    .hoffmann-steuermarken h1{margin:0 0 6px;font-size:28px;font-weight:700}
    .hoffmann-steuermarken .sub{color:var(--muted);font-size:14px}
    .hoffmann-steuermarken .toolbar{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
    .hoffmann-steuermarken .input,.hoffmann-steuermarken select,.hoffmann-steuermarken .btn{border:1px solid var(--line);background:#fff;border-radius:10px;height:40px;padding:8px 12px;font-size:14px}
    .hoffmann-steuermarken .btn{cursor:pointer}
    .hoffmann-steuermarken .btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
    .hoffmann-steuermarken .grid{display:grid;gap:16px}
    .hoffmann-steuermarken .grid-4{grid-template-columns:repeat(4,1fr)}
    @media (max-width:990px){.hoffmann-steuermarken .grid-4{grid-template-columns:repeat(2,1fr)}}
    @media (max-width:640px){.hoffmann-steuermarken .grid-4{grid-template-columns:1fr}}
    .hoffmann-steuermarken .kpi{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
    .hoffmann-steuermarken .kpi .label{font-size:12px;color:var(--muted)}
    .hoffmann-steuermarken .kpi .val{font-size:22px;font-weight:700;margin-top:6px}
    .hoffmann-steuermarken .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}
    .hoffmann-steuermarken .card h2{margin:0;padding:14px 16px;border-bottom:1px solid var(--line);font-size:15px;color:#374151}
    .hoffmann-steuermarken .card .body{padding:16px}
    .hoffmann-steuermarken .table{overflow:auto;border-radius:var(--radius);border:1px solid var(--line);background:#fff}
    .hoffmann-steuermarken table{width:100%;border-collapse:collapse;font-size:14px}
    .hoffmann-steuermarken th,.hoffmann-steuermarken td{padding:12px 14px;border-top:1px solid var(--line);text-align:left}
    .hoffmann-steuermarken thead th{background:#f3f4f6;text-transform:uppercase;font-size:11px;letter-spacing:.06em;color:#6b7280}
    .hoffmann-steuermarken tbody tr:nth-child(odd){background:#fbfdff}
    .hoffmann-steuermarken .right{text-align:right}
    .hoffmann-steuermarken .orderNo{font-weight:700;background:var(--order-bg);color:var(--order-fg);padding:4px 8px;border-radius:6px;display:inline-block;text-decoration:none}
    .hoffmann-steuermarken .orderNo:hover{background:var(--accent);color:#fff}
    .hoffmann-steuermarken .muted{color:var(--muted);font-size:13px}
    .hoffmann-steuermarken .footer{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:16px}
    @media (max-width:990px){.hoffmann-steuermarken .footer{grid-template-columns:1fr}}
    </style>

    <script>
    const EUR = new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
    const fmtDate = (d)=> new Date(d).toLocaleDateString('de-DE');
    const DATA = <?php echo wp_json_encode($rows); ?>;
    const state = { q:'', from:'', to:'', sort:'date_desc' };

    function money(n){ return EUR.format(n || 0); }
    function within(d, from, to){ const t = +new Date(d); return (!from || t >= +new Date(from)) && (!to || t <= +new Date(to)+86400000-1); }

    function getFiltered(){
      let rows = DATA.filter(r=>{
        const txt = (r.title+" "+r.orderNo).toLowerCase();
        const matches = !state.q || txt.includes(state.q.toLowerCase());
        const inRange = within(r.orderedAt, state.from, state.to);
        return matches && inRange;
      });
      rows.sort((a,b)=>{
        switch(state.sort){
          case 'date_asc': return +new Date(a.orderedAt) - +new Date(b.orderedAt);
          case 'qty_desc': return b.qty - a.qty;
          case 'qty_asc': return a.qty - b.qty;
          case 'value_desc': return (b.qty*b.unitValueEUR) - (a.qty*a.unitValueEUR);
          case 'value_asc': return (a.qty*a.unitValueEUR) - (b.qty*b.unitValueEUR);
          default: return +new Date(b.orderedAt) - +new Date(a.orderedAt);
        }
      });
      return rows;
    }

    function render(){
      const rows = getFiltered();
      const tbody = document.querySelector('#tbl tbody');
      tbody.innerHTML = '';
      let qtySum = 0, valueSum = 0;
      rows.forEach(r=>{
        const total = r.qty * r.unitValueEUR;
        qtySum += r.qty; valueSum += total;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${r.title}</strong></td>
          <td><a href="${r.orderUrl}" class="orderNo" target="_blank">${r.orderNo}</a></td>
          <td>${fmtDate(r.orderedAt)}</td>
          <td class="right">${r.qty.toLocaleString('de-DE')}</td>
          <td class="right">${money(r.unitValueEUR)}</td>
          <td class="right">${money(total)}</td>
          <td><button class="btn edit-stm" data-url="${r.editUrl}">Bearbeiten</button></td>`;
        tbody.appendChild(tr);
      });

      document.getElementById('rowsum').textContent = `${rows.length} Bestellungen angezeigt`;
      document.getElementById('kpi-qty').textContent = qtySum.toLocaleString('de-DE');
      document.getElementById('kpi-total').textContent = money(valueSum);
      document.getElementById('kpi-avg').textContent = money(qtySum ? valueSum/qtySum : 0);
      document.getElementById('kpi-count').textContent = rows.length.toString();

      const top = rows.reduce((m,r)=> r.qty>m.qty? r: m, rows[0] || {title:'—',qty:0});
      document.getElementById('qsTop').textContent = `Top-Order: ${top.title} (${top.qty.toLocaleString('de-DE')} Stk)`;
      document.getElementById('qsAvg').textContent = `Ø je Bestellung: ${rows.length?Math.round(qtySum/rows.length).toLocaleString('de-DE'):0} Stk`;
    }

    function exportDSV(){
      const rows = getFiltered();
      const header = ['Titel','Bestellnr','Bestelldatum','Stückzahl','Wert je Marke (EUR)','Gesamtwert (EUR)'];
      const out = [header.join(';')].concat(rows.map(r=>[
        r.title, r.orderNo, r.orderedAt, r.qty, r.unitValueEUR.toFixed(2).replace('.',','), (r.qty*r.unitValueEUR).toFixed(2).replace('.',',')
      ].join(';'))).join('\n');
      const blob = new Blob([out],{type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'steuermarken_export.csv'; a.click(); URL.revokeObjectURL(url);
    }

    document.getElementById('q').addEventListener('input', e=>{state.q=e.target.value; render();});
    document.getElementById('from').addEventListener('change', e=>{state.from=e.target.value; render();});
    document.getElementById('to').addEventListener('change', e=>{state.to=e.target.value; render();});
    document.getElementById('sort').addEventListener('change', e=>{state.sort=e.target.value; render();});
    document.getElementById('reset').addEventListener('click', ()=>{state.q='';state.from='';state.to='';state.sort='date_desc';document.getElementById('q').value='';document.getElementById('from').value='';document.getElementById('to').value='';document.getElementById('sort').value='date_desc';render();});
    document.getElementById('export-dsv').addEventListener('click', exportDSV);

    const OVERLAY=document.getElementById('stm-overlay');
    const POPUP=document.getElementById('stm-popup');
    const FRAME=document.getElementById('stm-frame');
    const ADD_URL='<?php echo esc_js(admin_url('post-new.php?post_type=steuermarken')); ?>';

    function openPopup(url){ FRAME.src=url; OVERLAY.style.display='block'; POPUP.style.display='block'; }
    function closePopup(){ FRAME.src=''; OVERLAY.style.display='none'; POPUP.style.display='none'; }

    document.addEventListener('click',e=>{
      if(e.target.matches('.edit-stm')){ e.preventDefault(); openPopup(e.target.getAttribute('data-url')); }
      else if(e.target.matches('#stm-add')){ e.preventDefault(); openPopup(ADD_URL); }
      else if(e.target.matches('.popup-close')||e.target.classList.contains('hoffmann-overlay')){ closePopup(); }
    });

    render();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('hoffmann_steuermarken', 'hoffmann_steuermarken_shortcode');

