<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('hoffmann_format_currency')) {
    function hoffmann_format_currency($value) {
        if ($value === '' || $value === null) {
            return '';
        }
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return number_format((float) $value, 2, ',', '.');
    }
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
        $data = get_post_meta($post->ID, 'produkte', true);
        if (!$data) {
            echo '<p>' . esc_html__('Keine Produkte vorhanden.', 'hoffmann') . '</p>';
            return;
        }
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        if (empty($data)) {
            echo '<p>' . esc_html__('Keine Produkte vorhanden.', 'hoffmann') . '</p>';
            return;
        }

        echo '<table class="widefat fixed"><thead><tr>';
        echo '<th>' . esc_html__('Artikelnummer', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Beschreibung', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Menge', 'hoffmann') . '</th>';
        echo '<th>' . esc_html__('Preis', 'hoffmann') . '</th>';
        echo '</tr></thead><tbody>';
        echo hoffmann_render_produkte_rows($data);
        echo '</tbody></table>';
    }
}

if (!function_exists('hoffmann_render_produkte_rows')) {
    function hoffmann_render_produkte_rows($items, $level = 0) {
        $html = '';
        if (!is_array($items)) {
            return $html;
        }
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nummer = isset($item['Artikelnummer']) ? $item['Artikelnummer'] : '';
            $beschreibung = isset($item['Bezeichnung']) ? $item['Bezeichnung'] : '';
            $menge = isset($item['Menge']) ? $item['Menge'] : '';
            $preis = isset($item['Einzelpreis']) ? $item['Einzelpreis'] : '';
            $pad = str_repeat('&nbsp;', $level * 4);
            $html .= '<tr>';
            $html .= '<td>' . esc_html($nummer) . '</td>';
            $html .= '<td>' . $pad . esc_html($beschreibung) . '</td>';
            $html .= '<td>' . esc_html($menge) . '</td>';
            $html .= '<td>' . esc_html(hoffmann_format_currency($preis)) . '</td>';
            $html .= '</tr>';
            foreach ($item as $key => $val) {
                if (is_array($val)) {
                    $html .= hoffmann_render_produkte_rows($val, $level + 1);
                }
            }
        }
        return $html;
    }
}
?>
