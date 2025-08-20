<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('hoffmann_add_produkte_metabox')) {
    function hoffmann_add_produkte_metabox() {
        add_meta_box(
            'hoffmann_produkte_metabox',
            __('Produkte'),
            'hoffmann_render_produkte_metabox',
            ['belege', 'bestellungen'],
            'normal',
            'default'
        );
    }
    add_action('add_meta_boxes', 'hoffmann_add_produkte_metabox');
}

if (!function_exists('hoffmann_render_produkte_metabox')) {
    function hoffmann_render_produkte_metabox($post) {
        if (!function_exists('get_field')) {
            echo '<p>' . esc_html__('Advanced Custom Fields erforderlich.', 'hoffmann') . '</p>';
            return;
        }
        $rows = get_field('produkte', $post->ID);
        if (empty($rows)) {
            echo '<p>' . esc_html__('Keine Produkte vorhanden.', 'hoffmann') . '</p>';
            return;
        }
        echo '<table class="widefat fixed"><thead><tr>';
        echo '<th>' . esc_html__('Artikelnummer', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Beschreibung', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Menge', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Preis', 'hoffmann') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $nummer = isset($row['artikelnummer']) ? $row['artikelnummer'] : '';
            $beschreibung = isset($row['artikelbeschreibung']) ? $row['artikelbeschreibung'] : '';
            $menge = isset($row['menge']) ? $row['menge'] : '';
            $preis = isset($row['preis']) ? $row['preis'] : '';
            echo '<tr>';
            echo '<td>' . esc_html($nummer) . '</td>';
            echo '<td>' . esc_html($beschreibung) . '</td>';
            echo '<td>' . esc_html($menge) . '</td>';
            echo '<td>' . esc_html($preis) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
?>
