<?php
/**
 * Plugin Name: Hoffmann OPV Anbindung
 * Description: Importiert offene Posten aus einer JSON-Datei alle 5 Minuten und zeigt sie per Shortcode im Frontend mit Filterung, Sortierung und Summenbildung an. Die Daten werden auch im CPT 'belege' synchronisiert.
 * Version: main-v1.0.1
 * Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
 */

if (!defined('ABSPATH')) {
    exit;
}


// Debug Funktion
function hoffmann_opv_log($msg) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Hoffmann OPV] ' . $msg);
    }
}

// OPV JSON importieren und in CPT "belege" aktualisieren
function hoffmann_opv_import_json() {
    $url = 'https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json';
    $json = wp_remote_get($url);
    if (is_wp_error($json)) return;

    $data = json_decode(wp_remote_retrieve_body($json), true);
    if (!is_array($data)) return;

    foreach ($data as $kunde => $rechnungen) {
        foreach ($rechnungen as $eintrag) {
            $rechnungsnr = sanitize_text_field($eintrag['Rechnungsnr']);
            $post = get_posts([
                'post_type' => 'belege',
                'title' => $rechnungsnr,
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);

            if (!empty($post)) {
                $post_id = $post[0];
                update_post_meta($post_id, 'produkttyp', sanitize_text_field($eintrag['Produkttyp']));
                update_post_meta($post_id, 'betrag_gesamt', number_format((float)$eintrag['Betrag Gesamt'], 0, ',', '.'));
                update_post_meta($post_id, 'bisher_gezahlt', number_format((float)$eintrag['Bisher gezahlt'], 0, ',', '.'));
                update_post_meta($post_id, 'noch_zu_zahlen', number_format((float)$eintrag['Noch zu zahlen'], 0, ',', '.'));
            }
        }
    }
}
add_action('hoffmann_opv_sync', 'hoffmann_opv_import_json');

// Cron-Intervall definieren (5 Minuten)
add_filter('cron_schedules', function($schedules) {
    $schedules['every_five_minutes'] = [
        'interval' => 300,
        'display'  => __('Alle 5 Minuten')
    ];
    return $schedules;
});

// Cron aktivieren
add_action('wp', function() {
    if (!wp_next_scheduled('hoffmann_opv_sync')) {
        wp_schedule_event(time(), 'every_five_minutes', 'hoffmann_opv_sync');
    }
});

// Cron deaktivieren bei Plugin-Deaktivierung
register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('hoffmann_opv_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'hoffmann_opv_sync');
    }
});

// Shortcode [offene_posten_tabelle]
add_shortcode('offene_posten_tabelle', function() {
    ob_start();
    ?>
    <style>
        .opv-table-wrapper { overflow-x: auto; }
        @media (max-width: 600px) {
            .opv-table-wrapper th, .opv-table-wrapper td { white-space: nowrap; }
        }
    </style>
    <div id="offene-posten-filter">
        <label for="op-kunde-select">Kunde:</label>
        <select id="op-kunde-select">
            <option value="">Alle Kunden</option>
        </select>
        <button id="op-download-btn" style="margin-left: 20px;">PDF herunterladen</button>
    </div>

    <div class="opv-table-wrapper">
    <table id="offene-posten-tabelle" class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th>Kunde</th>
                <th>Rechnungsnr</th>
                <th>Datum</th>
                <th>Produkttyp</th>
                <th style="text-align:right;">Betrag Gesamt</th>
                <th style="text-align:right;">Bisher gezahlt</th>
                <th style="text-align:right;">Noch zu zahlen</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>

    <div id="op-summe">
        <strong>Gesamt offener Betrag:</strong> <span id="op-summe-wert">0,00 €</span>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const url = "https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json";
        let alleDaten = {};
        const kundeSelect = document.getElementById('op-kunde-select');
        const tbody = document.querySelector('#offene-posten-tabelle tbody');
        const sumField = document.getElementById('op-summe-wert');
        const downloadBtn = document.getElementById('op-download-btn');

        function renderKundenDropdown() {
            const kunden = Object.keys(alleDaten).sort();
            kunden.forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                kundeSelect.appendChild(opt);
            });
        }

        function renderTabelle(filterName = '') {
            tbody.innerHTML = '';
            let summe = 0;
            const kunden = filterName ? [filterName] : Object.keys(alleDaten);

            kunden.forEach(name => {
                alleDaten[name].forEach(eintrag => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${name}</td>
                        <td>${eintrag.Rechnungsnr}</td>
                        <td>${eintrag.Datum}</td>
                        <td>${eintrag.Produkttyp}</td>
                        <td style="text-align:right;">${parseFloat(eintrag["Betrag Gesamt"]).toLocaleString('de-DE')} €</td>
                        <td style="text-align:right;">${parseFloat(eintrag["Bisher gezahlt"]).toLocaleString('de-DE')} €</td>
                        <td style="text-align:right;">${parseFloat(eintrag["Noch zu zahlen"]).toLocaleString('de-DE')} €</td>
                    `;
                    tbody.appendChild(tr);
                    summe += parseFloat(eintrag["Noch zu zahlen"]);
                });
            });

            sumField.textContent = summe.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        }

        kundeSelect.addEventListener('change', () => {
            renderTabelle(kundeSelect.value);
        });

        fetch(url)
            .then(res => res.json())
            .then(data => {
                alleDaten = data;
                renderKundenDropdown();
                renderTabelle();
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="7">Fehler beim Laden der Daten.</td></tr>`;
                console.error(err);
            });

        downloadBtn.addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape' });
            const head = [["Kunde", "Rechnungsnr", "Datum", "Produkttyp", "Betrag Gesamt", "Bisher gezahlt", "Noch zu zahlen"]];
            const body = [];

            document.querySelectorAll('#offene-posten-tabelle tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = [];
                cells.forEach((cell, index) => {
                    let text = cell.textContent;
                    if (index >= 4) {
                        text = { content: text, styles: { halign: 'right' } };
                    }
                    rowData.push(text);
                });
                body.push(rowData);
            });

            doc.autoTable({ head, body });
            const date = new Date().toISOString().split('T')[0];
            doc.save(`OPV-Kunden-${date}.pdf`);
        });
    });
    </script>
    <?php
    return ob_get_clean();
});

