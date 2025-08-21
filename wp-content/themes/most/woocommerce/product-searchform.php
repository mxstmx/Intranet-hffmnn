<?php
/**
 * The template for displaying product search form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-searchform.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the correct `post_type` for WooCommerce product search.
add_filter( 'get_search_form', function ( $form ) {
    if ( is_search() && get_query_var( 'post_type' ) === 'product' ) {
        $form = str_replace( '<input type="hidden" name="post_type" value="product" />', '', $form );
    }
    return $form;
}, 10, 1 );
?>

<?php get_search_form(); ?>
