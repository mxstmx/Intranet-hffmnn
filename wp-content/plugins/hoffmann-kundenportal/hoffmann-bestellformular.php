<?php
/**
 * Plugin Name: Hoffmann Bestellformular
 * Description: Zeigt Produkte mit Verfügbarkeit und Bestellmenge in 10er-Schritten, filterbar nach Warengruppen, erstellt eine .xls-Datei und versendet per SMTP-Mail.
 * Version: main-v1.0.1
 * Author: Max Florian Krauss
 */

if (!defined('ABSPATH')) {
    exit;
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer laden
require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

/**
 * Shortcode: [hoffmann_bestellformular]
 */
add_shortcode('hoffmann_bestellformular', 'hoffmann_render_bestellformular');
function hoffmann_render_bestellformular() {
    ob_start();

    echo '<style>
    @media (max-width: 768px) {
      .hoffmann-filter,
      .bestellformular,
      .bestellformular input,
      .bestellformular button {
        font-size: 80%;
      }
    }
    </style>';

    $terms = get_terms([ 'taxonomy' => 'warengruppe', 'hide_empty' => false ]);
    if ( is_wp_error( $terms ) ) {
        return '<p>Fehler beim Laden der Warengruppen.</p>';
    }
    $default    = ! empty( $terms ) ? sanitize_title( $terms[0]->slug ) : '';
    $url_filter = isset( $_GET['gruppe'] ) ? sanitize_title( $_GET['gruppe'] ) : $default;

    if ( isset( $_GET['success'] ) ) {
        $class = $_GET['success'] === '1' ? 'success' : 'error';
        $msg   = $_GET['success'] === '1'
               ? '✅ Bestellung erfolgreich versendet'
               : '❌ Fehler beim Versand';
        echo '<div class="notice ' . esc_attr( $class ) . '">' . esc_html( $msg ) . '</div>';
    }

    echo '<div class="hoffmann-filter">';
    foreach ( $terms as $term ) {
        $slug    = sanitize_title( $term->slug );
        $pressed = $slug === $url_filter ? ' aria-pressed="true"' : '';
        $url     = esc_url( add_query_arg( 'gruppe', $slug ) );
        echo '<button type="button" class="e-filter-item" onclick="location.href=\'' . $url . '\'"' . $pressed . '>'
           . esc_html( $term->name )
           . '</button>';
    }
    echo '</div>';

    ?>
    <form id="hoffmann-bestellformular" method="post">
        <input type="hidden" name="hoffmann_bestellung_absenden" value="1">
        <input type="hidden" name="gruppe" value="<?php echo esc_attr( $url_filter ); ?>">
        <?php wp_nonce_field('hoffmann_bestellung_action', 'hoffmann_bestellung_nonce'); ?>
        <table class="bestellformular"><thead><tr>
            <th width="25%">Artikelname</th>
            <th width="25%">Verfügbarkeit</th>
            <th width="50%">Menge</th>
        </tr></thead><tbody>
        <?php
        $products = get_posts([
            'post_type'      => 'produkte',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        foreach ( $products as $p ) {
            $stock = (int) get_post_meta( $p->ID, 'bestand', true );
            $res   = (int) get_post_meta( $p->ID, 'reserviert', true );
            $avail = max( 0, $stock - $res );
            if ( $avail <= 0 ) continue;
            $grs = wp_get_post_terms( $p->ID, 'warengruppe' );
            if ( is_wp_error( $grs ) || empty( $grs ) ) continue;
            $slug = sanitize_title( $grs[0]->slug );
            if ( $slug !== $url_filter ) continue;
            $artnr = esc_attr( get_post_meta( $p->ID, 'artikelnummer', true ) );
            ?>
            <tr>
                <td><?php echo esc_html( $p->post_title ); ?></td>
                <td><?php echo number_format_i18n( $avail ); ?></td>
                <td>
                    <input
                      type="number"
                      name="menge[<?php echo $artnr; ?>]"
                      step="10"
                      min="0"
                      max="<?php echo $avail; ?>"
                      value="0"
                      data-produktname="<?php echo esc_attr( $p->post_title ); ?>"
                    >
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody></table>
        <button type="submit">Bestellung abschicken</button>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        let warnungAktiv = false;
        
        // Tracking von Eingaben > 0 und Validierung
        document.querySelectorAll('input[type=number]').forEach(input => {
            input.addEventListener('change', function () {
                // Abrundung auf 10er-Schritt
                let v = parseInt(this.value) || 0;
                let mx = parseInt(this.max) || 0;
                
                // Auf 10er abrunden
                v = Math.floor(v / 10) * 10;
                
                // Begrenzung auf Maximum
                if (v > mx) v = mx;
                
                // Wert aktualisieren
                this.value = v;
                
                // Tracking von Änderungen für Warnung beim Wechsel
                if (v > 0) warnungAktiv = true;
            });
        });

        // Warnung beim Filterwechsel
        document.querySelectorAll('.e-filter-item').forEach(btn => {
            btn.addEventListener('click', e => {
                if (warnungAktiv) {
                    e.preventDefault();
                    const weiter = confirm('⚠️ Du hast Mengen eingegeben. Bitte Bestellung erst absenden.\nWillst du trotzdem die Warengruppe wechseln?');
                    if (weiter) {
                        const url = btn.getAttribute('onclick').split("location.href='")[1].replace("'", "");
                        window.location.href = url;
                    }
                }
            });
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

/**
 * Bestellung verarbeiten und versenden
 */
add_action('template_redirect', function() {
    if (
        isset( $_POST['hoffmann_bestellung_absenden'] ) &&
        ! empty( $_POST['menge'] ) &&
        ! empty( $_POST['gruppe'] ) && 
        isset( $_POST['hoffmann_bestellung_nonce'] ) && 
        wp_verify_nonce( $_POST['hoffmann_bestellung_nonce'], 'hoffmann_bestellung_action' )
    ) {
        $group   = sanitize_text_field( $_POST['gruppe'] );
        $data    = $_POST['menge'];
        $has     = false;
        
        // Verbesserte Excel-Datei-Erstellung mit UTF-8 BOM
        $content = "\xEF\xBB\xBF"; // UTF-8 BOM für Excel
        $content .= "Menge\tArtikelnr\tEinzelpreis\tProduktname\n";

        foreach ( $data as $artnr => $qty ) {
            $qty = (int) $qty;
            if ( $qty <= 0 ) continue;
            $posts = get_posts([
                'post_type'   => 'produkte',
                'meta_key'    => 'artikelnummer',
                'meta_value'  => $artnr,
                'numberposts' => 1,
            ]);
            if ( empty( $posts ) ) continue;
            $p   = $posts[0];
            $grs = wp_get_post_terms( $p->ID, 'warengruppe' );
            if ( is_wp_error( $grs ) || empty( $grs ) ) continue;
            if ( sanitize_title( $grs[0]->slug ) !== $group ) continue;
            $stock = (int) get_post_meta( $p->ID, 'bestand', true );
            $res   = (int) get_post_meta( $p->ID, 'reserviert', true );
            $avail = max( 0, $stock - $res );
            $m     = min( floor( $qty / 10 ) * 10, $avail );
            if ( $m <= 0 ) continue;
            
            // Produktname in Excel-Datei aufnehmen
            $content .= "{$m}\t{$artnr}\t5,00\t{$p->post_title}\n";
            $has = true;
        }

        if ( $has ) {
            $user = wp_get_current_user()->user_login;
            $time = date( 'Y-m-d_H-i-s' );
            $file = "{$user}_bestellung_{$time}.xlsx"; // .xlsx statt .xls verwenden
            $path = sys_get_temp_dir() . '/' . $file;
            file_put_contents( $path, $content );

            $mail = new PHPMailer(true);
            $sent = false;
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.office365.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'info@grosshandel-hoffmann.de';
                // Passwort sollte eigentlich in Optionen ausgelagert werden
                $mail->Password   = 'FX1qrNh35i33';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('info@grosshandel-hoffmann.de','Hoffmann Bestellportal');
                $mail->addAddress('info@hoffmann-hd.de');
                $current = wp_get_current_user();
                $mail->Subject = $current->last_name . ' - Bestellung ' . $time;
                $mail->Body    = 'Bestellung im Anhang.';
                
                // Korrekten MIME-Type setzen
                $mail->addAttachment( $path, $file, 'base64', 
                  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );

                if ( $mail->send() ) {
                    $sent = true;
                }
            } catch ( Exception $e ) {
                error_log( 'Mail error: ' . $mail->ErrorInfo );
            }

            if ( file_exists( $path ) ) {
                unlink( $path );
            }

            wp_redirect( add_query_arg( 'success', $sent ? '1' : '0' ) );
            exit;
        }
    }
});