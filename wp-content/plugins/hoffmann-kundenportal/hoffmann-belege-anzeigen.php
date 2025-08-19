<?php
/**
 * Plugin Name: Hoffmann Belege Anzeigen
 * Description: Zeigt eingeloggten Kunden seine Belege filterbar nach Belegart, mit Sortierung nach Datum und Nummer. Erzeugt für jeden Beleg ein eigenes Popup mit halbtransparentem Hintergrund.
 * Version: 2.6
 * Author: Max Florian Krauss
 */

// Übersicht-Shortcode: [hoffmann_belege_anzeigen]
add_shortcode('hoffmann_belege_anzeigen','hoffmann_belege_anzeigen_shortcode');
function hoffmann_belege_anzeigen_shortcode(){
    if(!is_user_logged_in()){
        return '<p>Bitte <a href="'.esc_url(wp_login_url()).'">loggen Sie sich ein</a>, um Ihre Belege zu sehen.</p>';
    }
    $customer = wp_get_current_user()->user_login;

    // Filter & Sort
    $filter  = isset($_GET['belegart']) ? sanitize_text_field($_GET['belegart']) : '__all';
    $orderby = (isset($_GET['orderby']) && in_array($_GET['orderby'], ['date','nummer'])) ? $_GET['orderby'] : 'date';
    $order   = (isset($_GET['order']) && strtolower($_GET['order'])==='asc') ? 'asc' : 'desc';

    // Dropdown
    $terms = get_terms(['taxonomy'=>'belegart','hide_empty'=>true]);
    $output = '<form method="get" class="beleg-filter-form">';
    $output .= '<select name="belegart" onchange="this.form.submit()">';
    $output .= '<option value="__all"'.selected($filter,'__all',false).'>Alle Belegarten</option>';
    foreach($terms as $t){
        $slug = sanitize_title($t->slug);
        $output .= '<option value="'.esc_attr($slug).'"'.selected($filter,$slug,false).'>'.esc_html($t->description).'</option>';
    }
    $output .= '</select></form>';

    // Sort links
    $base = strtok($_SERVER['REQUEST_URI'],'?');
    $dord = ($orderby==='date' && $order==='asc') ? 'desc' : 'asc';
    $nord = ($orderby==='nummer' && $order==='asc') ? 'desc' : 'asc';
    $link_date = esc_url(add_query_arg(['orderby'=>'date','order'=>$dord,'belegart'=>$filter],$base));
    $link_nr   = esc_url(add_query_arg(['orderby'=>'nummer','order'=>$nord,'belegart'=>$filter],$base));

    // Query
    $args = [
        'post_type'=>'belege',
        'posts_per_page'=>-1,
        'meta_query'=>[['key'=>'kundennummer','value'=>$customer,'compare'=>'=']],
        'orderby'=>($orderby==='date'?'meta_value':'title'),
        'meta_key'=>($orderby==='date'?'belegdatum':''),
        'order'=>$order,
    ];
    if($filter!=='__all'){
        $args['tax_query']=[['taxonomy'=>'belegart','field'=>'slug','terms'=>$filter]];
    }
    $q = new WP_Query($args);

    // Table and popups
    $output .= '<table class="hoffmann-belege" style="width:100%;border-collapse:collapse;"><thead><tr>'
             . '<th><a href="'.$link_date.'">Datum</a></th>'
             . '<th><a href="'.$link_nr.'">Nummer</a></th>'
             . '<th>Status</th><th>Preis</th><th>Aktion</th>'
             . '</tr></thead><tbody>';
    $popups = '';
    if($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $pid   = get_the_ID();
            $date  = date_i18n('d.m.Y', strtotime(get_post_meta($pid,'belegdatum',true)));
            $nr    = get_the_title();
            $price = number_format_i18n((float)get_post_meta($pid,'betragnetto',true),2);
            $st    = get_post_meta($pid,'belegstatus',true);
            switch($st){
                case '3': $label = 'Offen'; break;
                case '5': $label = 'Abgeschlossen'; break;
                default:  $label = esc_html($st); break;
            }
            $output .= '<tr>'
                     . '<td>'.esc_html($date).'</td>'
                     . '<td>'.esc_html($nr).'</td>'
                     . '<td>'.esc_html($label).'</td>'
                     . '<td>'.esc_html($price).'</td>'
                     . '<td><button class="show-popup" data-popup="popup-'.$pid.'">Beleg anzeigen</button></td>'
                     . '</tr>';

            // Overlay + Popup HTML
            $detail_html = do_shortcode('[hoffmann_beleg_details id="'.$pid.'"]');
            $popups .= '<div id="overlay-'.$pid.'" class="hoffmann-overlay" style="display:none;position:fixed;top:0;left:0;'
                     . 'width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;"></div>';
            $popups .= '<div id="popup-'.$pid.'" class="hoffmann-popup" style="display:none;position:fixed;top:50%;left:50%;'
                     . 'transform:translate(-50%, -50%);background:#fff;padding:20px;box-shadow:0 0 10px rgba(0,0,0,0.5);'
                     . 'z-index:9999;max-width:90%;max-height:80%;overflow:auto;">'
                     . '<button class="popup-close" style="float:right;">&times;</button>'
                     . $detail_html .'</div>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<tr><td colspan="5">Keine Belege gefunden.</td></tr>';
    }
    $output .= '</tbody></table>' . $popups;

    // JS: Open/close
    $output .= <<<JS
<script>
document.addEventListener('click',function(e){
    if(e.target.matches('.show-popup')){
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
</script>
JS;

    return $output;
}

// Detail-Shortcode: [hoffmann_beleg_details id="123"]
add_shortcode('hoffmann_beleg_details','hoffmann_beleg_details_shortcode');
function hoffmann_beleg_details_shortcode($atts){
    $pid = intval($atts['id'] ?? 0);
    if(!$pid) return '<p>Ungültige Beleg-ID.</p>';
    $terms = wp_get_post_terms($pid,'belegart');
    $ba    = (!is_wp_error($terms)&&$terms) ? esc_html($terms[0]->name) : '';
    $nr    = esc_html(get_the_title($pid));
    $dt    = date_i18n('d.m.Y H:i', strtotime(get_post_meta($pid,'belegdatum',true)));
    $rows  = function_exists('get_field') ? get_field('produkte',$pid) : [];
    $html  = "<h3>{$ba} {$nr}</h3><p><strong>Datum:</strong> {$dt}</p>";
    $html .= "<table style='width:100%;border-collapse:collapse;'><tr><th>Beschreibung</th><th>Menge</th><th>Preis</th></tr>";
    $net   = 0;
    foreach($rows ?: [] as $r){
        $html .= "<tr><td>".esc_html($r['artikelbeschreibung'])."</td>";
        $html .= "<td>".intval($r['menge'])."</td>";
        $html .= "<td>".esc_html($r['preis'])."</td></tr>";
        $net += floatval(str_replace(',','.',$r['preis'])) * intval($r['menge']);
    }
    $html .= '</table>';
    $net_f = number_format_i18n($net,2);
    $mwst  = $net * 0.19;
    $mwst_f= number_format_i18n($mwst,2);
    $br_f  = number_format_i18n($net + $mwst,2);
    $html .= "<p style='text-align:right;'><strong>Netto:</strong> {$net_f}</p>";
    $html .= "<p style='text-align:right;'><strong>MwSt (19%):</strong> {$mwst_f}</p>";
    $html .= "<p style='text-align:right;'><strong>Brutto:</strong> {$br_f}</p>";
    return $html;
}
