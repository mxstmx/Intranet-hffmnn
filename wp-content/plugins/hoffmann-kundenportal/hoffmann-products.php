<?php
/*
Plugin Name: Hoffmann Produkte
Description: Erstellt einen benutzerdefinierten Post-Typ für Produkte mit Warengruppen und aktualisiert den Bestand alle 5 Minuten.
Version: main-v1.0.1
Author: Max Florian Krauss
*/

if (!defined('ABSPATH')) {
    exit;
}


define('BESTAND_JSON_URL', WP_CONTENT_DIR . '/uploads/json/bestand.json');

// 1. Registrierung des Post-Typs "Produkte" und der Taxonomie "Warengruppen"
function hoffmann_register_custom_post_type() {
    // Registrierung des Post-Typs "Produkte"
    register_post_type('produkte', [
        'labels' => [
            'name' => __('Produkte'),
            'singular_name' => __('Produkt')
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'custom-fields'],
        'menu_icon' => 'dashicons-cart'
    ]);

    // Registrierung der Taxonomie "Warengruppen" für die Produkte
    register_taxonomy('warengruppe', 'produkte', [
        'labels' => [
            'name' => __('Warengruppen'),
            'singular_name' => __('Warengruppe')
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true
    ]);
}
add_action('init', 'hoffmann_register_custom_post_type');

// 2. Bestand alle 5 Minuten aktualisieren
function hoffmann_add_inventory_cron_interval($schedules) {
    $schedules['every_five_minutes'] = [
        'interval' => 300,
        'display' => __('Alle 5 Minuten')
    ];
    return $schedules;
}
add_filter('cron_schedules', 'hoffmann_add_inventory_cron_interval');

// Cron-Job für die Bestandsaktualisierung planen
register_activation_hook(__FILE__, 'hoffmann_schedule_inventory_update');
function hoffmann_schedule_inventory_update() {
    if (!wp_next_scheduled('hoffmann_update_inventory_event')) {
        wp_schedule_event(time(), 'every_five_minutes', 'hoffmann_update_inventory_event');
    }
}

register_deactivation_hook(__FILE__, 'hoffmann_remove_inventory_update_schedule');
function hoffmann_remove_inventory_update_schedule() {
    $timestamp = wp_next_scheduled('hoffmann_update_inventory_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'hoffmann_update_inventory_event');
    }
}

// 3. Funktion zur Aktualisierung des Bestands
add_action('hoffmann_update_inventory_event', 'hoffmann_update_inventory');
function hoffmann_update_inventory() {
    // JSON-Datei einlesen
    if (!file_exists(BESTAND_JSON_URL)) {
        error_log('Bestand JSON-Datei nicht gefunden.');
        return;
    }
    $json_data = file_get_contents(BESTAND_JSON_URL);
    $bestandData = json_decode($json_data, true);

    if (!$bestandData) {
        error_log('Fehler beim Lesen der Bestand JSON-Daten.');
        return;
    }

    foreach ($bestandData as $warengruppe_id => $warengruppe) {
        $warengruppenbezeichnung = $warengruppe['Warengruppenbezeichnung'];

        // Warengruppe (Kategorie) erstellen oder finden
        $term = term_exists($warengruppenbezeichnung, 'warengruppe');
        if ($term === 0 || $term === null) {
            $term = wp_insert_term($warengruppenbezeichnung, 'warengruppe');
        }
        $term_id = is_array($term) ? $term['term_id'] : $term;

        foreach ($warengruppe['Artikel'] as $artikel) {
            $artikelnummer = $artikel['Artikelnummer'];
            $artikelbezeichnung = $artikel['Artikelbezeichnung'];
            $bestand = isset($artikel['Bestand']) ? intval($artikel['Bestand']) : 0;
            $reserviert = isset($artikel['Reserviert']) ? intval($artikel['Reserviert']) : 0;
            $bestellt = isset($artikel['Bestellt']) ? intval($artikel['Bestellt']) : 0;
            $information = isset($artikel['Information']) ? sanitize_text_field($artikel['Information']) : '';

            // Prüfen, ob das Produkt existiert (nach Artikelnummer als Primärschlüssel)
            $existing_product = get_posts([
                'post_type' => 'produkte',
                'meta_key' => 'artikelnummer',
                'meta_value' => $artikelnummer,
                'numberposts' => 1
            ]);

            if ($existing_product) {
                // Produkt aktualisieren
                $post_id = $existing_product[0]->ID;
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $artikelbezeichnung,
                ]);
            } else {
                // Neues Produkt erstellen
                $post_id = wp_insert_post([
                    'post_title' => $artikelbezeichnung,
                    'post_type' => 'produkte',
                    'post_status' => 'publish',
                ]);
            }

            // Produkt-Metadaten aktualisieren
            update_post_meta($post_id, 'artikelnummer', $artikelnummer);
            update_post_meta($post_id, 'bestand', $bestand);
            update_post_meta($post_id, 'reserviert', $reserviert);
            update_post_meta($post_id, 'bestellt', $bestellt);
            update_post_meta($post_id, 'information', $information);

            // Warengruppe (Kategorie) dem Produkt zuweisen
            wp_set_object_terms($post_id, intval($term_id), 'warengruppe');
        }
    }
}

