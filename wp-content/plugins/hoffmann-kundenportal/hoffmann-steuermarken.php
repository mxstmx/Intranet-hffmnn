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
    $stueckzahl  = (int)get_post_meta($post->ID, 'stueckzahl', true);
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
    $wert_disp = $wert_calc;
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
    $stueckzahl = (int) $st_raw;
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

?>
