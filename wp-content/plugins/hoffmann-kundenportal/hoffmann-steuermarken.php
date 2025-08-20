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
    $belegnummer = get_post_meta($post->ID, 'belegnummer', true);
    $wert        = get_post_meta($post->ID, 'wert', true);
    $stueckzahl  = get_post_meta($post->ID, 'stueckzahl', true);
    $bestelldatum= get_post_meta($post->ID, 'bestelldatum', true);
    $order_id    = get_post_meta($post->ID, 'bestellung_id', true);
    $orders      = get_posts(array('post_type'=>'bestellungen', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC'));
    ?>
    <p><label>Steuermarken-Belegnummer<br><input type="text" name="belegnummer" value="<?php echo esc_attr($belegnummer); ?>"></label></p>
    <p><label>Steuermarken-Wert<br><input type="text" name="wert" value="<?php echo esc_attr($wert); ?>"></label></p>
    <p><label>Steuermarken-Stückzahl<br><input type="number" name="stueckzahl" value="<?php echo esc_attr($stueckzahl); ?>"></label></p>
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
    update_post_meta($post_id, 'belegnummer', sanitize_text_field($_POST['belegnummer'] ?? ''));
    update_post_meta($post_id, 'wert', sanitize_text_field($_POST['wert'] ?? ''));
    update_post_meta($post_id, 'stueckzahl', intval($_POST['stueckzahl'] ?? 0));
    update_post_meta($post_id, 'bestelldatum', sanitize_text_field($_POST['bestelldatum'] ?? ''));
    update_post_meta($post_id, 'bestellung_id', intval($_POST['bestellung_id'] ?? 0));
}
add_action('save_post_steuermarken', 'hoffmann_steuermarken_save_meta');

?>