// Admin-Spalten für den Post-Typ "Produkte" anpassen
add_filter('manage_produkte_posts_columns', 'hoffmann_customize_product_columns');
function hoffmann_customize_product_columns($columns) {
    unset($columns['date']); // Datumsspalte entfernen
    $columns['warengruppe'] = __('Warengruppe');
    $columns['bestand'] = __('Bestand');
    $columns['verfuegbarkeit'] = __('Verfügbarkeit');
    return $columns;
}

// Daten für die neuen Spalten hinzufügen
add_action('manage_produkte_posts_custom_column', 'hoffmann_custom_product_column_content', 10, 2);
function hoffmann_custom_product_column_content($column, $post_id) {
    if ($column === 'warengruppe') {
        // Zeigt die Namen der Warengruppe(n) an
        $terms = get_the_terms($post_id, 'warengruppe');
        if ($terms && !is_wp_error($terms)) {
            $term_names = array_map(function($term) { return $term->name; }, $terms);
            echo esc_html(implode(', ', $term_names));
        } else {
            echo __('Keine Warengruppe');
        }
    }

    if ($column === 'bestand') {
        // Zeigt den Bestand mit Tausender-Trennzeichen
        $bestand = get_post_meta($post_id, 'bestand', true);
        echo number_format_i18n($bestand);
    }

    if ($column === 'verfuegbarkeit') {
        // Zeigt die Verfügbarkeit (Bestand - Reserviert) mit Tausender-Trennzeichen
        $bestand = get_post_meta($post_id, 'bestand', true);
        $reserviert = get_post_meta($post_id, 'reserviert', true);
        $verfuegbarkeit = $bestand - $reserviert;
        echo number_format_i18n($verfuegbarkeit);
    }
}

// Sortierung im Admin nach Warengruppe und Name anpassen
add_filter('manage_edit-produkte_sortable_columns', 'hoffmann_sortable_product_columns');
function hoffmann_sortable_product_columns($columns) {
    $columns['warengruppe'] = 'warengruppe';
    $columns['title'] = 'title';
    return $columns;
}

// Sortierung nach Warengruppe und dann alphabetisch
add_action('pre_get_posts', 'hoffmann_sort_products_by_group_and_name');
function hoffmann_sort_products_by_group_and_name($query) {
    if (is_admin() && $query->get('post_type') === 'produkte') {
        $query->set('orderby', ['warengruppe' => 'ASC', 'title' => 'ASC']);
    }
}

// Filter für Warengruppen hinzufügen
add_action('restrict_manage_posts', 'hoffmann_filter_by_warengruppe');
function hoffmann_filter_by_warengruppe() {
    global $typenow;
    if ($typenow == 'produkte') {
        $taxonomy = 'warengruppe';
        $terms = get_terms($taxonomy);
        
        if ($terms) {
            echo '<select name="' . esc_attr($taxonomy) . '" class="postform">';
            echo '<option value="">' . __('Alle Warengruppen') . '</option>';
            foreach ($terms as $term) {
                printf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    esc_attr($term->slug),
                    (isset($_GET[$taxonomy]) && $_GET[$taxonomy] == $term->slug) ? ' selected="selected"' : '',
                    esc_html($term->name)
                );
            }
            echo '</select>';
        }
    }
}

// Anpassung der Query bei Auswahl einer Warengruppe im Filter
add_action('pre_get_posts', 'hoffmann_filter_products_by_warengruppe');
function hoffmann_filter_products_by_warengruppe($query) {
    global $pagenow;
    $post_type = 'produkte';
    $taxonomy = 'warengruppe';

    if ($pagenow == 'edit.php' && isset($_GET[$taxonomy]) && $_GET[$taxonomy] != '' && $query->is_main_query() && $query->get('post_type') == $post_type) {
        $query->set('tax_query', [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $_GET[$taxonomy]
            ]
        ]);
    }
}

// Shortcode für die Verfügbarkeit des aktuellen Produkts, mit Null für negative Werte
function hoffmann_current_post_verfuegbarkeit_shortcode() {
    if (get_post_type() !== 'produkte') {
        return 'Dieser Shortcode ist nur für den Produkttyp "Produkte" verfügbar';
    }

    $post_id = get_the_ID();
    $bestand = get_post_meta($post_id, 'bestand', true);
    $reserviert = get_post_meta($post_id, 'reserviert', true);
    $verfuegbarkeit = $bestand - $reserviert;

    // Setzt Verfügbarkeit auf 0, wenn sie negativ ist
    $verfuegbarkeit = max(0, $verfuegbarkeit);

    // Speichert die Verfügbarkeit in einer globalen Variable zur späteren Summierung
    global $hoffmann_gesamt_verfuegbarkeit;
    if ($verfuegbarkeit > 0) {
        $hoffmann_gesamt_verfuegbarkeit += $verfuegbarkeit;
    }

    return number_format_i18n($verfuegbarkeit);
}
add_shortcode('hoffmann_verfuegbarkeit', 'hoffmann_current_post_verfuegbarkeit_shortcode');



