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
    $air_val  = get_post_meta($post->ID,'air_cargo_kosten',true);
    $zoll_val = get_post_meta($post->ID,'zoll_abwicklung_kosten',true);
    $air  = esc_attr(hoffmann_format_currency($air_val));
    $zoll = esc_attr(hoffmann_format_currency($zoll_val));
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
        update_post_meta($post_id, 'air_cargo_kosten', sanitize_text_field($_POST['air_cargo_kosten']));
    }
    if (isset($_POST['zoll_abwicklung_kosten'])) {
        update_post_meta($post_id, 'zoll_abwicklung_kosten', sanitize_text_field($_POST['zoll_abwicklung_kosten']));
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
        $html = '<table style="width:100%;border-collapse:collapse;"><tbody>';
        foreach ($fields as $key => $label) {
            $val = get_post_meta($pid, $key, true);
            if ($key === 'betragnetto') {
                $val = hoffmann_format_currency($val);
            }
            $html .= '<tr><th style="text-align:left;">'.esc_html($label).'</th><td>'.esc_html($val).'</td></tr>';
        }
        $html .= '</tbody></table>';
        $prod = get_post_meta($pid, 'produkte', true);
        if (!is_array($prod)) {
            $prod = json_decode($prod, true);
        }
        if ($prod) {
            $html .= '<h4>'.esc_html__('Produkte', 'hoffmann').'</h4>';
            $html .= '<table style="width:100%;border-collapse:collapse;"><thead><tr><th>'.esc_html__('Artikelnummer', 'hoffmann').'</th><th>'.esc_html__('Beschreibung', 'hoffmann').'</th><th>'.esc_html__('Menge', 'hoffmann').'</th><th>'.esc_html__('Preis', 'hoffmann').'</th></tr></thead><tbody>';
            $html .= hoffmann_render_produkte_rows($prod);
            $html .= '</tbody></table>';
        }
        $air  = esc_attr(hoffmann_format_currency(get_post_meta($pid, 'air_cargo_kosten', true)));
        $zoll = esc_attr(hoffmann_format_currency(get_post_meta($pid, 'zoll_abwicklung_kosten', true)));
        $html .= '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        $html .= wp_nonce_field('hoffmann_save_order_costs_'.$pid, '_wpnonce', true, false);
        $html .= '<input type="hidden" name="action" value="hoffmann_save_order_costs">';
        $html .= '<input type="hidden" name="post_id" value="'.esc_attr($pid).'">';
        $html .= '<p><label>Aircargo <input type="text" name="air_cargo_kosten" value="'.$air.'"></label></p>';
        $html .= '<p><label>Zoll Kosten <input type="text" name="zoll_abwicklung_kosten" value="'.$zoll.'"></label></p>';
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
        $air  = isset($_POST['air_cargo_kosten']) ? sanitize_text_field($_POST['air_cargo_kosten']) : '';
        $zoll = isset($_POST['zoll_abwicklung_kosten']) ? sanitize_text_field($_POST['zoll_abwicklung_kosten']) : '';
        update_post_meta($post_id, 'air_cargo_kosten', $air);
        update_post_meta($post_id, 'zoll_abwicklung_kosten', $zoll);
        wp_redirect(wp_get_referer());
        exit;
    }
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

