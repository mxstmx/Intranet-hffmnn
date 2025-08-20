<?php
/*
Plugin Name: Hoffmann Kundenportal
Description: Kundenportal-Plugin für Hoffmann Handel & Dienstleistungen GmbH & Co. KG, nur Kunden haben Zugriff auf das Frontend, neue Kunden werden aus einer JSON-Datei erstellt.
Version: 1.7
Author: Max Florian Krauss
*/

define('KUNDEN_JSON_PATH', WP_CONTENT_DIR . '/uploads/json/kunden.json');

/**
 * Helper function to determine if a user has a specific role.
 *
 * @param string   $role    The role slug to check for.
 * @param int|null $user_id Optional user ID. Defaults to current user.
 *
 * @return bool True if the user has the role, otherwise false.
 */
function hoffmann_user_has_role($role, $user_id = null) {
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();

    if (!$user) {
        return false;
    }

    return in_array($role, (array) $user->roles, true);
}

// 1. Rolle "Kunde" erstellen, wenn das Plugin aktiviert wird
register_activation_hook(__FILE__, 'hoffmann_create_customer_role');
function hoffmann_create_customer_role() {
    add_role(
        'kunde', 
        __('Kunde'), 
        [
            'read' => true, // Zugriff auf das Frontend
            'edit_posts' => false, // Kein Zugriff auf Beiträge
            'delete_posts' => false, // Keine Beiträge löschen
        ]
    );
}

// 2. Umleitung zur Login-Seite für nicht eingeloggte Benutzer auf /login
add_action('template_redirect', 'hoffmann_redirect_to_custom_login');
function hoffmann_redirect_to_custom_login() {
    if (!is_user_logged_in() && !is_page('login')) {
        wp_redirect(home_url('/login'));
        exit;
    }
}

// 3. Benutzer aus der JSON-Datei hinzufügen oder aktualisieren
function hoffmann_update_customers_from_json() {
    if (!file_exists(KUNDEN_JSON_PATH)) {
        error_log('Kunden JSON-Datei nicht gefunden.');
        return;
    }

    $json_data = file_get_contents(KUNDEN_JSON_PATH);
    $kundenData = json_decode($json_data, true);

    if (!$kundenData) {
        error_log('Fehler beim Lesen der Kunden JSON-Datei.');
        return;
    }

    foreach ($kundenData as $kundennr => $details) {
        $username = $kundennr;
        $email = $username . '@hoffmann-hd.de';
        $password = trim($details['Adresse']['PLZ']); // Passwort ist die PLZ des Kunden
        $lastname = trim($details['Name']);
        $strasse = isset($details['Adresse']['Strasse']) ? trim($details['Adresse']['Strasse']) : '';
        $ort = isset($details['Adresse']['Ort']) ? trim($details['Adresse']['Ort']) : '';
        $plz = isset($details['Adresse']['PLZ']) ? trim($details['Adresse']['PLZ']) : '';

        $existing_user = get_user_by('login', $username);

        if ($existing_user) {
            // Vorhandenen Benutzer aktualisieren
            $user_id = $existing_user->ID;

            wp_update_user([
                'ID'         => $user_id,
                'user_email' => $email,
                'last_name'  => $lastname,
            ]);

            update_user_meta($user_id, 'adresse_strasse', $strasse);
            update_user_meta($user_id, 'adresse_ort', $ort);
            update_user_meta($user_id, 'adresse_plz', $plz);

            error_log("Benutzer $username aktualisiert");
        } else {
            // Benutzer erstellen und Passwort explizit setzen
            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_pass'  => $password,
                'user_email' => $email,
                'role'       => 'kunde',
                'last_name'  => $lastname,
            ]);

            if (!is_wp_error($user_id)) {
                wp_set_password($password, $user_id); // Passwort direkt in der Datenbank setzen

                // Zusätzliche Benutzerinformationen (Adresse, PLZ, Ort) speichern und protokollieren
                update_user_meta($user_id, 'adresse_strasse', $strasse);
                update_user_meta($user_id, 'adresse_ort', $ort);
                update_user_meta($user_id, 'adresse_plz', $plz);

                error_log("Benutzer $username erfolgreich erstellt mit Adresse: Straße = $strasse, Ort = $ort, PLZ = $plz");
            } else {
                error_log("Fehler beim Erstellen des Benutzers $username: " . $user_id->get_error_message());
            }
        }
    }
}