// Funktion zur Berechnung der Gesamtverfügbarkeit
function hoffmann_gesamt_verfuegbarkeit() {
    $warengruppe = isset($_GET['e-filter-47aa928-warengruppe']) ? sanitize_text_field($_GET['e-filter-47aa928-warengruppe']) : '';

    // Argumente für WP_Query
    $args = [
        'post_type' => 'produkte',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'bestand',
                'compare' => 'EXISTS',
            ],
        ],
    ];

    if (!empty($warengruppe)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'warengruppe',
                'field' => 'slug',
                'terms' => $warengruppe,
            ],
        ];
    }

    $query = new WP_Query($args);
    $gesamt_verfuegbarkeit = 0;

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $bestand = (int) get_post_meta(get_the_ID(), 'bestand', true);
            $reserviert = (int) get_post_meta(get_the_ID(), 'reserviert', true);
            $verfuegbarkeit = $bestand - $reserviert;

            // Nur positive Werte zur Gesamtsumme addieren
            if ($verfuegbarkeit > 0) {
                $gesamt_verfuegbarkeit += $verfuegbarkeit;
            }
        }
        wp_reset_postdata();
    }

    // Ausgabe der berechneten Verfügbarkeit
    return number_format_i18n($gesamt_verfuegbarkeit);
}

// Shortcode zur Anzeige der Gesamtverfügbarkeit
function hoffmann_gesamt_verfuegbarkeit_shortcode() {
    return hoffmann_gesamt_verfuegbarkeit();
}
add_shortcode('hoffmann_gesamt_verfuegbarkeit', 'hoffmann_gesamt_verfuegbarkeit_shortcode');


// Shortcode-Funktion für den Export-Link
function hoffmann_export_link_shortcode() {
    $export_url = esc_url(admin_url('admin-ajax.php?action=export_loop_grid_excel'));
    return '<a href="' . $export_url . '" class="button">Exportiere Excel</a>';
}
add_shortcode('hoffmann_export_link', 'hoffmann_export_link_shortcode');

// Funktion für den Excel-Export mit Debugging
function hoffmann_export_loop_grid_excel() {
    // Setze den Pfad zur PHPExcel-Bibliothek
    $phpexcel_path = plugin_dir_path(__FILE__) . 'PHPExcel.php';
    
    // Debug: Überprüfen, ob die Datei existiert
    if (file_exists($phpexcel_path)) {
        echo 'PHPExcel gefunden.';
        include_once($phpexcel_path);
    } else {
        wp_die('PHPExcel library not found at: ' . $phpexcel_path);
    }

    // Überprüfen, ob die PHPExcel-Klasse verfügbar ist
    if (!class_exists('PHPExcel')) {
        wp_die('PHPExcel class not loaded.');
    }

    // PHPExcel instanziieren
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->setActiveSheetIndex(0);
    $sheet->setCellValue('A1', 'Produktname');
    $sheet->setCellValue('B1', 'Artikelnummer');
    $sheet->setCellValue('C1', 'Verfügbarkeit');

    // Produkte mit Bestand und Verfügbarkeit abfragen
    $args = [
        'post_type' => 'produkte',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'bestand',
                'compare' => 'EXISTS',
            ],
        ],
    ];
    $query = new WP_Query($args);
    $row = 2;

    if ($query->have_posts()) {
        echo 'Produkte gefunden.';
        while ($query->have_posts()) {
            $query->the_post();
            $produktname = get_the_title();
            $artikelnummer = get_post_meta(get_the_ID(), 'artikelnummer', true);
            $bestand = (int) get_post_meta(get_the_ID(), 'bestand', true);
            $reserviert = (int) get_post_meta(get_the_ID(), 'reserviert', true);
            $verfuegbarkeit = max(0, $bestand - $reserviert);

            // Excel-Arbeitsblatt schreiben
            $sheet->setCellValue("A{$row}", $produktname);
            $sheet->setCellValue("B{$row}", $artikelnummer);
            $sheet->setCellValue("C{$row}", $verfuegbarkeit);
            $row++;
        }
        wp_reset_postdata();
    } else {
        wp_die('Keine Produkte gefunden.');
    }

    // Header für den Excel-Download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="produkte_export.xlsx"');
    header('Cache-Control: max-age=0');

    // Excel-Datei schreiben und ausgeben
    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $writer->save('php://output');
    exit();
}
add_action('wp_ajax_export_loop_grid_excel', 'hoffmann_export_loop_grid_excel');

