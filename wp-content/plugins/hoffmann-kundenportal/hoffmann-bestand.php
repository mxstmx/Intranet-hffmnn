<?php
/**
 * Plugin Name: Hoffmann Bestand
 * Description: Zeigt Produkte mit Verfügbarkeit filterbar nach Warengruppen ohne Bestellfunktion.
 * Version: main-v1.0.1
 * Author: Max Florian Krauss
 */

if (!defined('ABSPATH')) {
    exit;
}


// Shortcode für Bestandsanzeige
add_shortcode('hoffmann_bestand', 'hoffmann_render_bestand');
function hoffmann_render_bestand() {
    // Produkte auslesen und Datenstruktur für das Frontend vorbereiten
    $products = get_posts([
        'post_type'      => 'produkte',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $rows = [];
    foreach ($products as $p) {
        $stock     = (int) get_post_meta($p->ID, 'bestand', true);
        $reserved  = (int) get_post_meta($p->ID, 'reserviert', true);
        $available = max(0, $stock - $reserved);
        if ($available <= 0) {
            continue;
        }

        $grs   = wp_get_post_terms($p->ID, 'warengruppe');
        $group = (!is_wp_error($grs) && !empty($grs)) ? $grs[0]->name : 'Unbekannt';

        $rows[] = [
            'name'  => $p->post_title,
            'group' => $group,
            'qty'   => $available,
        ];
    }

    $data_json = wp_json_encode($rows);

    ob_start();
    ?>
    <div class="hoffmann-bestand">
      <style>
        .hoffmann-bestand{--bg:#f7fafc;--fg:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#ffffff;--accent:#2563eb;--radius:14px;--shadow:0 6px 24px rgba(2,6,23,.06);--order-bg:#f3f4f6;--order-fg:#111827;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--fg)}
        .hoffmann-bestand *{box-sizing:border-box}
        .hoffmann-bestand .wrap{max-width:1200px;margin:36px auto;padding:0 20px;background:var(--bg)}
        .hoffmann-bestand h1{margin:0 0 6px;font-size:28px;font-weight:700}
        .hoffmann-bestand .sub{color:var(--muted);font-size:14px}
        .hoffmann-bestand .toolbar{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
        .hoffmann-bestand .input,.hoffmann-bestand select,.hoffmann-bestand .btn{border:1px solid var(--line);background:#fff;border-radius:10px;height:40px;padding:8px 12px;font-size:14px}
        .hoffmann-bestand .btn{cursor:pointer}
        .hoffmann-bestand .btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
        .hoffmann-bestand .grid{display:grid;gap:16px}
        .hoffmann-bestand .grid-3{grid-template-columns:repeat(3,1fr)}
        @media (max-width:990px){.hoffmann-bestand .grid-3{grid-template-columns:repeat(2,1fr)}}
        @media (max-width:640px){.hoffmann-bestand .grid-3{grid-template-columns:1fr}}
        .hoffmann-bestand .kpi{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px}
        .hoffmann-bestand .kpi .label{font-size:12px;color:var(--muted)}
        .hoffmann-bestand .kpi .val{font-size:22px;font-weight:700;margin-top:6px}
        .hoffmann-bestand .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}
        .hoffmann-bestand .card h2{margin:0;padding:14px 16px;border-bottom:1px solid var(--line);font-size:15px;color:#374151}
        .hoffmann-bestand .card .body{padding:16px}
        .hoffmann-bestand .table{overflow:auto;border-radius:var(--radius);border:1px solid var(--line);background:#fff}
        .hoffmann-bestand table{width:100%;border-collapse:collapse;font-size:14px}
        .hoffmann-bestand th,.hoffmann-bestand td{padding:12px 14px;border-top:1px solid var(--line);text-align:left}
        .hoffmann-bestand thead th{background:#f3f4f6;text-transform:uppercase;font-size:11px;letter-spacing:.06em;color:#6b7280}
        .hoffmann-bestand tbody tr:nth-child(odd){background:#fbfdff}
        .hoffmann-bestand .right{text-align:right}
        .hoffmann-bestand .muted{color:var(--muted);font-size:13px}
        .hoffmann-bestand .footer{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:16px}
        @media (max-width:990px){.hoffmann-bestand .footer{grid-template-columns:1fr}}
      </style>
      <div class="wrap">
        <header>
          <h1>Bestand – Übersicht</h1>
          <div class="sub">Verfügbarkeit von Produkten, Warengruppen & Gesamtbestände – mit Suche & Filter</div>
        </header>

        <section class="toolbar">
          <input id="q" class="input" placeholder="Suchen: Produktname oder Warengruppe…" />
          <select id="group" title="Warengruppe">
            <option value="">Alle Warengruppen</option>
          </select>
          <select id="sort" title="Sortierung">
            <option value="qty_desc">Verfügbarkeit ↓</option>
            <option value="qty_asc">Verfügbarkeit ↑</option>
            <option value="name_asc">Name A-Z</option>
            <option value="name_desc">Name Z-A</option>
          </select>
          <button id="reset" class="btn">Zurücksetzen</button>
          <button id="export" class="btn primary">PDF Export</button>
        </section>

        <section class="grid grid-3" id="kpis">
          <div class="kpi"><div class="label">Gesamt Produkte</div><div class="val" id="kpi-count">0</div></div>
          <div class="kpi"><div class="label">Gesamt Verfügbarkeit</div><div class="val" id="kpi-qty">0</div></div>
          <div class="kpi"><div class="label">Warengruppen</div><div class="val" id="kpi-groups">0</div></div>
        </section>

        <section class="card" style="margin-top:16px">
          <h2>Bestandstabelle</h2>
          <div class="body table">
            <table id="tbl">
              <thead>
                <tr>
                  <th>Produktname</th>
                  <th>Warengruppe</th>
                  <th class="right">Verfügbarkeit</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="body muted" id="rowsum"></div>
        </section>

        <section class="footer">
          <div class="card"><h2>Hinweis</h2><div class="body muted">Die Verfügbarkeiten stammen aus dem ERP und werden regelmäßig aktualisiert.</div></div>
          <div class="card"><h2>Datenquellen</h2><div class="body muted">ERP / CSV / JSON – mappen auf: <code>{ name, group, qty }</code>.</div></div>
        </section>
      </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    const DATA = <?php echo $data_json; ?>;
    const state = { q:'', group:'', sort:'qty_desc' };

    function buildGroupOptions(){
      const sel = document.getElementById('group');
      const groups = Array.from(new Set(DATA.map(r=>r.group))).sort();
      groups.forEach(g=>{
        const opt = document.createElement('option');
        opt.value = g; opt.textContent = g; sel.appendChild(opt);
      });
      document.getElementById('kpi-groups').textContent = groups.length.toString();
    }

    function getFiltered(){
      let rows = DATA.filter(r=>{
        const txt = (r.name+" "+r.group).toLowerCase();
        const matches = !state.q || txt.includes(state.q.toLowerCase());
        const gmatch = !state.group || r.group === state.group;
        return matches && gmatch;
      });
      rows.sort((a,b)=>{
        switch(state.sort){
          case 'qty_asc': return a.qty - b.qty;
          case 'name_asc': return a.name.localeCompare(b.name);
          case 'name_desc': return b.name.localeCompare(a.name);
          default: return b.qty - a.qty;
        }
      });
      return rows;
    }

    function render(){
      const rows = getFiltered();
      const tbody = document.querySelector('#tbl tbody');
      tbody.innerHTML = '';
      let sumQty = 0; const groups = new Set();
      rows.forEach(r=>{
        sumQty += r.qty; groups.add(r.group);
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${r.name}</strong></td>
          <td>${r.group}</td>
          <td class="right">${r.qty}</td>`;
        tbody.appendChild(tr);
      });

      document.getElementById('rowsum').textContent = `${rows.length} Produkte angezeigt`;
      document.getElementById('kpi-count').textContent = rows.length.toString();
      document.getElementById('kpi-qty').textContent = sumQty;
    }

    function exportPDF(){
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.setFontSize(14);
      doc.text("Bestand – Übersicht", 14, 20);
      const rows = getFiltered();
      let y = 30;
      doc.setFontSize(10);
      doc.text("Produktname",14,y); doc.text("Warengruppe",80,y); doc.text("Verfügbarkeit",150,y);
      y+=6;
      rows.forEach(r=>{
        doc.text(r.name,14,y);
        doc.text(r.group,80,y);
        doc.text(r.qty.toString(),150,y,{align:'right'});
        y+=6;
      });
      doc.save("bestand.pdf");
    }

    document.getElementById('q').addEventListener('input', e=>{state.q=e.target.value; render();});
    document.getElementById('group').addEventListener('change', e=>{state.group=e.target.value; render();});
    document.getElementById('sort').addEventListener('change', e=>{state.sort=e.target.value; render();});
    document.getElementById('reset').addEventListener('click', ()=>{state.q='';state.group='';state.sort='qty_desc';document.getElementById('q').value='';document.getElementById('group').value='';document.getElementById('sort').value='qty_desc';render();});
    document.getElementById('export').addEventListener('click', exportPDF);

    buildGroupOptions();
    render();
    </script>
    <?php
    return ob_get_clean();
}
