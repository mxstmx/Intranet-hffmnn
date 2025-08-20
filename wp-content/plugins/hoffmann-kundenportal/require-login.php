<?php
/**
 * Plugin Name: Require Login
 * Description: Restrict site to logged-in users by redirecting to the login page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Redirect visitors to the login page if they are not authenticated.
 */
function hffmnn_require_login() {
    if ( is_user_logged_in() || is_login_page() || wp_doing_cron() || wp_doing_ajax() || hffmnn_is_custom_login() ) {
        return;
    }

    wp_safe_redirect( home_url( '/login' ) );
    exit;
}
add_action( 'init', 'hffmnn_require_login' );

/**
 * Determine if the current request is for the login or registration pages.
 *
 * @return bool True when on the login or registration screen.
 */
function is_login_page() {
    return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ), true );
}

/**
 * Determine if the current request is for the custom login page.
 *
 * @return bool True when visiting the custom login page.
 */
function hffmnn_is_custom_login() {
    $login_path   = untrailingslashit( parse_url( home_url( '/login' ), PHP_URL_PATH ) );
    $request_path = untrailingslashit( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

    return $request_path === $login_path;
}
