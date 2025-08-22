<?php
/*
Plugin Name: Hoffmann Textbestellung
Description: Erstellt aus einem Text eine CSV-Bestellliste.
Version: main-v1.0.1
Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
*/

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode zum Anzeigen des Formulars
add_shortcode('hoffmann_textbestellung', 'hoffmann_render_textbestellung_form');
add_action('template_redirect', 'hoffmann_handle_textbestellung_submission');
function hoffmann_render_textbestellung_form() {
    ob_start();
    ?>
    <style>
        #hoffmann-textbestellung * { box-sizing: border-box; }
        #hoffmann-textbestellung .wrap { max-width: 700px; margin: 32px auto; padding: 0 20px; }
        #hoffmann-textbestellung h1 { margin: 0 0 6px; font-size: 26px; font-weight: 700; }
        #hoffmann-textbestellung .sub { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
        #hoffmann-textbestellung .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 6px 24px rgba(2,6,23,.06); }
        #hoffmann-textbestellung .card h2 { margin: 0; padding: 14px 16px; border-bottom: 1px solid #e5e7eb; font-size: 15px; color: #374151; }
        #hoffmann-textbestellung .card .body { padding: 16px; }
        #hoffmann-textbestellung label { font-size: 12px; color: #374151; margin-bottom: 6px; display: block; }
        #hoffmann-textbestellung input[type="text"],
        #hoffmann-textbestellung input[type="date"],
        #hoffmann-textbestellung input[type="time"],
        #hoffmann-textbestellung textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; font-size: 14px; background: #fff; }
        #hoffmann-textbestellung textarea { min-height: 120px; resize: vertical; }
        #hoffmann-textbestellung .switch { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
        #hoffmann-textbestellung .switch input { appearance: none; width: 46px; height: 26px; border-radius: 999px; background: #e5e7eb; position: relative; outline: none; cursor: pointer; transition: .2s; }
        #hoffmann-textbestellung .switch input:checked { background: #2563eb; }
        #hoffmann-textbestellung .switch input:before { content: ""; position: absolute; left: 2px; top: 2px; width: 22px; height: 22px; border-radius: 50%; background: #fff; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        #hoffmann-textbestellung .switch input:checked:before { left: 22px; }
        #hoffmann-textbestellung .btn { border: 1px solid #e5e7eb; background: #fff; border-radius: 10px; padding: 10px 14px; font-size: 14px; cursor: pointer; margin-top: 16px; }
        #hoffmann-textbestellung .btn.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
    <div id="hoffmann-textbestellung">
        <div class="wrap">
            <header>
                <h1>Text‑Besteller</h1>
                <div class="sub">Lege eine Textbestellung an – inkl. Option <strong>Vorbestellung</strong>.</div>
            </header>

            <form method="post">
                <?php wp_nonce_field('hoffmann_textbestellung_action', 'hoffmann_textbestellung_nonce'); ?>
                <input type="hidden" name="hoffmann_textbestellung" value="1">
                <section class="card">
                    <h2>Bestellung</h2>
                    <div class="body">
                        <div class="switch">
                            <input id="preorder" type="checkbox" name="vorbestellung" value="1">
                            <label for="preorder" style="margin:0;font-size:14px">Vorbestellung aktivieren</label>
                        </div>

                        <div id="preorderFields" style="display:none;margin-top:10px">
                            <div>
                                <label>Wunschtermin (Datum)</label>
                                <input id="preDate" type="date" name="pre_date" />
                            </div>
                            <div style="margin-top:10px">
                                <label>Wunschzeit</label>
                                <input id="preTime" type="time" name="pre_time" />
                            </div>
                        </div>

                        <div style="margin-top:10px">
                            <label>Nachrichtentext</label>
                            <textarea id="message" name="bestelltext" placeholder="Schreibe hier die Textbestellung…"></textarea>
                            <div class="sub" id="charCount">0 Zeichen</div>
                        </div>

                        <div style="margin-top:10px">
                            <label>Einzelpreis (optional)</label>
                            <input id="price" type="text" name="preis" placeholder="z.B. 1,99" />
                        </div>

                        <button class="btn primary" type="submit">CSV erstellen</button>
                    </div>
                </section>
            </form>
        </div>
    </div>
    <script>
        const $$ = (s) => document.querySelector('#hoffmann-textbestellung ' + s);
        function updateCharCount(){
            const len = $$('#message').value.length; $$('#charCount').textContent = len + ' Zeichen';
        }
        $$('#preorder').addEventListener('change', e=>{
            $$('#preorderFields').style.display = e.target.checked ? 'block' : 'none';
        });
        $$('#message').addEventListener('input', updateCharCount);
        updateCharCount();
    </script>
    <?php
    return ob_get_clean();
}