// 4. Benutzerdefinierte Felder im Benutzerprofil anzeigen
add_action('show_user_profile', 'hoffmann_show_custom_user_fields');
add_action('edit_user_profile', 'hoffmann_show_custom_user_fields');
function hoffmann_show_custom_user_fields($user) {
    if (current_user_can('administrator')) {
        ?>
        <h3>Zusätzliche Benutzerinformationen</h3>
        <table class="form-table">
            <tr>
                <th><label for="adresse_strasse">Straße</label></th>
                <td><input type="text" name="adresse_strasse" id="adresse_strasse" value="<?php echo esc_attr(get_user_meta($user->ID, 'adresse_strasse', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="adresse_ort">Ort</label></th>
                <td><input type="text" name="adresse_ort" id="adresse_ort" value="<?php echo esc_attr(get_user_meta($user->ID, 'adresse_ort', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="adresse_plz">PLZ</label></th>
                <td><input type="text" name="adresse_plz" id="adresse_plz" value="<?php echo esc_attr(get_user_meta($user->ID, 'adresse_plz', true)); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
}

add_action('personal_options_update', 'hoffmann_save_custom_user_fields');
add_action('edit_user_profile_update', 'hoffmann_save_custom_user_fields');
function hoffmann_save_custom_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    update_user_meta($user_id, 'adresse_strasse', sanitize_text_field($_POST['adresse_strasse'] ?? ''));
    update_user_meta($user_id, 'adresse_ort', sanitize_text_field($_POST['adresse_ort'] ?? ''));
    update_user_meta($user_id, 'adresse_plz', sanitize_text_field($_POST['adresse_plz'] ?? ''));
}

// 5. Cronjob für die Aktualisierung der Kunden aus der JSON-Datei alle 30 Minuten
add_filter('cron_schedules', 'hoffmann_add_cron_interval');
function hoffmann_add_cron_interval($schedules) {
    $schedules['thirty_minutes'] = [
        'interval' => 1800,
        'display'  => __('Every 30 Minutes')
    ];
    return $schedules;
}

register_activation_hook(__FILE__, 'hoffmann_schedule_customer_update');
function hoffmann_schedule_customer_update() {
    if (!wp_next_scheduled('hoffmann_update_customers_event')) {
        wp_schedule_event(time(), 'thirty_minutes', 'hoffmann_update_customers_event');
    }
}
add_action('hoffmann_update_customers_event', 'hoffmann_update_customers_from_json');

// 6. Rolle "Kunde" auf das Frontend beschränken und nach dem Login zur Startseite weiterleiten
add_action('init', 'hoffmann_restrict_kunde_backend_access');
function hoffmann_restrict_kunde_backend_access() {
    if (hoffmann_user_has_role('kunde') && is_admin()) {
        wp_redirect(home_url());
        exit;
    }
}

// 7. Cronjob bei Deaktivierung entfernen
register_deactivation_hook(__FILE__, 'hoffmann_remove_cron');
function hoffmann_remove_cron() {
    $timestamp = wp_next_scheduled('hoffmann_update_customers_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'hoffmann_update_customers_event');
    }
}


// Benutzer nach erfolgreichem Login auf die Startseite weiterleiten
add_filter('login_redirect', 'hoffmann_redirect_to_home_after_login', 10, 3);
function hoffmann_redirect_to_home_after_login($redirect_to, $request, $user) {
    // Prüfen, ob der Benutzer eingeloggt ist und die Rolle "Kunde" hat
    if (isset($user->roles) && in_array('kunde', $user->roles)) {
        return home_url(); // Weiterleitung zur Startseite
    }

    return $redirect_to; // Standard-Weiterleitung für andere Benutzer
}


add_filter('show_admin_bar', 'hoffmann_hide_admin_bar_for_kunden');
function hoffmann_hide_admin_bar_for_kunden($show) {
    if (hoffmann_user_has_role('kunde')) {
        return false;
    }
    return $show;
}

// Logout-Link Shortcode hinzufügen
add_shortcode('hoffmann_logout', 'hoffmann_logout_link');
function hoffmann_logout_link() {
    // Generiert eine Logout-URL, die anschließend zurück zur Login-Seite führt
    $logout_url = wp_logout_url( home_url('/login') );
    // Rückgabe als Button (kann natürlich beliebig als Link oder Button gestylt werden)
    return '<a class="button hoffmann-logout" href="' . esc_url( $logout_url ) . '">Logout</a>';
}

