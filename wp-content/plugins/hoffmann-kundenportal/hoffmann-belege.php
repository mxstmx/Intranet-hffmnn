<?php
/**
 * Plugin Name: Hoffmann Belege Importer
 * Description: Importiert und aktualisiert Belege aus einer JSON-Datei, erstellt neue Beiträge im Post-Typ 'belege', aktualisiert nur, wenn das Belegdatum neuer ist und erstellt eine hierarchische Struktur anhand der Vorbelegnummer. Die Beiträge werden nach Belegart kategorisiert.
 * Version: main-v1.0.1
 * Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/lib/produkte-metabox.php';


// Debugging-Funktion
if (!function_exists('hoffmann_debug_log')) {
    function hoffmann_debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log($message);
        }
    }
}

// Post-Typ 'belege' registrieren
function hoffmann_register_belege_post_type() {
    if (post_type_exists('belege')) return;
    register_post_type('belege', array(
        'labels' => array(
            'name'          => __('Belege'),
            'singular_name' => __('Beleg'),
        ),
        'public'       => true,
        'hierarchical' => true,
        'supports'     => array('title','custom-fields','page-attributes'),
    ));
}
add_action('init','hoffmann_register_belege_post_type');

// Taxonomie 'Belegart' registrieren
function hoffmann_register_belegart_taxonomy() {
    register_taxonomy('belegart', 'belege', array(
        'labels' => array(
            'name' => __('Belegart'),
            'singular_name' => __('Belegart'),
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
    ));
}
add_action('init', 'hoffmann_register_belegart_taxonomy');

// Belege importieren/aktualisieren
function hoffmann_import_belege_from_json() {
    $json_path = WP_CONTENT_DIR . '/uploads/json/belege.json';
    if (!file_exists($json_path)) {
        hoffmann_debug_log('JSON-Datei nicht gefunden: ' . $json_path);
        return;
    }
    $raw = file_get_contents($json_path);
    if (!$raw) {
        hoffmann_debug_log('Fehler beim Lesen der JSON-Datei.');
        return;
    }
    $belege = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        hoffmann_debug_log('JSON-Fehler: ' . json_last_error_msg());
        return;
    }
    foreach ($belege as $beleg) {
        $beleg_id = $beleg['ID'];
        $belegnummer = $beleg['Belegnummer'];
        $belegdatum   = $beleg['Metadaten']['Belegdatum'];
        $belegstatus  = $beleg['Metadaten']['Belegstatus'];
        $kundennummer = $beleg['Metadaten']['Kundennummer'];
        $betragnetto  = $beleg['Metadaten']['BetragNetto'];
        $vorbelegnummer = $beleg['Metadaten']['Vorbelegnummer'];
        $air_cargo    = isset($beleg['Metadaten']['AirCargoKosten']) ? $beleg['Metadaten']['AirCargoKosten'] : '';
        $zoll_kosten  = isset($beleg['Metadaten']['ZollAbwicklungKosten']) ? $beleg['Metadaten']['ZollAbwicklungKosten'] : '';
        $belegart_term = $beleg['Metadaten']['Belegart'];

        // Vorhandene Posts prüfen
        $existing = get_posts(array(
            'post_type' => 'belege',
            'meta_query' => array(array('key'=>'belegid','value'=>$beleg_id,'compare'=>'=')),
            'posts_per_page'=>1,'fields'=>'ids'
        ));
        if (is_wp_error($existing)) {
            continue;
        }
        if (!empty($existing)) {
            $post_id = $existing[0];
            $old_date = get_post_meta($post_id, 'belegdatum', true);
            if (strtotime($belegdatum) > strtotime($old_date)) {
                wp_update_post(array('ID'=>$post_id,'post_title'=>$belegnummer));
                update_post_meta($post_id,'belegstatus',$belegstatus);
                update_post_meta($post_id,'kundennummer',$kundennummer);
                update_post_meta($post_id,'betragnetto',$betragnetto);
                update_post_meta($post_id,'belegdatum',$belegdatum);
                update_post_meta($post_id,'vorbeleg',$vorbelegnummer);
                update_post_meta($post_id,'air_cargo_kosten',$air_cargo);
                update_post_meta($post_id,'zoll_abwicklung_kosten',$zoll_kosten);
                if (!empty($vorbelegnummer)) {
                    $parent = get_posts(array('post_type'=>'belege','title'=>$vorbelegnummer,'posts_per_page'=>1,'fields'=>'ids'));
                    if (!is_wp_error($parent) && !empty($parent)) {
                        wp_update_post(array('ID'=>$post_id,'post_parent'=>$parent[0]));
                    }
                }
                hoffmann_debug_log("Beleg aktualisiert: ID {$post_id}, Nummer {$belegnummer}");
                if (isset($beleg['Produkte'])) {
                    update_post_meta($post_id, 'produkte', wp_json_encode($beleg['Produkte']));
                }
            }
        } else {
            $data = array(
                'post_title'   => $belegnummer,
                'post_type'    => 'belege',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    'belegid'         => $beleg_id,
                    'belegstatus'     => $belegstatus,
                    'kundennummer'    => $kundennummer,
                    'betragnetto'     => $betragnetto,
                    'belegdatum'      => $belegdatum,
                    'vorbeleg'        => $vorbelegnummer,
                    'air_cargo_kosten'   => $air_cargo,
                    'zoll_abwicklung_kosten' => $zoll_kosten,
                    'produkte'        => isset($beleg['Produkte']) ? wp_json_encode($beleg['Produkte']) : '',
                ),
            );
            if (!empty($vorbelegnummer)) {
                $parent = get_posts(array(
                    'post_type'=>'belege','title'=>$vorbelegnummer,'posts_per_page'=>1,'fields'=>'ids'
                ));
                if (!is_wp_error($parent) && !empty($parent)) {
                    $data['post_parent'] = $parent[0];
                }
            }
            $post_id = wp_insert_post($data);
            if (!is_wp_error($post_id)) {
                if (!term_exists($belegart_term, 'belegart')) {
                    wp_insert_term($belegart_term, 'belegart');
                }
                wp_set_object_terms($post_id, $belegart_term, 'belegart');
                hoffmann_debug_log("Neuer Beleg erstellt: ID {$post_id}, Nummer {$belegnummer}");
                if (isset($beleg['Produkte'])) {
                    update_post_meta($post_id, 'produkte', wp_json_encode($beleg['Produkte']));
                }
            } else {
                hoffmann_debug_log("Fehler beim Erstellen des Belegs: {$belegnummer}");
            }
        }
    }
}
add_action('wp_loaded','hoffmann_import_belege_from_json');

// Cron-Interval hinzufügen
add_filter('cron_schedules','hoffmann_belege_cron_schedule');
function hoffmann_belege_cron_schedule($schedules) {
    $schedules['five_minutes'] = array('interval'=>300,'display'=>__('Alle 5 Minuten'));
    return $schedules;
}

// Cron-Job registrieren
add_action('wp','hoffmann_belege_cron_job');
function hoffmann_belege_cron_job() {
    if (!wp_next_scheduled('hoffmann_belege_sync')) {
        wp_schedule_event(time(),'five_minutes','hoffmann_belege_sync');
    }
}
add_action('hoffmann_belege_sync','hoffmann_import_belege_from_json');

// Cron-Job deaktivieren
register_deactivation_hook(__FILE__,'hoffmann_deactivate_plugin');
function hoffmann_deactivate_plugin() {
    $ts = wp_next_scheduled('hoffmann_belege_sync');
    if ($ts) wp_unschedule_event($ts,'hoffmann_belege_sync');
}

// Submenu: Alle Belege löschen
add_action('admin_menu','hoffmann_belege_delete_menu');
function hoffmann_belege_delete_menu() {
    add_submenu_page('edit.php?post_type=belege','Alle Belege löschen','Alle löschen','delete_posts','hoffmann-delete-belege','hoffmann_delete_belege_page');
}

function hoffmann_delete_belege_page() {
    if (!current_user_can('delete_posts')) wp_die('Keine Berechtigung');
    $nonce = wp_create_nonce('hoffmann_delete_belege');
    echo '<div class="wrap"><h1>Alle Belege löschen</h1>';
    echo '<form method="post" action="'.admin_url('admin-post.php').'">';
    echo '<input type="hidden" name="action" value="hoffmann_delete_belege">';
    echo '<input type="hidden" name="hoffmann_delete_belege_nonce" value="'.esc_attr($nonce).'">';
    submit_button('Alle Belege löschen','delete');
    echo '</form></div>';
}
add_action('admin_post_hoffmann_delete_belege','hoffmann_handle_delete_belege');
function hoffmann_handle_delete_belege() {
    if (!current_user_can('delete_posts')) wp_die('Keine Berechtigung');
    check_admin_referer('hoffmann_delete_belege','hoffmann_delete_belege_nonce');
    $all = get_posts(array('post_type'=>'belege','posts_per_page'=>-1,'fields'=>'ids'));
    foreach($all as $pid) {
        wp_delete_post($pid,true);
    }
    wp_redirect(add_query_arg('post_type','belege',admin_url('edit.php')));
    exit;
}

// Admin-Spalte 'Vorbelegnummer' hinzufügen
add_filter('manage_belege_posts_columns','hoffmann_belege_columns');
function hoffmann_belege_columns($columns) {
    $columns['vorbeleg'] = __('Vorbelegnummer');
    return $columns;
}
add_action('manage_belege_posts_custom_column','hoffmann_belege_custom_column',10,2);
function hoffmann_belege_custom_column($column,$post_id) {
    if ($column==='vorbeleg') {
        $val = get_post_meta($post_id,'vorbeleg',true);
        if ($val) {
            $parent = get_page_by_title($val, OBJECT, 'belege');
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
add_action('add_meta_boxes_belege','hoffmann_belege_meta_box_init');
function hoffmann_belege_meta_box_init(){
    add_meta_box('hoffmann_belege_meta',__('Belegdetails'),'hoffmann_belege_meta_box','belege','normal','default');
}
function hoffmann_belege_meta_box($post){
    $fields = array(
        'belegid'               => __('Beleg ID'),
        'belegdatum'            => __('Belegdatum'),
        'belegstatus'           => __('Status'),
        'kundennummer'          => __('Kundennummer'),
        'betragnetto'           => __('Betrag Netto'),
        'air_cargo_kosten'      => __('Air-Cargo-Kosten'),
        'zoll_abwicklung_kosten'=> __('Zoll-Abwicklung-Kosten'),
        'vorbeleg'              => __('Vorbelegnummer'),
    );
    echo '<table class="form-table"><tbody>';
    foreach($fields as $key=>$label){
        $val = esc_html(get_post_meta($post->ID,$key,true));
        echo '<tr><th>'.esc_html($label).'</th><td>'.$val.'</td></tr>';
    }
    echo '</tbody></table>';
}