// Shortcode zur Ausgabe der Hauptbestellungen mit Unterbestellungen
function hoffmann_bestellungen_shortcode() {
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

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return '';
    }

    ob_start();
    $popups = '';
    ?>
    <table id="hoffmann-bestellungen-table">
        <thead>
            <tr>
                <th><?php echo esc_html__('Bestellnummer', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Belegdatum', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Betreff', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Gesamtstückzahl', 'hoffmann'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <?php $pid = get_the_ID(); ?>
            <?php $datum = get_post_meta($pid, 'belegdatum', true); ?>
            <?php $betreff = get_post_meta($pid, 'betreff', true); ?>
            <?php $menge = hoffmann_sum_produkt_mengen($pid); ?>
            <tr class="hoffmann-parent" data-id="<?php echo esc_attr($pid); ?>">
                <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                <td><?php echo esc_html($datum ? date_i18n('d.m.Y', strtotime($datum)) : ''); ?></td>
                <td><?php echo esc_html($betreff); ?></td>
                <td><?php echo esc_html($menge); ?></td>
            </tr>
            <?php
            $children = get_children(array(
                'post_type'   => 'bestellungen',
                'post_parent' => $pid,
                'orderby'     => 'title',
                'order'       => 'ASC',
            ));
            if ($children) {
                foreach ($children as $child) {
                    $cid = $child->ID;
                    $c_datum = get_post_meta($cid, 'belegdatum', true);
                    $c_betreff = get_post_meta($cid, 'betreff', true);
                    $c_menge = hoffmann_sum_produkt_mengen($cid);
                    echo '<tr class="hoffmann-child" data-parent="'.esc_attr($pid).'">';
                    echo '<td><a href="#" class="show-popup" data-popup="popup-'.$cid.'">'.esc_html($child->post_title).'</a></td>';
                    echo '<td>'.esc_html($c_datum ? date_i18n('d.m.Y', strtotime($c_datum)) : '').'</td>';
                    echo '<td>'.esc_html($c_betreff).'</td>';
                    echo '<td>'.esc_html($c_menge).'</td>';
                    echo '</tr>';
                    $detail_html = hoffmann_bestellung_detail_html($cid);
                    $popups .= '<div id="overlay-'.$cid.'" class="hoffmann-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;"></div>';
                    $popups .= '<div id="popup-'.$cid.'" class="hoffmann-popup" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#fff;padding:20px;box-shadow:0 0 10px rgba(0,0,0,0.5);z-index:9999;max-width:90%;max-height:80%;overflow:auto;">';
                    $popups .= '<button class="popup-close" style="float:right;">&times;</button>'.$detail_html.'</div>';
                }
            }
            ?>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php echo $popups; ?>
    <style>
    #hoffmann-bestellungen-table {width:100%;border-collapse:collapse;}
    #hoffmann-bestellungen-table th,#hoffmann-bestellungen-table td {border:1px solid #ccc;padding:4px;text-align:left;}
    #hoffmann-bestellungen-table thead th {cursor:pointer;background:#f0f0f0;}
    #hoffmann-bestellungen-table tr.hoffmann-child {display:none;background:#f9f9f9;}
    #hoffmann-bestellungen-table tr.hoffmann-child.visible {display:table-row;}
    #hoffmann-bestellungen-table tr.hoffmann-child td:first-child {padding-left:50px;}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('#hoffmann-bestellungen-table tbody tr.hoffmann-parent').forEach(function(row){
            row.addEventListener('click',function(){
                var id=this.dataset.id;
                document.querySelectorAll('#hoffmann-bestellungen-table tbody tr.hoffmann-child[data-parent="'+id+'"]').forEach(function(ch){
                    ch.classList.toggle('visible');
                });
            });
        });
        document.querySelectorAll('#hoffmann-bestellungen-table thead th').forEach(function(th,idx){
            th.addEventListener('click',function(){ sortTable(idx); });
        });
        function sortTable(n){
            var table=document.getElementById('hoffmann-bestellungen-table');
            var tbody=table.tBodies[0];
            var rows=Array.from(tbody.querySelectorAll('tr.hoffmann-parent'));
            var dir=table.getAttribute('data-sort-dir')==='asc'?'desc':'asc';
            rows.sort(function(a,b){
                var valA=a.children[n].innerText.toLowerCase();
                var valB=b.children[n].innerText.toLowerCase();
                if(!isNaN(valA) && !isNaN(valB)){ valA=parseFloat(valA); valB=parseFloat(valB); }
                if(valA<valB) return dir==='asc'?-1:1;
                if(valA>valB) return dir==='asc'?1:-1;
                return 0;
            });
            rows.forEach(function(row){
                var id=row.dataset.id;
                var children=Array.from(tbody.querySelectorAll('tr.hoffmann-child[data-parent="'+id+'"]'));
                tbody.appendChild(row);
                children.forEach(function(c){tbody.appendChild(c);});
            });
            table.setAttribute('data-sort-dir',dir);
        }
        document.addEventListener('click',function(e){
            if(e.target.matches('.show-popup')){
                e.preventDefault();
                var id = e.target.getAttribute('data-popup').replace('popup-','');
                document.getElementById('overlay-'+id).style.display = 'block';
                document.getElementById('popup-'+id).style.display   = 'block';
            }
            if(e.target.matches('.popup-close') || e.target.classList.contains('hoffmann-overlay')){
                var popup = e.target.closest('.hoffmann-popup');
                var id = popup ? popup.id.replace('popup-','') : e.target.id.replace('overlay-','');
                document.getElementById('overlay-'+id).style.display = 'none';
                document.getElementById('popup-'+id).style.display   = 'none';
            }
        });
    });
    </script>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('bestellungen_uebersicht','hoffmann_bestellungen_shortcode');