// Verarbeitung des Formulars und Generierung der CSV-Datei vor dem Senden von HTML
function hoffmann_handle_textbestellung_submission() {
    if (!isset($_POST['hoffmann_textbestellung']) || !isset($_POST['bestelltext'])) {
        return;
    }
    if (!isset($_POST['hoffmann_textbestellung_nonce']) || !wp_verify_nonce($_POST['hoffmann_textbestellung_nonce'], 'hoffmann_textbestellung_action')) {
        return;
    }

    $text = sanitize_textarea_field($_POST['bestelltext']);
    $vorbestellung = !empty($_POST['vorbestellung']);
    $preorder_date = isset($_POST['pre_date']) ? sanitize_text_field($_POST['pre_date']) : '';
    $preorder_time = isset($_POST['pre_time']) ? sanitize_text_field($_POST['pre_time']) : '';
    $global_price  = isset($_POST['preis']) ? sanitize_text_field($_POST['preis']) : '';

    if ($global_price !== '') {
        $global_price = str_replace(',', '.', $global_price);
    }

    $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $text)));
    if (empty($lines)) {
        return;
    }

    // Alle Produkte einmal laden
    $product_posts = get_posts([
        'post_type'      => 'produkte',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (empty($product_posts)) {
        return;
    }

    $product_titles = [];
    $product_warengruppen = [];
    foreach ($product_posts as $p) {
        $product_titles[$p->ID] = strtolower($p->post_title);
        // Zugeordnete Warengruppen der Produkte für spätere Filterung erfassen
        $terms = wp_get_post_terms($p->ID, 'warengruppe', ['fields' => 'ids']);
        $product_warengruppen[$p->ID] = is_wp_error($terms) ? [] : $terms;
    }

    // Warengruppen sammeln, um Präfixe aus dem Namen entfernen zu können
    $warengruppen = get_terms([
        'taxonomy'   => 'warengruppe',
        'hide_empty' => false,
    ]);
    $warengruppe_info = [];
    if (!is_wp_error($warengruppen)) {
        foreach ($warengruppen as $wg) {
            $warengruppe_info[] = [
                'name'    => strtolower($wg->name),
                'term_id' => $wg->term_id,
                'slug'    => $wg->slug,
            ];
        }
        // Längere Namen zuerst prüfen
        usort($warengruppe_info, function ($a, $b) {
            return strlen($b['name']) - strlen($a['name']);
        });
    }

    $items = [];
    $current_warengruppe_id = null; // Merkt die zuletzt erkannte Warengruppe
    foreach ($lines as $line) {
        $line_trimmed = trim($line);
        $line_lower   = strtolower($line_trimmed);

        // Zeile kann ausschließlich eine Warengruppe enthalten
        foreach ($warengruppe_info as $wg) {
            if ($line_lower === $wg['name']) {
                $current_warengruppe_id = $wg['term_id'];
                continue 2; // nächste Zeile verarbeiten
            }
        }

        if (!preg_match('/(.+?)\s*(\d+)/', $line_trimmed, $m)) {
            continue;
        }
        $name = strtolower(trim($m[1]));
        $qty  = (int) $m[2];

        // Warengruppenpräfix entfernen, falls vorhanden
        $warengruppe_id = null;
        foreach ($warengruppe_info as $wg) {
            if (stripos($name, $wg['name'] . ' ') === 0) {
                $name = trim(substr($name, strlen($wg['name'])));
                $warengruppe_id = $wg['term_id'];
                break;
            }
        }

        // Wenn kein Präfix vorhanden ist, aktuelle Warengruppe verwenden
        if ($warengruppe_id === null && $current_warengruppe_id !== null) {
            $warengruppe_id = $current_warengruppe_id;
        }
        if ($name === '') {
            continue;
        }

        $best_id = null;
        $best_score = 0;
        foreach ($product_titles as $id => $title) {
            if ($warengruppe_id !== null && !in_array($warengruppe_id, $product_warengruppen[$id], true)) {
                continue; // Nur Produkte der angegebenen Warengruppe berücksichtigen
            }
            similar_text($name, $title, $percent);
            if ($percent > $best_score) {
                $best_score = $percent;
                $best_id = $id;
            }
        }
        if (!$best_id || $best_score < 40) {
            continue;
        }

        $artikelnummer = get_post_meta($best_id, 'artikelnummer', true);

        if ($global_price !== '') {
            $preis_formatiert = number_format((float) $global_price, 2, ',', '');
        } else {
            $preis = get_post_meta($best_id, 'einzelpreis', true);
            if ($preis === '') {
                $preis = get_post_meta($best_id, 'preis', true);
            }
            $preis_formatiert = $preis !== '' ? str_replace('.', ',', $preis) : '0,00';
        }
        $bestand = (int) get_post_meta($best_id, 'bestand', true);
        $reserviert = (int) get_post_meta($best_id, 'reserviert', true);
        $verfuegbar = max(0, $bestand - $reserviert);
        $menge = $vorbestellung ? $qty : min($qty, $verfuegbar);
        if ($menge <= 0) {
            continue;
        }

        $items[] = [
            'menge' => $menge,
            'artnr' => $artikelnummer,
            'preis' => $preis_formatiert,
        ];
    }

    if (empty($items)) {
        return;
    }

    $content = "\xEF\xBB\xBF"; // UTF-8 BOM
    $content .= "Menge;Artikelnr;Einzelpreis\n";
    foreach ($items as $item) {
        $content .= $item['menge'] . ';' . $item['artnr'] . ';' . $item['preis'] . "\n";
    }

    $filename = 'bestellung_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}
?>