// Shortcode [offene_posten_total]
add_shortcode('offene_posten_total', function() {
    ob_start();
    ?>
    <style>
        .opv-table-wrapper { overflow-x: auto; }
        @media (max-width: 600px) {
            .opv-table-wrapper th, .opv-table-wrapper td { white-space: nowrap; }
        }
    </style>
    <div class="opv-table-wrapper">
    <table id="offene-posten-total" class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th>Kunde</th>
                <th style="text-align:right;">Betrag Gesamt</th>
                <th style="text-align:right;">Bisher gezahlt</th>
                <th style="text-align:right;">Noch zu zahlen</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>
    <div id="op-total-summe">
        <strong>Gesamt offener Betrag:</strong> <span id="op-total-summe-wert">0,00 €</span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const url = "https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json";
        const tbody = document.querySelector('#offene-posten-total tbody');
        const sumField = document.getElementById('op-total-summe-wert');
        fetch(url)
            .then(res => res.json())
            .then(data => {
                let gesamtOffen = 0;
                Object.keys(data).sort().forEach(name => {
                    let sumGesamt = 0, sumGezahlt = 0, sumOffen = 0;
                    data[name].forEach(e => {
                        sumGesamt += parseFloat(e["Betrag Gesamt"]);
                        sumGezahlt += parseFloat(e["Bisher gezahlt"]);
                        sumOffen  += parseFloat(e["Noch zu zahlen"]);
                    });
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${name}</td>
                        <td style="text-align:right;">${sumGesamt.toLocaleString('de-DE')} €</td>
                        <td style="text-align:right;">${sumGezahlt.toLocaleString('de-DE')} €</td>
                        <td style="text-align:right;">${sumOffen.toLocaleString('de-DE')} €</td>
                    `;
                    tbody.appendChild(tr);
                    gesamtOffen += sumOffen;
                });
                sumField.textContent = gesamtOffen.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="4">Fehler beim Laden der Daten.</td></tr>`;
            });
    });
    </script>
    <?php
    return ob_get_clean();
});

// Shortcode [opv] - interaktive Offene-Posten-Übersicht
add_shortcode('opv', function() {
    wp_enqueue_style('hoffmann-opv', plugins_url('css/opv.css', __FILE__));
    wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', [], null, true);
    wp_enqueue_script('hoffmann-opv', plugins_url('js/opv.js', __FILE__), ['jspdf'], null, true);
    ob_start();
    ?>
    <div id="opv-root">
      <header>
        <h1>Offene Posten</h1>
        <div class="sub">Kunde · Rechnungsnr · Datum · Produkttyp · Betrag · Bisher gezahlt · Noch zu zahlen</div>
      </header>
      <section class="toolbar">
        <input id="opv-q" class="input" placeholder="Suche: Kunde, Rechnungsnr, Produkttyp…" />
        <label class="muted" style="display:flex;align-items:center;gap:8px">Von <input id="opv-from" type="date" class="input" /></label>
        <label class="muted" style="display:flex;align-items:center;gap:8px">Bis <input id="opv-to" type="date" class="input" /></label>
        <select id="opv-customer" title="Kunde"><option value="">Alle Kunden</option></select>
        <select id="opv-ptype" title="Produkttyp"><option value="">Alle Produkttypen</option></select>
        <select id="opv-status" title="Status"><option value="">Alle</option><option value="overdue">Überfällig</option><option value="due7">Fällig ≤ 7 Tage</option></select>
        <button id="opv-reset" class="btn">Zurücksetzen</button>
        <button id="opv-exportCsv" class="btn">CSV Export</button>
        <button id="opv-exportPdf" class="btn primary">PDF Export</button>
      </section>
      <section class="grid grid-4" id="opv-kpis">
        <div class="kpi"><div class="label">Gesamtbetrag</div><div class="val" id="opv-kpi-gross">€ 0,00</div></div>
        <div class="kpi"><div class="label">Bisher gezahlt</div><div class="val" id="opv-kpi-paid">€ 0,00</div></div>
        <div class="kpi"><div class="label">Noch zu zahlen</div><div class="val" id="opv-kpi-open">€ 0,00</div></div>
        <div class="kpi"><div class="label">Überfällig</div><div class="val" id="opv-kpi-overdue">€ 0,00</div></div>
      </section>
      <section class="layout" style="margin-top:16px">
 
        <div class="card">
          <h2>Totals je Kunde</h2>
          <div class="body">
            <div class="totals" id="opv-totalsByCustomer"></div>
            <div class="footer-actions">
              <span class="pill warn">Überfällig</span>
              <span class="pill">Fällig ≤ 7 Tage</span>
            </div>
          </div>
        </div>
		         <div class="card">
          <h2>Rechnungen</h2>
          <div class="body table">
            <table id="opv-tbl">
              <thead>
                <tr>
                  <th>Kunde</th>
                  <th>Rechnungsnr</th>
                  <th>Datum</th>
                  <th>Produkttyp</th>
                  <th class="right">Betrag Gesamt</th>
                  <th class="right">Bisher gezahlt</th>
                  <th class="right">Noch zu zahlen</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="body muted" id="opv-rowsum"></div>
        </div>
      </section>
    </div>
    <?php
    return ob_get_clean();
});