add_filter('the_content','hoffmann_bestellung_single_content');
function hoffmann_bestellung_single_content($content){
    if (!is_singular('bestellungen') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    global $post;
    if (!has_term('2200','bestellart',$post)) {
        return $content;
    }
    $pid = $post->ID;
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
            'ordered' => isset($item['Menge']) ? intval($item['Menge']) : 0,
            'preis' => isset($item['Einzelpreis']) ? (float) str_replace(',', '.', str_replace('.', '', $item['Einzelpreis'])) : 0,
            'delivered' => 0,
        );
    }
    $children = get_posts(array(
        'post_type' => 'bestellungen',
        'post_parent' => $pid,
        'posts_per_page' => -1,
        'tax_query' => array(array('taxonomy'=>'bestellart','field'=>'name','terms'=>'2900')),
    ));
    $total_qty = 0;
    $total_air = 0;
    $total_zoll = 0;
    foreach ($children as $child) {
        $cid = $child->ID;
        $air  = (float) str_replace(',', '.', str_replace('.', '', get_post_meta($cid,'air_cargo_kosten',true)));
        $zoll = (float) str_replace(',', '.', str_replace('.', '', get_post_meta($cid,'zoll_abwicklung_kosten',true)));
        $total_air  += $air;
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
                        'ordered' => 0,
                        'preis' => isset($p['Einzelpreis']) ? (float) str_replace(',', '.', str_replace('.', '', $p['Einzelpreis'])) : 0,
                        'delivered' => 0,
                    );
                }
                $products[$art]['delivered'] += $qty;
                $total_qty += $qty;
            }
        }
    }
    $air_per_unit  = $total_qty > 0 ? $total_air / $total_qty : 0;
    $zoll_per_unit = $total_qty > 0 ? $total_zoll / $total_qty : 0;
    ob_start();
    ?>
    <h2><?php echo esc_html__('Auswertung', 'hoffmann'); ?></h2>
    <table class="hoffmann-auswertung">
        <thead>
            <tr>
                <th><?php echo esc_html__('Artikelnummer', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Bezeichnung', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Bestellt', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Geliefert', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Rest', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Status', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Preis', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Aircargo/Stk', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Zoll/Stk', 'hoffmann'); ?></th>
                <th><?php echo esc_html__('Gesamt/Stk', 'hoffmann'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $art => $info):
                $rest = $info['ordered'] - $info['delivered'];
                $percent = $info['ordered'] > 0 ? ($info['delivered'] / $info['ordered']) * 100 : 0;
                $total_unit = $info['preis'] + $air_per_unit + $zoll_per_unit;
            ?>
            <tr>
                <td><?php echo esc_html($art); ?></td>
                <td><?php echo esc_html($info['bezeichnung']); ?></td>
                <td><?php echo esc_html($info['ordered']); ?></td>
                <td><?php echo esc_html($info['delivered']); ?></td>
                <td><?php echo esc_html($rest); ?></td>
                <td><div class="hoffmann-bar"><div style="width:<?php echo esc_attr(intval($percent)); ?>%"></div></div> <?php echo esc_html(number_format($percent,2,',','.')); ?>%</td>
                <td><?php echo esc_html(hoffmann_format_currency($info['preis'])); ?></td>
                <td><?php echo esc_html(hoffmann_format_currency($air_per_unit)); ?></td>
                <td><?php echo esc_html(hoffmann_format_currency($zoll_per_unit)); ?></td>
                <td><?php echo esc_html(hoffmann_format_currency($total_unit)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
    .hoffmann-auswertung{width:100%;border-collapse:collapse;}
    .hoffmann-auswertung th,.hoffmann-auswertung td{border:1px solid #ccc;padding:4px;text-align:left;}
    .hoffmann-bar{width:100px;background:#ddd;height:10px;position:relative;}
    .hoffmann-bar div{background:#4caf50;height:10px;}
    </style>
    <?php
    $html = ob_get_clean();
    return $content . $html;
}
