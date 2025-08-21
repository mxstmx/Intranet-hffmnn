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
function hoffmann_render_textbestellung_form() {
    if (isset($_POST['hoffmann_textbestellung']) && isset($_POST['bestelltext'])) {
        hoffmann_process_textbestellung();
    }

    ob_start();
    ?>
    <form method="post">
        <?php wp_nonce_field('hoffmann_textbestellung_action', 'hoffmann_textbestellung_nonce'); ?>
        <textarea name="bestelltext" rows="15" cols="60"></textarea><br>
        <label><input type="checkbox" name="vorbestellung" value="1"> Vorbestellung</label><br>
        <input type="hidden" name="hoffmann_textbestellung" value="1">
        <button type="submit">CSV erstellen</button>
    </form>
    <?php
    return ob_get_clean();
}

// Verarbeitung des Formulars und Generierung der CSV-Datei
function hoffmann_process_textbestellung() {
    if (!isset($_POST['hoffmann_textbestellung_nonce']) || !wp_verify_nonce($_POST['hoffmann_textbestellung_nonce'], 'hoffmann_textbestellung_action')) {
        return;
    }

    $text = sanitize_textarea_field($_POST['bestelltext']);
    $vorbestellung = !empty($_POST['vorbestellung']);

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
    foreach ($product_posts as $p) {
        $product_titles[$p->ID] = strtolower($p->post_title);
    }

    $items = [];
    foreach ($lines as $line) {
        if (!preg_match('/(.+?)\s*(\d+)/', $line, $m)) {
            continue;
        }
        $name = strtolower(trim($m[1]));
        $qty  = (int) $m[2];

        $best_id = null;
        $best_score = 0;
        foreach ($product_titles as $id => $title) {
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
        $preis = get_post_meta($best_id, 'einzelpreis', true);
        if ($preis === '') {
            $preis = get_post_meta($best_id, 'preis', true);
        }
        $bestand = (int) get_post_meta($best_id, 'bestand', true);
        $reserviert = (int) get_post_meta($best_id, 'reserviert', true);
        $verfuegbar = max(0, $bestand - $reserviert);
        $menge = $vorbestellung ? $qty : min($qty, $verfuegbar);
        if ($menge <= 0) {
            continue;
        }

        $preis_formatiert = $preis !== '' ? str_replace('.', ',', $preis) : '0,00';
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
