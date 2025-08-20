<?php
/**
 * Plugin Name: Hoffmann Bestellungen Importer
 * Description: Importiert und aktualisiert Bestellungen aus einer JSON-Datei, erstellt neue Beiträge im Post-Typ 'bestellungen', aktualisiert nur, wenn das Bestelldatum neuer ist und erstellt eine hierarchische Struktur anhand der Vorbelegnummer. Die Beiträge werden nach Bestellart kategorisiert.
 * Version: main-v1.0.0
 * Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debugging-Funktion
if (!function_exists('hoffmann_debug_log')) {
    function hoffmann_debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log($message);
        }
    }
}

// Post-Typ 'bestellungen' registrieren
function hoffmann_register_bestellungen_post_type() {
    register_post_type('bestellungen', array(
        'labels' => array(
            'name' => __('Bestellungen'),
            'singular_name' => __('Bestellung'),
        ),
        'public'        => true,
        'hierarchical'  => true,
        'supports'      => array('title','custom-fields','page-attributes'),
    ));
}
add_action('init', 'hoffmann_register_bestellungen_post_type');

// Taxonomie 'Bestellart' registrieren
function hoffmann_register_bestellart_taxonomy() {
    register_taxonomy('bestellart', 'bestellungen', array(
        'labels' => array(
            'name' => __('Bestellart'),
            'singular_name' => __('Bestellart'),
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
    ));
}
add_action('init', 'hoffmann_register_bestellart_taxonomy');

// Bestellungen importieren/aktualisieren
function hoffmann_import_bestellungen_from_json() {
    $json_path = WP_CONTENT_DIR . '/uploads/json/bestellungen.json';
    if (!file_exists($json_path)) {
        hoffmann_debug_log('JSON-Datei nicht gefunden: ' . $json_path);
        return;
    }
    $raw = file_get_contents($json_path);
    if (!$raw) {
        hoffmann_debug_log('Fehler beim Lesen der JSON-Datei.');
        return;
    }
    $bestellungen = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        hoffmann_debug_log('JSON-Fehler: ' . json_last_error_msg());
        return;
    }
    foreach ($bestellungen as $bestellung) {
        $bestellung_id = $bestellung['ID'];
        $bestellnummer = $bestellung['Belegnummer'];
        $bestelldatum   = $bestellung['Metadaten']['Belegdatum'];
        $bestellstatus  = $bestellung['Metadaten']['Belegstatus'];
        $kundennummer   = $bestellung['Metadaten']['Kundennummer'];
        $betragnetto    = $bestellung['Metadaten']['BetragNetto'];
        $vorbelegnummer = $bestellung['Metadaten']['Vorbelegnummer'];
        $air_cargo      = isset($bestellung['Metadaten']['AirCargoKosten']) ? $bestellung['Metadaten']['AirCargoKosten'] : '';
        $zoll_kosten    = isset($bestellung['Metadaten']['ZollAbwicklungKosten']) ? $bestellung['Metadaten']['ZollAbwicklungKosten'] : '';
        $bestellart_term = $bestellung['Metadaten']['Belegart'];

        // Vorhandene Posts prüfen
        $existing = get_posts(array(
            'post_type' => 'bestellungen',
            'meta_query' => array(array('key'=>'bestellungid','value'=>$bestellung_id,'compare'=>'=')),
            'posts_per_page'=>1,'fields'=>'ids'
        ));
        if (is_wp_error($existing)) {
            continue;
        }
        if (!empty($existing)) {
            $post_id = $existing[0];
            $old_date = get_post_meta($post_id, 'bestelldatum', true);
            if (strtotime($bestelldatum) > strtotime($old_date)) {
                wp_update_post(array('ID'=>$post_id,'post_title'=>$bestellnummer));
                update_post_meta($post_id,'bestellungstatus',$bestellstatus);
                update_post_meta($post_id,'kundennummer',$kundennummer);
                update_post_meta($post_id,'betragnetto',$betragnetto);
                update_post_meta($post_id,'bestelldatum',$bestelldatum);
                update_post_meta($post_id,'vorbeleg',$vorbelegnummer);
                update_post_meta($post_id,'air_cargo_kosten',$air_cargo);
                update_post_meta($post_id,'zoll_abwicklung_kosten',$zoll_kosten);
                if (!empty($vorbelegnummer)) {
                    $parent = get_posts(array('post_type'=>'bestellungen','title'=>$vorbelegnummer,'posts_per_page'=>1,'fields'=>'ids'));
                    if (!is_wp_error($parent) && !empty($parent)) {
                        wp_update_post(array('ID'=>$post_id,'post_parent'=>$parent[0]));
                    }
                }
                // Produkte in Repeater-Feld aktualisieren (ACF)
                if (function_exists('update_field')) {
                    $rows = array();
                    if (isset($bestellung['Produkte']) && is_array($bestellung['Produkte'])) {
                        foreach ($bestellung['Produkte'] as $prod) {
                            $rows[] = array(
                                'artikelnummer'          => sanitize_text_field($prod['Artikelnummer']),
                                'artikelbeschreibung'    => sanitize_text_field($prod['Bezeichnung']),
                                'menge'                  => intval($prod['Menge']),
                                'preis'                  => sanitize_text_field($prod['Einzelpreis']),
                            );
                        }
                    }
                    update_field('produkte', $rows, $post_id);
                }
            }
        } else {
            $data = array(
                'post_title'   => $bestellnummer,
                'post_type'    => 'bestellungen',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    'bestellungid'       => $bestellung_id,
                    'bestellungstatus'   => $bestellstatus,
                    'kundennummer'       => $kundennummer,
                    'betragnetto'        => $betragnetto,
                    'bestelldatum'       => $bestelldatum,
                    'vorbeleg'           => $vorbelegnummer,
                    'air_cargo_kosten'   => $air_cargo,
                    'zoll_abwicklung_kosten' => $zoll_kosten,
                ),
            );
            if (!empty($vorbelegnummer)) {
                $parent = get_posts(array(
                    'post_type'=>'bestellungen','title'=>$vorbelegnummer,'posts_per_page'=>1,'fields'=>'ids'
                ));
                if (!is_wp_error($parent) && !empty($parent)) {
                    $data['post_parent'] = $parent[0];
                }
            }
            $post_id = wp_insert_post($data);
            if (!is_wp_error($post_id)) {
                if (!term_exists($bestellart_term, 'bestellart')) {
                    wp_insert_term($bestellart_term, 'bestellart');
                }
                wp_set_object_terms($post_id, $bestellart_term, 'bestellart');
                // Produkte in Repeater-Feld speichern (ACF)
                if (function_exists('update_field')) {
                    $rows = array();
                    if (isset($bestellung['Produkte']) && is_array($bestellung['Produkte'])) {
                        foreach ($bestellung['Produkte'] as $prod) {
                            $rows[] = array(
                                'artikelnummer'          => sanitize_text_field($prod['Artikelnummer']),
                                'artikelbeschreibung'    => sanitize_text_field($prod['Bezeichnung']),
                                'menge'                  => intval($prod['Menge']),
                                'preis'                  => sanitize_text_field($prod['Einzelpreis']),
                            );
                        }
                    }
                    update_field('produkte', $rows, $post_id);
                }
            } else {
                hoffmann_debug_log("Fehler beim Erstellen der Bestellung: {$bestellnummer}");
            }
        }
    }
}
add_action('wp_loaded','hoffmann_import_bestellungen_from_json');

// Cron-Interval hinzufügen
add_filter('cron_schedules','hoffmann_bestellungen_cron_schedule');
function hoffmann_bestellungen_cron_schedule($schedules) {
    $schedules['five_minutes'] = array('interval'=>300,'display'=>__('Alle 5 Minuten'));
    return $schedules;
}

// Cron-Job registrieren
add_action('wp','hoffmann_bestellungen_cron_job');
function hoffmann_bestellungen_cron_job() {
    if (!wp_next_scheduled('hoffmann_bestellungen_sync')) {
        wp_schedule_event(time(),'five_minutes','hoffmann_bestellungen_sync');
    }
}
add_action('hoffmann_bestellungen_sync','hoffmann_import_bestellungen_from_json');

// Cron-Job deaktivieren
register_deactivation_hook(__FILE__,'hoffmann_bestellungen_deactivate');
function hoffmann_bestellungen_deactivate() {
    $ts = wp_next_scheduled('hoffmann_bestellungen_sync');
    if ($ts) wp_unschedule_event($ts,'hoffmann_bestellungen_sync');
}

// Submenu: Alle Bestellungen löschen
add_action('admin_menu','hoffmann_bestellungen_delete_menu');
function hoffmann_bestellungen_delete_menu() {
    add_submenu_page('edit.php?post_type=bestellungen','Alle Bestellungen löschen','Alle löschen','delete_posts','hoffmann-delete-bestellungen','hoffmann_delete_bestellungen_page');
}

function hoffmann_delete_bestellungen_page() {
    if (!current_user_can('delete_posts')) wp_die('Keine Berechtigung');
    $nonce = wp_create_nonce('hoffmann_delete_bestellungen');
    echo '<div class="wrap"><h1>Alle Bestellungen löschen</h1>';
    echo '<form method="post" action="'.admin_url('admin-post.php').'">';
    echo '<input type="hidden" name="action" value="hoffmann_delete_bestellungen">';
    echo '<input type="hidden" name="hoffmann_delete_bestellungen_nonce" value="'.esc_attr($nonce).'">';
    submit_button('Alle Bestellungen löschen','delete');
    echo '</form></div>';
}
add_action('admin_post_hoffmann_delete_bestellungen','hoffmann_handle_delete_bestellungen');
function hoffmann_handle_delete_bestellungen() {
    if (!current_user_can('delete_posts')) wp_die('Keine Berechtigung');
    check_admin_referer('hoffmann_delete_bestellungen','hoffmann_delete_bestellungen_nonce');
    $all = get_posts(array('post_type'=>'bestellungen','posts_per_page'=>-1,'fields'=>'ids'));
    foreach($all as $pid) {
        wp_delete_post($pid,true);
    }
    wp_redirect(add_query_arg('post_type','bestellungen',admin_url('edit.php')));
    exit;
}

// Admin-Spalte 'Vorbelegnummer' hinzufügen
add_filter('manage_bestellungen_posts_columns','hoffmann_bestellungen_columns');
function hoffmann_bestellungen_columns($columns) {
    $columns['vorbeleg'] = __('Vorbelegnummer');
    return $columns;
}
add_action('manage_bestellungen_posts_custom_column','hoffmann_bestellungen_custom_column',10,2);
function hoffmann_bestellungen_custom_column($column,$post_id) {
    if ($column==='vorbeleg') {
        $val = get_post_meta($post_id,'vorbeleg',true);
        if ($val) {
            $parent = get_page_by_title($val, OBJECT, 'bestellungen');
            if ($parent) {
                $link = get_edit_post_link($parent->ID);
                echo '<a href="' . esc_url($link) . '">' . esc_html($val) . '</a>';
            } else {
                echo esc_html($val);
            }
        }
    }
}

// Metabox zur Anzeige der Metadaten
add_action('add_meta_boxes_bestellungen','hoffmann_bestellungen_meta_box_init');
function hoffmann_bestellungen_meta_box_init(){
    add_meta_box('hoffmann_bestellungen_meta',__('Bestelldetails'),'hoffmann_bestellungen_meta_box','bestellungen','normal','default');
}
function hoffmann_bestellungen_meta_box($post){
    $fields = array(
        'bestellungid'           => __('Bestellung ID'),
        'bestelldatum'           => __('Bestelldatum'),
        'bestellungstatus'       => __('Status'),
        'kundennummer'           => __('Kundennummer'),
        'betragnetto'            => __('Betrag Netto'),
        'air_cargo_kosten'       => __('Air-Cargo-Kosten'),
        'zoll_abwicklung_kosten' => __('Zoll-Abwicklung-Kosten'),
        'vorbeleg'               => __('Vorbelegnummer'),
    );
    echo '<table class="form-table"><tbody>';
    foreach($fields as $key=>$label){
        $val = esc_html(get_post_meta($post->ID,$key,true));
        echo '<tr><th>'.esc_html($label).'</th><td>'.$val.'</td></tr>';
    }
    echo '</tbody></table>';

    // Produkte aus dem ACF-Repeater anzeigen
    if (function_exists('get_field')) {
        $produkte = get_field('produkte', $post->ID);
        if ($produkte) {
            echo '<h4>'.esc_html__('Produkte').'</h4>';
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>'.esc_html__('Artikelnummer').'</th>';
            echo '<th>'.esc_html__('Artikelbeschreibung').'</th>';
            echo '<th>'.esc_html__('Menge').'</th>';
            echo '<th>'.esc_html__('Preis').'</th>';
            echo '</tr></thead><tbody>';
            foreach ($produkte as $prod) {
                echo '<tr>';
                echo '<td>'.esc_html($prod['artikelnummer']).'</td>';
                echo '<td>'.esc_html($prod['artikelbeschreibung']).'</td>';
                echo '<td>'.esc_html($prod['menge']).'</td>';
                echo '<td>'.esc_html($prod['preis']).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }
}
