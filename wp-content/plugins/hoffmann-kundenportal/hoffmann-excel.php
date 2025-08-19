<?php
/*
Plugin Name: Hoffmann-Excel
Description: Exportiert die Artikelverfügbarkeit als PDF.
Version: 1.0
Author: Hoffmann Handel & Dienstleistungen GmbH & Co. KG
*/

require_once 'lib/fpdf.php';

// Funktion zur Umwandlung von Text für PDF-Ausgabe
function convertText($text) {
    // Wandelt den Text zu ISO-8859-1 und ersetzt unerwünschte Zeichen
    $text = mb_convert_encoding(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $text = str_replace("?", "-", $text); // Ersetzt Fragezeichen durch Bindestrich
    return $text;
}

// Hauptfunktion für den PDF-Export
function hoffmann_export_pdf() {
    // Neue FPDF-Instanz erstellen
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 8); // Schriftgröße auf 8 setzen

    // Titel
    $pdf->Cell(0, 10, convertText('Artikelverfügbarkeit'), 0, 1, 'C');

    // Holen aller Warengruppen-Taxonomien
    $warengruppen_terms = get_terms([
        'taxonomy' => 'warengruppe',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    // Tabellenkopf
    $pdf->Cell(80, 8, convertText('Artikelbezeichnung'), 1, 0, 'C');
    $pdf->Cell(50, 8, convertText('Artikelnummer'), 1, 0, 'C'); // Breitere Spalte für Artikelnummer
    $pdf->Cell(30, 8, convertText('Verfügbarkeit'), 1, 1, 'C'); // Kleinere Spalte für Verfügbarkeit

    // Daten in PDF einfügen
    foreach ($warengruppen_terms as $term) {
        // Abfrage für den Post-Type "Produkte" innerhalb der aktuellen Warengruppe
        $args = array(
            'post_type' => 'produkte',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'warengruppe',
                    'field' => 'slug',
                    'terms' => $term->slug,
                ),
            ),
        );
        $produkte_query = new WP_Query($args);

        if ($produkte_query->have_posts()) {
            // Warengruppen-Überschrift
            $pdf->Cell(160, 8, convertText("Warengruppe: {$term->name}"), 1, 1, 'L');

            $warengruppe_total = 0;

            // Produkte der Warengruppe auflisten
            while ($produkte_query->have_posts()) {
                $produkte_query->the_post();

                $artikelbezeichnung = get_the_title();
                $artikelnummer = get_post_meta(get_the_ID(), 'artikelnummer', true);
                $bestand = get_post_meta(get_the_ID(), 'bestand', true);
                $reserviert = get_post_meta(get_the_ID(), 'reserviert', true);
                $verfuegbarkeit = $bestand - $reserviert;

                if ($verfuegbarkeit < 0) {
                    $verfuegbarkeit = 0;
                }

                $warengruppe_total += $verfuegbarkeit;

                $pdf->Cell(80, 8, convertText($artikelbezeichnung), 1);
                $pdf->Cell(50, 8, convertText($artikelnummer), 1); // Breitere Spalte für Artikelnummer
                $pdf->Cell(30, 8, number_format($verfuegbarkeit, 0, ',', '.'), 1, 1, 'R'); // Kleinere Spalte für Verfügbarkeit
            }

            // Total der Warengruppe
            $pdf->Cell(130, 8, 'Total:', 1, 0, 'R');
            $pdf->Cell(30, 8, number_format($warengruppe_total, 0, ',', '.'), 1, 1, 'R');

            // Leerzeile nach jeder Warengruppe
            $pdf->Ln(4);
        }
        wp_reset_postdata();
    }

    // PDF generieren und Ausgabe an den Browser
    $date = date('Y-m-d');
    $pdf->Output('D', "bestand_$date.pdf");
    exit;
}


// Shortcode-Callback-Funktion für den PDF-Export
function hoffmann_pdf_shortcode() {
    // Link zur PDF-Erstellung
    return '<a href="' . esc_url(add_query_arg('export_pdf', '1')) . '">Artikelverfügbarkeit als PDF exportieren</a>';
}
add_shortcode('hoffmann_pdf_export', 'hoffmann_pdf_shortcode');

// Export-Aktion, wenn der Parameter in der URL gesetzt ist
function hoffmann_pdf_export_download() {
    if (isset($_GET['export_pdf']) && $_GET['export_pdf'] == '1') {
        hoffmann_export_pdf();
    }
}
add_action('init', 'hoffmann_pdf_export_download');
?>