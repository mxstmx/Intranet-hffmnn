<?php
/**
 * Plugin Name: Hoffmann Bestand
 * Description: Zeigt Produkte mit Verfügbarkeit filterbar nach Warengruppen ohne Bestellfunktion.
 * Version: main-v1.0.1
 * Author: Max Florian Krauss
 */

if (!defined('ABSPATH')) {
    exit;
}


// Shortcode für Bestandsanzeige
add_shortcode('hoffmann_bestand', 'hoffmann_render_bestand');
function hoffmann_render_bestand() {
    ob_start();
    // Taxonomy-Terms laden
    $terms = get_terms([ 'taxonomy' => 'warengruppe', 'hide_empty' => false ]);
    $default = '__all'; // Standardfilter: Alle Warengruppen
    $filter = isset($_GET['gruppe']) ? sanitize_title($_GET['gruppe']) : $default;

    // Buttons für Warengruppen
    echo '<div class="hoffmann-bestand-filter">';
    echo '<button class="hoffmann-filter-item" onclick="location.href=\'' . esc_url(remove_query_arg('gruppe')) . '\'" ' . ($filter === '__all' ? 'aria-pressed="true"' : 'aria-pressed="false"') . '>Alle</button>';

foreach ($terms as $term) {
        $slug = sanitize_title($term->slug);
        $pressed = ($slug === $filter) ? 'aria-pressed="true"' : 'aria-pressed="false"';
        $url = esc_url(add_query_arg('gruppe', $slug));
        echo '<button class="hoffmann-filter-item" onclick="location.href=\''.$url.'\'" '.$pressed.'>' . esc_html($term->name) . '</button>';
    }
    echo '</div>';

    // Tabelle ausgeben
    echo '<table class="hoffmann-bestand-tabelle"><thead><tr>';
    echo '<th>Produktname</th><th>Verfügbarkeit</th>';
    echo '</tr></thead><tbody>';

    $products = get_posts([ 'post_type' => 'produkte', 'posts_per_page' => -1, 'orderby'=>'title','order'=>'ASC' ]);
    foreach ($products as $p) {
        // Verfügbarkeit berechnen
        $stock = (int)get_post_meta($p->ID,'bestand',true);
        $reserved = (int)get_post_meta($p->ID,'reserviert',true);
        $available = max(0,$stock - $reserved);
        if ($available <= 0) continue;
        // Warengruppe prüfen
        $grs = wp_get_post_terms($p->ID,'warengruppe');
        if (is_wp_error($grs) || empty($grs)) {
            $slug = '';
            $gr_name = 'Unbekannt';
        } else {
            $slug = sanitize_title($grs[0]->slug);
            $gr_name = $grs[0]->name;
        }
        if ($filter !== '__all' && $slug !== $filter) continue;
        echo '<tr data-gruppe="'.esc_attr($gr_name).'">';
        echo '<td>'.esc_html($p->post_title).'</td>';
		$anzeige = $available > 5000 ? '> 5.000' : number_format_i18n($available);
		echo '<td>' . esc_html($anzeige) . '</td></tr>';

    }

    echo '</tbody></table>';

    echo '<div style="margin-top:20px;"><button id="bestand-download-pdf">PDF herunterladen</button></div>';
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const downloadBtn = document.getElementById('bestand-download-pdf');
        if (!downloadBtn) return;

        downloadBtn.addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const gruppen = {};
            const rows = document.querySelectorAll('.hoffmann-bestand-tabelle tbody tr');

            rows.forEach(row => {
                const name = row.children[0].textContent.trim();
                const menge = row.children[1].textContent.trim();
                const gruppe = row.getAttribute('data-gruppe') || 'Alle';
                if (!gruppen[gruppe]) gruppen[gruppe] = [];
                gruppen[gruppe].push([name, menge]);
            });

            let y = 10;
            Object.keys(gruppen).forEach(gruppe => {
                doc.text(`Warengruppe: ${gruppe}`, 14, y);
                y += 6;
                let total = 0;
                const body = gruppen[gruppe].map(([name, menge]) => {
                    const mengeInt = parseInt(menge.replace(/\./g, ''));
                    total += mengeInt;
                    return [name, menge];
                });
                doc.autoTable({
                    head: [["Produktname", "Verfügbarkeit"]],
                    body,
                    startY: y,
                    styles: { halign: 'left' },
                    theme: 'striped'
                });
                y = doc.lastAutoTable.finalY + 4;
                // doc.text(`Gesamtbestand: ${total.toLocaleString('de-DE')}`, 195, y, { align: 'right' });
                y += 10;
            });

            const d = new Date();
            const timestamp = `${d.getDate()}-${(d.getMonth()+1)}-${d.getFullYear()}`;
            doc.save(`Bestand_${timestamp}.pdf`);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
