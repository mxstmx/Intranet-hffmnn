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

require_once __DIR__ . '/lib/produkte-metabox.php';
require_once __DIR__ . '/lib/number-utils.php';
require_once __DIR__ . "/hoffmann-bestellungen-overview.php";

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
        $belegdatum        = $bestellung['Metadaten']['Belegdatum'];
        $letzte_aenderung  = isset($bestellung['Metadaten']['LetzteAenderung']) ? $bestellung['Metadaten']['LetzteAenderung'] : '';
        $adressnummer      = $bestellung['Metadaten']['Adressnummer'];
        $namezeile2        = isset($bestellung['Metadaten']['Namezeile2']) ? $bestellung['Metadaten']['Namezeile2'] : '';
        $betreff           = isset($bestellung['Metadaten']['Betreff']) ? $bestellung['Metadaten']['Betreff'] : '';
        $lfbelegnummer     = isset($bestellung['Metadaten']['LFBelegnummer']) ? $bestellung['Metadaten']['LFBelegnummer'] : '';
        $belegart          = $bestellung['Metadaten']['Belegart'];
        $vorbelegnummer    = isset($bestellung['Metadaten']['Vorbelegnummer']) ? $bestellung['Metadaten']['Vorbelegnummer'] : '';
        $betragnetto       = $bestellung['Metadaten']['BetragNetto'];
        $bestellstatus     = isset($bestellung['Metadaten']['Belegstatus']) ? $bestellung['Metadaten']['Belegstatus'] : '';

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
            $old_date = get_post_meta($post_id, 'belegdatum', true);
            if (strtotime($belegdatum) > strtotime($old_date)) {
                wp_update_post(array('ID'=>$post_id,'post_title'=>$bestellnummer));
                update_post_meta($post_id,'bestellungstatus',$bestellstatus);
                update_post_meta($post_id,'adressnummer',$adressnummer);
                update_post_meta($post_id,'namezeile2',$namezeile2);
                update_post_meta($post_id,'betreff',$betreff);
                update_post_meta($post_id,'lfbelegnummer',$lfbelegnummer);
                update_post_meta($post_id,'belegart',$belegart);
                update_post_meta($post_id,'vorbelegnummer',$vorbelegnummer);
                update_post_meta($post_id,'betragnetto',$betragnetto);
                update_post_meta($post_id,'belegdatum',$belegdatum);
                update_post_meta($post_id,'letzte_aenderung',$letzte_aenderung);
                if (!empty($vorbelegnummer)) {
                    $parent = get_posts(array('post_type'=>'bestellungen','title'=>$vorbelegnummer,'posts_per_page'=>1,'fields'=>'ids'));
                    if (!is_wp_error($parent) && !empty($parent)) {
                        wp_update_post(array('ID'=>$post_id,'post_parent'=>$parent[0]));
                    }
                }
                // Produkte als JSON speichern
                if (isset($bestellung['Produkte'])) {
                    update_post_meta($post_id, 'produkte', wp_json_encode($bestellung['Produkte']));
                }
            }
        } else {
            $data = array(
                'post_title'   => $bestellnummer,
                'post_type'    => 'bestellungen',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    'bestellungid'     => $bestellung_id,
                    'bestellungstatus' => $bestellstatus,
                    'adressnummer'     => $adressnummer,
                    'namezeile2'       => $namezeile2,
                    'betreff'          => $betreff,
                    'lfbelegnummer'    => $lfbelegnummer,
                    'belegart'         => $belegart,
                    'vorbelegnummer'   => $vorbelegnummer,
                    'betragnetto'      => $betragnetto,
                    'belegdatum'       => $belegdatum,
                    'letzte_aenderung' => $letzte_aenderung,
                    'produkte'         => isset($bestellung['Produkte']) ? wp_json_encode($bestellung['Produkte']) : '',
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
                if (!term_exists($belegart, 'bestellart')) {
                    wp_insert_term($belegart, 'bestellart');
                }
                wp_set_object_terms($post_id, $belegart, 'bestellart');
                // Produkte als JSON speichern
                if (isset($bestellung['Produkte'])) {
                    update_post_meta($post_id, 'produkte', wp_json_encode($bestellung['Produkte']));
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
    $columns['vorbelegnummer'] = __('Vorbelegnummer');
    return $columns;
}
add_action('manage_bestellungen_posts_custom_column','hoffmann_bestellungen_custom_column',10,2);
function hoffmann_bestellungen_custom_column($column,$post_id) {
    if ($column==='vorbelegnummer') {
        $val = get_post_meta($post_id,'vorbelegnummer',true);
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
    wp_nonce_field('hoffmann_bestellungen_meta','hoffmann_bestellungen_meta_nonce');
    $fields = array(
        'bestellungid'           => __('Bestellung ID'),
        'belegdatum'             => __('Belegdatum'),
        'bestellungstatus'       => __('Status'),
        'adressnummer'           => __('Adressnummer'),
        'namezeile2'             => __('Namezeile2'),
        'betreff'                => __('Betreff'),
        'lfbelegnummer'          => __('LF-Belegnummer'),
        'belegart'               => __('Belegart'),
        'vorbelegnummer'         => __('Vorbelegnummer'),
        'betragnetto'            => __('Betrag Netto'),
        'letzte_aenderung'       => __('Letzte Änderung'),
    );
    echo '<table class="form-table"><tbody>';
    foreach($fields as $key=>$label){
        $val = get_post_meta($post->ID,$key,true);
        if ($key === 'betragnetto') {
            $val = hoffmann_format_currency($val);
        }
        echo '<tr><th>'.esc_html($label).'</th><td>'.esc_html($val).'</td></tr>';
    }
    echo '</tbody></table>';
    $air  = esc_attr(get_post_meta($post->ID,'air_cargo_kosten',true));
    $zoll = esc_attr(get_post_meta($post->ID,'zoll_abwicklung_kosten',true));
    echo '<p><label>'.esc_html__('AirCargo Kosten','hoffmann').'<br><input type="text" name="air_cargo_kosten" value="'.$air.'"></label></p>';
    echo '<p><label>'.esc_html__('Zoll Abwicklung','hoffmann').'<br><input type="text" name="zoll_abwicklung_kosten" value="'.$zoll.'"></label></p>';
}

add_action('save_post_bestellungen','hoffmann_bestellungen_save_admin_meta');
function hoffmann_bestellungen_save_admin_meta($post_id){
    if (!isset($_POST['hoffmann_bestellungen_meta_nonce']) || !wp_verify_nonce($_POST['hoffmann_bestellungen_meta_nonce'],'hoffmann_bestellungen_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['air_cargo_kosten'])) {
        $air = hoffmann_to_float(sanitize_text_field($_POST['air_cargo_kosten']));
        update_post_meta($post_id, 'air_cargo_kosten', $air);
    }
    if (isset($_POST['zoll_abwicklung_kosten'])) {
        $zoll = hoffmann_to_float(sanitize_text_field($_POST['zoll_abwicklung_kosten']));
        update_post_meta($post_id, 'zoll_abwicklung_kosten', $zoll);
    }
}

// Hilfsfunktion: Summe der Produktmengen ermitteln
if (!function_exists('hoffmann_sum_produkt_mengen')) {
    function hoffmann_sum_produkt_mengen($post_id) {
        $data = get_post_meta($post_id, 'produkte', true);
        if (!$data) {
            return 0;
        }
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        return hoffmann_sum_menge_recursive($data);
    }
}

if (!function_exists('hoffmann_bestellung_detail_html')) {
    function hoffmann_bestellung_detail_html($pid) {
        $title = esc_html(get_the_title($pid));
        $fields = array(
            'bestellungid'     => __('Bestellung ID', 'hoffmann'),
            'belegdatum'       => __('Belegdatum', 'hoffmann'),
            'betreff'          => __('Betreff', 'hoffmann'),
            'bestellungstatus' => __('Status', 'hoffmann'),
            'lfbelegnummer'    => __('LF-Belegnummer', 'hoffmann'),
            'belegart'         => __('Belegart', 'hoffmann'),
            'vorbelegnummer'   => __('Vorbelegnummer', 'hoffmann'),
            'betragnetto'      => __('Betrag Netto', 'hoffmann'),
            'letzte_aenderung' => __('Letzte Änderung', 'hoffmann'),
        );
        $betreff = get_post_meta($pid, 'betreff', true);
        $html = '<h3>'. $title .'</h3>';
        if ($betreff) {
            $html .= '<p><strong>'.esc_html__('Betreff', 'hoffmann').':</strong> '.esc_html($betreff).'</p>';
        }
        $html .= '<table style="width:100%;border-collapse:collapse;"><tbody>';
        foreach ($fields as $key => $label) {
            $val = get_post_meta($pid, $key, true);
            if ($key === 'betragnetto') {
                $val = hoffmann_format_currency($val);
            }
            if (in_array($key, ['belegdatum', 'letzte_aenderung'], true) && $val) {
                $val = date_i18n('d.m.Y', strtotime($val));
            }
            $html .= '<tr><th style="text-align:left;">' . esc_html($label) . '</th><td>' . esc_html($val) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $prod = get_post_meta($pid, 'produkte', true);
        if (!is_array($prod)) {
            $prod = json_decode($prod, true);
        }
        if ($prod) {
            $html .= '<h4>'.esc_html__('Produkte', 'hoffmann').'</h4>';
            $html .= '<table style="width:100%;border-collapse:collapse;"><thead><tr>'
                .'<th>'.esc_html__('Artikelnummer', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Bezeichnung', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Bestellt', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Geliefert', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Rest', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Status', 'hoffmann').'</th>'
                .'<th>'.esc_html__('Preis', 'hoffmann').'</th>'
                .'</tr></thead><tbody>';
            foreach ($prod as $item) {
                $artikelnr = $item['Artikelnummer'] ?? '';
                $bez       = $item['Bezeichnung'] ?? '';
                $bestellt  = $item['Bestellt'] ?? $item['Menge'] ?? '';
                $geliefert = $item['Geliefert'] ?? '';
                $rest      = $item['Rest'] ?? '';
                $status    = $item['Status'] ?? '';
                $preis_raw = $item['Preis'] ?? $item['Einzelpreis'] ?? '';
                $preis     = hoffmann_format_currency($preis_raw);
                $html .= '<tr><td>'.esc_html($artikelnr).'</td><td>'.esc_html($bez).'</td>'
                    .'<td>'.esc_html($bestellt).'</td><td>'.esc_html($geliefert).'</td>'
                    .'<td>'.esc_html($rest).'</td><td>'.esc_html($status).'</td>'
                    .'<td>'.esc_html($preis).'</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $air_raw  = get_post_meta($pid, 'air_cargo_kosten', true);
        $zoll_raw = get_post_meta($pid, 'zoll_abwicklung_kosten', true);

        $html .= '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        $html .= wp_nonce_field('hoffmann_save_order_costs_'.$pid, '_wpnonce', true, false);
        $html .= '<input type="hidden" name="action" value="hoffmann_save_order_costs">';
        $html .= '<input type="hidden" name="post_id" value="'.esc_attr($pid).'">';
        $html .= '<p><label>Aircargo <input type="text" name="air_cargo_kosten" value="'.esc_attr($air_raw).'"></label></p>';
        $html .= '<p><label>Zoll Kosten <input type="text" name="zoll_abwicklung_kosten" value="'.esc_attr($zoll_raw).'"></label></p>';
        $html .= '<p><button type="submit">'.esc_html__('Speichern', 'hoffmann').'</button></p>';
        $html .= '</form>';
        return $html;
    }
}

add_action('admin_post_hoffmann_save_order_costs', 'hoffmann_save_order_costs');
if (!function_exists('hoffmann_save_order_costs')) {
    function hoffmann_save_order_costs() {
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_die('Keine Berechtigung');
        }
        check_admin_referer('hoffmann_save_order_costs_'.$post_id);
        $air  = isset($_POST['air_cargo_kosten']) ? hoffmann_to_float(sanitize_text_field($_POST['air_cargo_kosten'])) : 0.0;
        $zoll = isset($_POST['zoll_abwicklung_kosten']) ? hoffmann_to_float(sanitize_text_field($_POST['zoll_abwicklung_kosten'])) : 0.0;
        update_post_meta($post_id, 'air_cargo_kosten', $air);
        update_post_meta($post_id, 'zoll_abwicklung_kosten', $zoll);
        wp_redirect('https://dashboard.hoffmann-hd.de/bestellungen/');
        exit;
    }
}

add_action('wp_ajax_hoffmann_save_lieferschein_costs','hoffmann_save_lieferschein_costs');
add_action('wp_ajax_nopriv_hoffmann_save_lieferschein_costs','hoffmann_save_lieferschein_costs');
function hoffmann_save_lieferschein_costs(){
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if(!$post_id || !current_user_can('edit_post',$post_id)){
        wp_send_json_error();
    }
    check_ajax_referer('hoffmann_lieferschein_costs','nonce');
    $air  = isset($_POST['air_cargo_kosten']) ? hoffmann_to_float(sanitize_text_field($_POST['air_cargo_kosten'])) : 0.0;
    $zoll = isset($_POST['zoll_abwicklung_kosten']) ? hoffmann_to_float(sanitize_text_field($_POST['zoll_abwicklung_kosten'])) : 0.0;
    update_post_meta($post_id,'air_cargo_kosten',$air);
    update_post_meta($post_id,'zoll_abwicklung_kosten',$zoll);
    wp_send_json_success();
}

add_action('wp_ajax_hoffmann_save_wechselkurs','hoffmann_save_wechselkurs');
add_action('wp_ajax_nopriv_hoffmann_save_wechselkurs','hoffmann_save_wechselkurs');
function hoffmann_save_wechselkurs(){
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if(!$post_id || !current_user_can('edit_post',$post_id)){
        wp_send_json_error();
    }
    check_ajax_referer('hoffmann_wechselkurs','nonce');
    $rate = isset($_POST['wechselkurs']) ? hoffmann_to_float(sanitize_text_field($_POST['wechselkurs'])) : 0.0;
    update_post_meta($post_id,'wechselkurs',$rate);
    wp_send_json_success();
}

if (!function_exists('hoffmann_sum_menge_recursive')) {
    function hoffmann_sum_menge_recursive($items) {
        $sum = 0;
        if (!is_array($items)) {
            return $sum;
        }
        foreach ($items as $key => $val) {
            if (is_array($val)) {
                $sum += hoffmann_sum_menge_recursive($val);
            } elseif ($key === 'Menge' || $key === 'menge') {
                $sum += intval($val);
            }
        }
        return $sum;
    }
}

if (!function_exists('hoffmann_bestellungen_get_rows')) {
    function hoffmann_bestellungen_get_rows($args) {
        $query  = new WP_Query($args);
        $rows   = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid    = get_the_ID();
                $datum  = get_post_meta($pid, 'belegdatum', true);
                $betreff= get_post_meta($pid, 'betreff', true);
                $menge  = hoffmann_sum_produkt_mengen($pid);
                $rows  .= '<tr class="hoffmann-parent" data-id="'.esc_attr($pid).'">';
                $rows  .= '<td><a href="'.get_permalink().'">'.esc_html(get_the_title()).'</a></td>';
                $rows  .= '<td>'.esc_html($datum ? date_i18n('d.m.Y', strtotime($datum)) : '').'</td>';
                $rows  .= '<td>'.esc_html($betreff).'</td>';
                $rows  .= '<td>'.esc_html(number_format_i18n($menge)).'</td>';
                $rows  .= '</tr>';

                $children = get_children(array(
                    'post_type'   => 'bestellungen',
                    'post_parent' => $pid,
                    'orderby'     => 'title',
                    'order'       => 'ASC',
                ));
                if ($children) {
                    foreach ($children as $child) {
                        $cid      = $child->ID;
                        $c_datum  = get_post_meta($cid, 'belegdatum', true);
                        $c_betreff= get_post_meta($cid, 'betreff', true);
                        $c_menge  = hoffmann_sum_produkt_mengen($cid);
                        $rows .= '<tr class="hoffmann-child" data-parent="'.esc_attr($pid).'">';
                        $rows .= '<td><a href="'.esc_url(get_permalink($cid)).'">'.esc_html($child->post_title).'</a></td>';
                        $rows .= '<td>'.esc_html($c_datum ? date_i18n('d.m.Y', strtotime($c_datum)) : '').'</td>';
                        $rows .= '<td>'.esc_html($c_betreff).'</td>';
                        $rows .= '<td>'.esc_html(number_format_i18n($c_menge)).'</td>';
                        $rows .= '</tr>';
                    }
                }
            }
        }
        wp_reset_postdata();
        return array('rows' => $rows);
    }
}

function hoffmann_bestellungen_ajax_search() {
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $start  = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
    $end    = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
    $args = array(
        'post_type'      => 'bestellungen',
        'post_parent'    => 0,
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'bestellart',
                'field'    => 'name',
                'terms'    => '2200',
            ),
        ),
    );
    $meta_query = array();
    if ($start || $end) {
        $date_filter = array('key' => 'belegdatum', 'type' => 'DATE');
        if ($start && $end) {
            $date_filter['value'] = array($start, $end);
            $date_filter['compare'] = 'BETWEEN';
        } elseif ($start) {
            $date_filter['value'] = $start;
            $date_filter['compare'] = '>=';
        } else {
            $date_filter['value'] = $end;
            $date_filter['compare'] = '<=';
        }
        $meta_query[] = $date_filter;
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    if ($search) {
        $args['s'] = $search;
    }
    $result = hoffmann_bestellungen_get_rows($args);
    wp_send_json_success($result);
}
add_action('wp_ajax_hoffmann_bestellungen_search','hoffmann_bestellungen_ajax_search');
add_action('wp_ajax_nopriv_hoffmann_bestellungen_search','hoffmann_bestellungen_ajax_search');


add_filter('the_content','hoffmann_bestellung_single_content');
function hoffmann_bestellung_single_content($content){
    if (!is_singular('bestellungen') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    global $post;
    if (!has_term(array('2200','2900'),'bestellart',$post)) {
        return $content;
    }
    $pid = $post->ID;
    $title = get_the_title($pid);
    $exchange_rate = hoffmann_to_float(get_post_meta($pid, 'wechselkurs', true));
    if (!$exchange_rate) { $exchange_rate = 1.0; }
    if (has_term('2900','bestellart',$post)) {
        return $content . hoffmann_bestellung_detail_html($pid);
    }
    $data = get_post_meta($pid, 'produkte', true);
    if (!is_array($data)) {
        $data = json_decode($data, true);
    }
    if (!$data) {
        return $content;
    }
    $products = array();
    foreach ($data as $item) {
        if (!is_array($item)) { continue; }
        $art = isset($item['Artikelnummer']) ? $item['Artikelnummer'] : '';
        if (!$art) { continue; }
        $products[$art] = array(
            'bezeichnung' => isset($item['Bezeichnung']) ? $item['Bezeichnung'] : '',
            'ordered'     => isset($item['Menge']) ? intval($item['Menge']) : 0,
            'preis'       => isset($item['Einzelpreis']) ? (float) $item['Einzelpreis'] : 0,
            'delivered'   => 0,
        );
    }
    $children = get_posts(array(
        'post_type'      => 'bestellungen',
        'post_parent'    => $pid,
        'posts_per_page' => -1,
        'tax_query'      => array(array('taxonomy'=>'bestellart','field'=>'name','terms'=>'2900')),
    ));
    $total_delivered_qty = 0;
    $total_air = 0;
    $total_zoll = 0;
    foreach ($children as $child) {
        $cid       = $child->ID;
        $air_raw   = get_post_meta($cid,'air_cargo_kosten',true);
        $air       = hoffmann_to_float($air_raw);
        $total_air += $air;

        $zoll_raw  = get_post_meta($cid,'zoll_abwicklung_kosten',true);
        $zoll      = hoffmann_to_float($zoll_raw);
        $total_zoll += $zoll;
        $prod = get_post_meta($cid, 'produkte', true);
        if (!is_array($prod)) { $prod = json_decode($prod, true); }
        if (is_array($prod)) {
            foreach ($prod as $p) {
                if (!is_array($p)) { continue; }
                $art = isset($p['Artikelnummer']) ? $p['Artikelnummer'] : '';
                $qty = isset($p['Menge']) ? intval($p['Menge']) : 0;
                if (!$art) { continue; }
                if (!isset($products[$art])) {
                    $products[$art] = array(
                        'bezeichnung' => isset($p['Bezeichnung']) ? $p['Bezeichnung'] : '',
                        'ordered'     => 0,
                        'preis'       => isset($p['Einzelpreis']) ? (float) $p['Einzelpreis'] : 0,
                        'delivered'   => 0,
                    );
                }
                $products[$art]['delivered'] += $qty;
                $total_delivered_qty        += $qty;
            }
        }
    }
    $total_ordered      = 0;
    $total_delivered    = 0;
    $total_warenwert_usd = 0;
    foreach ($products as $art => &$info) {
        $total_ordered   += $info['ordered'];
        $total_delivered += $info['delivered'];
        $total_warenwert_usd += $info['ordered'] * $info['preis'];
        $info['preis'] = $info['preis'] / $exchange_rate;
    }
    unset($info);
    $total_warenwert = $total_warenwert_usd / $exchange_rate;
    $stm_posts = get_posts(array(
        'post_type'  => 'steuermarken',
        'numberposts'=> -1,
        'meta_key'   => 'bestellung_id',
        'meta_value' => $pid,
    ));
    $total_stm = 0;
    foreach ($stm_posts as $s) {
        $w = get_post_meta($s->ID,'wert',true);
        $total_stm += (float)$w;
    }
    $lieferscheine = get_posts(array(
        'post_type'   => 'bestellungen',
        'post_parent' => $pid,
        'numberposts' => -1,
        'tax_query'   => array(array('taxonomy'=>'bestellart','field'=>'name','terms'=>'2900')),
    ));
    $popup_html = '';
    $exchange_popup  = '<div id="overlay-exchange" class="hoffmann-overlay"></div>';
    $exchange_popup .= '<div id="popup-exchange" class="hoffmann-popup">';
    $exchange_popup .= '<button class="popup-close">&times;</button>';
    $exchange_popup .= '<form class="exchange-form">'.
        wp_nonce_field('hoffmann_wechselkurs','nonce',true,false).
        '<input type="hidden" name="action" value="hoffmann_save_wechselkurs">'.
        '<input type="hidden" name="post_id" value="'.esc_attr($pid).'">'.
        '<p><label>Wechselkurs <input type="text" name="wechselkurs" value="'.esc_attr($exchange_rate).'"></label></p>'.
        '<p><button type="submit">Speichern</button></p>'.
        '</form>';
    $exchange_popup .= '</div>';
    $air_per_unit   = $total_ordered > 0 ? $total_air / $total_ordered : 0;
    $zoll_per_unit  = $total_ordered > 0 ? $total_zoll / $total_ordered : 0;
    $stm_per_unit   = $total_ordered > 0 ? $total_stm / $total_ordered : 0;
    $landed_total   = $total_warenwert + $total_air + $total_zoll + $total_stm;
    $landed_per_unit= $total_ordered > 0 ? $landed_total / $total_ordered : 0;
    $supplier = get_post_meta($pid, 'namezeile2', true);
    $eta      = get_post_meta($pid, 'belegdatum', true);
    $eta      = $eta ? date_i18n('Y-m-d', strtotime($eta)) : '';
    $betreff  = get_post_meta($pid, 'betreff', true);
    $deliv_percent = $total_ordered > 0 ? ($total_delivered / $total_ordered) * 100 : 0;
    ob_start();
    ?>
    <style>
    body { font-family: Arial, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #111827; }
    h1 { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
    .subtitle { font-size: 14px; color: #6b7280; margin-bottom: 20px; }
    .grid { display: grid; gap: 20px; }
    .grid-4 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    .card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .card h2 { font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 8px; }
    .card .value { font-size: 20px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { padding: 10px; font-size: 14px; text-align: left; }
    th { background: #f3f4f6; text-transform: uppercase; font-size: 12px; }
    tr:nth-child(even) { background: #f9fafb; }
    .status { display: inline-block; padding: 2px 6px; border-radius: 6px; font-size: 12px; }
    .status.offen { background: #e5e7eb; color: #374151; }
    .status.teil { background: #fef3c7; color: #92400e; }
    .status.voll { background: #dcfce7; color: #166534; }
    .chart-placeholder { height: 200px; display: flex; align-items: center; justify-content: center; color: #6b7280; border: 2px dashed #d1d5db; border-radius: 12px; }
    .hoffmann-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display:none; z-index:1000; }
    .hoffmann-popup { position: fixed; top:50%; left:50%; transform: translate(-50%,-50%); background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); display:none; z-index:1001; max-width:90%; width:400px; }
    .hoffmann-popup .popup-close { position:absolute; top:5px; right:10px; background:none; border:none; font-size:20px; cursor:pointer; }
    </style>
    <h1 style="font-size: 28px;">Bestellübersicht</h1>
    <div class="subtitle">Order <strong><?php echo esc_html($title); ?></strong> · Betreff <strong><?php echo esc_html($betreff); ?></strong> · Lieferant <strong><?php echo esc_html($supplier); ?></strong> · ETA <strong><?php echo esc_html($eta); ?></strong></div>
    <div class="grid grid-4">
        <div class="card"><h2>Warenwert (bestellt)</h2><div class="value"><?php echo esc_html(number_format_i18n($total_warenwert, 2)); ?> €<br><span class="muted">$<?php echo esc_html(number_format_i18n($total_warenwert_usd, 2)); ?></span></div></div>
        <div class="card"><h2>Aircargo gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_air, 2)); ?> €</div></div>
        <div class="card"><h2>Zollabwicklung gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_zoll, 2)); ?> €</div></div>
        <div class="card"><h2>Steuermarken gesamt</h2><div class="value"><?php echo esc_html(number_format_i18n($total_stm, 2)); ?>  €</div></div>
    </div>
    <div class="grid" style="grid-template-columns: 1fr 2fr; margin-top:20px;">
        <div class="card">
            <h2>Lieferstatus</h2>
            <div class="chart-placeholder"><canvas id="hoffmann-bestellung-pie"></canvas></div>
            <p style="font-size:14px; margin-top:10px;">Geliefert: <?php echo esc_html(number_format($deliv_percent,2,',','.')); ?>% (<?php echo esc_html(number_format_i18n($total_delivered)); ?> / <?php echo esc_html(number_format_i18n($total_ordered)); ?> Stk)</p>
        </div>
        <div class="card">
            <h2>Kosten-Zusammenfassung</h2>
<p>Bestellung Stückzahl: <strong><?php echo esc_html(number_format_i18n($total_ordered)); ?> Stück</strong></p>
<p>Warenwert gesamt: <strong><?php echo esc_html(number_format_i18n($total_warenwert, 2)); ?> €</strong> (<span>$<?php echo esc_html(number_format_i18n($total_warenwert_usd, 2)); ?></span>)</p>
<p>Aircargo gesamt: <strong><?php echo esc_html(number_format_i18n($total_air, 2)); ?> €</strong></p>
<p>Aircargo Stückpreis: <strong><?php echo esc_html(number_format_i18n($air_per_unit, 2)); ?> €</strong></p>
<p>Zollabwicklung gesamt: <strong><?php echo esc_html(number_format_i18n($total_zoll, 2)); ?> €</strong></p>
<p>Zollabwicklung Stückpreis: <strong><?php echo esc_html(number_format_i18n($zoll_per_unit, 2)); ?> €</strong></p>
<p>Steuermarken gesamt: <strong><?php echo esc_html(number_format_i18n($total_stm, 2)); ?> €</strong></p>
<p>Landed Cost gesamt: <strong><?php echo esc_html(number_format_i18n($landed_total, 2)); ?> €</strong></p>
<p>Stückpreis: <strong><?php echo esc_html(number_format_i18n($landed_per_unit, 2)); ?> €</strong></p>
<p>Wechselkurs: <strong><?php echo esc_html($exchange_rate); ?></strong> <a href="#" class="show-popup" data-popup="popup-exchange">ändern</a></p>

        </div>
    </div>
    <div class="grid" style="grid-template-columns: 1fr 1fr; margin-top:20px;">
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
                        $zoll_v  = get_post_meta($ls_id,'zoll_abwicklung_kosten',true);
                        $popup_html .= '<div id="overlay-'.$ls_id.'" class="hoffmann-overlay"></div>';
                        $popup_html .= '<div id="popup-'.$ls_id.'" class="hoffmann-popup">';
                        $popup_html .= '<button class="popup-close">&times;</button>';
                        $popup_html .= '<form class="lieferschein-form">'.
                            wp_nonce_field('hoffmann_lieferschein_costs','nonce',true,false).
                            '<input type="hidden" name="action" value="hoffmann_save_lieferschein_costs">'.
                            '<input type="hidden" name="post_id" value="'.esc_attr($ls_id).'">'.
                            '<p><label>Aircargo <input type="text" name="air_cargo_kosten" value="'.esc_attr($air_v).'"></label></p>'.
                            '<p><label>Zollabwicklung <input type="text" name="zoll_abwicklung_kosten" value="'.esc_attr($zoll_v).'"></label></p>'.
                            '<p><button type="submit">Speichern</button></p>'.
                            '</form>';
                        $popup_html .= '</div>';
                    ?>
                        <tr>
                            <td><a href="#" class="show-popup" data-popup="popup-<?php echo esc_attr($ls_id); ?>"><?php echo esc_html(get_the_title($ls)); ?></a><?php if($lf_no) echo '<br>'.esc_html($lf_no); ?></td>
                            <td><?php echo esc_html(date_i18n('Y-m-d', strtotime($ls_date))); ?></td>
                            <td><?php echo esc_html(hoffmann_format_currency($zoll_v)); ?> €</td>
                            <td><?php echo esc_html(hoffmann_format_currency($air_v)); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php echo $popup_html . $exchange_popup; ?>
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
                        $stm_qty   = number_format_i18n(get_post_meta($stm->ID,'stueckzahl', true));
                    ?>
                        <tr>
                            <td><?php echo esc_html($stm_title); ?></td>
                            <td><?php echo esc_html($stm_date); ?></td>
                            <td><?php echo esc_html($stm_wert); ?> €</td>
                            <td><?php echo esc_html($stm_qty); ?></td>
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
                    <th>EK €/Stk</th>
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
                    <td><?php echo esc_html(hoffmann_format_currency($info['preis'])); ?></td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        var ctx=document.getElementById('hoffmann-bestellung-pie').getContext('2d');
        new Chart(ctx,{type:'pie',data:{labels:['Geliefert','Offen'],datasets:[{data:[<?php echo (int)$total_delivered; ?>,<?php echo (int)max(0,$total_ordered-$total_delivered); ?>],backgroundColor:['#4caf50','#ddd']}]},options:{responsive:true}});
    });
    document.addEventListener('click',function(e){
        if(e.target.matches('.show-popup')){
            e.preventDefault();
            var id=e.target.getAttribute('data-popup');
            var num=id.replace('popup-','');
            document.getElementById('overlay-'+num).style.display='block';
            document.getElementById(id).style.display='block';
        }
        if(e.target.matches('.popup-close')||e.target.classList.contains('hoffmann-overlay')){
            var popup=e.target.classList.contains('hoffmann-overlay')?document.getElementById('popup-'+e.target.id.replace('overlay-','')):e.target.closest('.hoffmann-popup');
            var num=popup.id.replace('popup-','');
            document.getElementById('overlay-'+num).style.display='none';
            popup.style.display='none';
        }
    });
    document.addEventListener('submit',function(e){
        if(e.target.matches('.lieferschein-form')||e.target.matches('.exchange-form')){
            e.preventDefault();
            var form=e.target;
            var data=new FormData(form);
            fetch('<?php echo admin_url('admin-ajax.php'); ?>',{method:'POST',body:data}).then(r=>r.json()).then(function(resp){if(resp.success){location.reload();}});
        }
    });
    </script>
    <?php
    $html = ob_get_clean();
    return $content . $html;
}

add_action('admin_menu','hoffmann_bestellungen_dashboard_menu');
function hoffmann_bestellungen_dashboard_menu(){
    add_submenu_page('edit.php?post_type=bestellungen','Dashboard','Dashboard','read','bestellungen-dashboard','hoffmann_bestellungen_dashboard_page');
}

function hoffmann_bestellungen_dashboard_page(){
    $orders = get_posts(array('post_type'=>'bestellungen','numberposts'=>-1));
    $total_netto = $total_air = $total_zoll = 0;
    foreach($orders as $o){
        $net = hoffmann_to_float(get_post_meta($o->ID,'betragnetto',true));
        $total_netto += $net;
        $air = hoffmann_to_float(get_post_meta($o->ID,'air_cargo_kosten',true));
        $total_air += $air;
        $zoll = hoffmann_to_float(get_post_meta($o->ID,'zoll_abwicklung_kosten',true));
        $total_zoll += $zoll;
    }
    $stm_posts = get_posts(array('post_type'=>'steuermarken','numberposts'=>-1));
    $total_stm = 0;
    foreach($stm_posts as $s){
        $w = get_post_meta($s->ID,'wert',true);
        $total_stm += (float)$w;
    }
    ?>
    <div class="wrap"><h1>Bestellungen Dashboard</h1>
    <canvas id="bestellungenChart" width="400" height="200"></canvas></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('bestellungenChart').getContext('2d');
    new Chart(ctx,{type:'bar',data:{labels:['Netto','Aircargo','Zoll','Steuermarken'],datasets:[{label:'Kosten',data:[<?php echo $total_netto; ?>,<?php echo $total_air; ?>,<?php echo $total_zoll; ?>,<?php echo $total_stm; ?>],backgroundColor:['#4e79a7','#f28e2b','#e15759','#76b7b2']}]} });
    </script>
    <?php
}
