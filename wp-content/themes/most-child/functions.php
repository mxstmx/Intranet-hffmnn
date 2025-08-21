<?php
//
// Recommended way to include parent theme styles.
//  (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
//  
add_action('wp_enqueue_scripts', 'load_child_theme_styles', 999);

function load_child_theme_styles(){

    // If your css changes are minimal we recommend you to put them in the main style.css.
    // In this case uncomment bellow

    wp_enqueue_style( 'child-theme-style', get_stylesheet_directory_uri() . '/style.css' );

}