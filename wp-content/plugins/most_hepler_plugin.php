<?php

/**
 *Plugin Name: Most Helper Plugin
 *Plugin URI: https://themeforest.net/user/madsparrow
 *Description: Special Plugin for create portfolios Post Type
 *Author: Mad Sparrow
 *Author URI: https://madsparrow.me
 *Version: 1.1.0
 *Text Domain: most
 *License: GPLv2+
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' ); // Exit if accessed directly.
}

/*
 * Register custom post type for special use
 */
if ( ! function_exists( 'portfolios_register' ) ) {

	function portfolios_register() {

		$labels = array(
			'name'                  => _x( 'Portfolio', 'Post Type General Name', 'most' ),
			'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'most' ),
			'menu_name'             => __( 'Portfolios', 'most' ),
			'name_admin_bar'        => __( 'Project', 'most' ),
			'archives'              => __( 'Project Archives', 'most' ),
			'attributes'            => __( 'Project Attributes', 'most' ),
			'parent_item_colon'     => __( 'Parent Item:', 'most' ),
			'all_items'             => __( 'All Projects', 'most' ),
			'add_new_item'          => __( 'Add New Project', 'most' ),
			'add_new'               => __( 'Add Project', 'most' ),
			'new_item'              => __( 'New Project', 'most' ),
			'edit_item'             => __( 'Edit Project', 'most' ),
			'update_item'           => __( 'Update Project', 'most' ),
			'view_item'             => __( 'View Project', 'most' ),
			'view_items'            => __( 'View Projects', 'most' ),
			'search_items'          => __( 'Search Project', 'most' ),
			'not_found'             => __( 'Not found', 'most' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'most' ),
			'featured_image'        => __( 'Featured Image', 'most' ),
			'set_featured_image'    => __( 'Set featured image', 'most' ),
			'remove_featured_image' => __( 'Remove featured image', 'most' ),
			'use_featured_image'    => __( 'Use as featured image', 'most' ),
			'insert_into_item'      => __( 'Insert into Project', 'most' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Project', 'most' ),
			'items_list'            => __( 'Projects list', 'most' ),
			'items_list_navigation' => __( 'Projects list navigation', 'most' ),
			'filter_items_list'     => __( 'Filter Projects list', 'most' ),
		);
		$args = array(
			'label'                 => __( 'Project', 'most' ),
			'description'           => __( 'Add Portfolios here', 'most' ),
			'labels'                => $labels,
			'show_in_rest' 			=> true,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'categories' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 6,
			'menu_icon'             => 'dashicons-portfolio',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,		
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
		);
		register_post_type( 'portfolios', $args );
	}
}
add_action( 'init', 'portfolios_register' );

// Albums category
if ( ! function_exists( 'portfolios_taxonomies' ) ) {

	function portfolios_taxonomies() {
	    $labels = array(
	        'name'              => _x( 'Categories', 'taxonomy general name', 'most' ),
	        'singular_name'     => _x( 'Category', 'taxonomy singular name', 'most' ),
	        'search_items'      => __( 'Search Categories', 'most' ),
	        'all_items'         => __( 'All Categories', 'most' ),
	        'parent_item'       => __( 'Parent Category', 'most' ),
	        'parent_item_colon' => __( 'Parent Category:', 'most' ),
	        'edit_item'         => __( 'Edit Category', 'most' ),
	        'update_item'       => __( 'Update Category', 'most' ),
	        'add_new_item'      => __( 'Add New Category', 'most' ),
	        'new_item_name'     => __( 'New Category Name', 'most' ),
	        'menu_name'         => __( 'Categories', 'most' ),
	    );

	    $args = array(
	        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
	        'labels'            => $labels,
	        'show_ui'           => true,
	        'show_in_nav_menus' => true,
	        'show_admin_column' => true,
	        'show_in_rest'      => true,
	        'query_var'         => true,
	        'rewrite'           => array( 'slug' => 'categories' ),
	    );

	    register_taxonomy( 'portfolios_categories', array( 'portfolios' ), $args );
	}
}
add_action( 'init', 'portfolios_taxonomies', 0 );

// Action Widgets Init
function most_register_widgets() {
	$most_widgets = array(
		'recent_posts' => 'most_recent_widget_custom',
	);

	if ( class_exists( 'acf' ) ) {
		foreach ( $most_widgets as $key => $value ) {
			require_once plugin_dir_path( __FILE__ ) . 'widgets/' . sanitize_key( $key ) . '.php';
			register_widget( $value );
		}
	}
}
add_action( 'widgets_init', 'most_register_widgets' );

if ( ! class_exists( 'MSHelperPlugin' ) ) {

	class MSHelperPlugin {

        private $theme_slug;
        private $theme_name;
        private $theme_version;
        private $theme_author;
        private $theme_is_child;

		private static $_instance = null;

		/**
		 * Main Instance
		 * Ensures only one instance of this class exists in memory at any one time.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
				self::$_instance->init_hooks();
				self::$_instance->theme_init();

			}
			return self::$_instance;
		}

		public $plugin_path;
		public $plugin_url;
		public $plugin_name;
		public $plugin_version;
		public $plugin_slug;

		public function __construct() {
			// We do nothing here!
		}

		/**
		 * Plugin init
		 */
		public function plugin_init() {
			$data = get_plugin_data( __FILE__ );
			$this->plugin_name = $data[ 'Name' ];
			$this->plugin_version = $data[ 'Version' ];
			$this->plugin_slug = 'ms_helper_plugin';
		}

		/**
		 * Theme init
		 */
		public function theme_init() {
			$theme_info = wp_get_theme();
			$theme_parent = $theme_info->parent();
			if ( !empty( $theme_parent ) ) {
				$theme_info = $theme_parent;
			}
            $this->theme_slug = $theme_info->get_stylesheet();
            $this->theme_name = $theme_info->get( 'Name' );
            $this->theme_version = $theme_info->get( 'Version' );
            $this->theme_author = $theme_info->get( 'Author' );
            $this->theme_is_child = !empty( $theme_parent );
		}

		/**
		 * Init options
		 */
		public function init_option() {
			$this->plugin_path = plugin_dir_path( __FILE__ );
			$this->plugin_url = plugin_dir_url( __FILE__ );

			load_plugin_textdomain( 'most', false, $this->plugin_path . 'languages/' );
		}

		/**
		 * Init hooks
		 */
		public function init_hooks() {
			add_action( 'admin_init', array( $this, 'plugin_init' ) );

			// Process Elementor Blocks
			if ( defined( 'ELEMENTOR_PATH' ) ) {
				add_action( 'init', array( $this, 'init_elementor' ) );
			}
		}

		/**
		 * Init Elementor
		 */
		public function init_elementor() {
			require_once $this->plugin_path . 'elementor/helper.php';
			require_once $this->plugin_path . 'elementor/elementor.php';
			require_once $this->plugin_path . 'elementor/group-control-image-size.php';
		}

		/**
		 * Get all options
		 */
		private function get_options() {
			$options_slug = 'most_helper_options';
			return unserialize( get_option( $options_slug, 'a:0:{}' ) );
		}

		/**
		 * Get option value
		 */
		public function get_option( $name, $default = null ) {
			$options = self::get_options();
			$name = sanitize_key( $name );
			return isset( $options[ $name ] ) ? $options[ $name ] : $default;
		}

		/**
		 * Update option
		 */
		public function update_option( $name, $value ) {
			$options_slug = 'most_helper_options';
			$options = self::get_options();
			$options[ sanitize_key( $name ) ] = $value;
			update_option( $options_slug, serialize( $options ) );
		}

		/**
		 * Get all caches
		 */
		private function get_caches() {
			$caches_slug = 'cache';
			return $this->get_option( $caches_slug, array() );
		}

		/**
		 * Set cache
		 */
		public function set_cache( $name, $value, $time = 3600 ) {
			if ( ! $time || $time <= 0 ) {
				return;
			}
			$caches_slug = 'cache';
			$caches = self::get_caches();

			$caches[ sanitize_key( $name ) ] = array(
				'value' => $value,
				'expired' => time() + ( (int) $time ? $time : 0 ),
			);
			$this->update_option( $caches_slug, $caches );
		}

		/**
		 * Get cache
		 */
		public function get_cache( $name, $default = null ) {
			$caches = self::get_caches();
			$name = sanitize_key( $name );
			return isset( $caches[ $name ][ 'value' ] ) ? $caches[ $name ][ 'value' ] : $default;
		}

		/**
		 * Clear cache
		 */
		public function clear_cache( $name ) {
			$caches_slug = 'cache';
			$caches = self::get_caches();
			$name = sanitize_key( $name );
			if ( isset( $caches[ $name ] ) ) {
				$caches[ $name ] = null;
				$this->update_option( $caches_slug, $caches );
			}
		}

		/**
		 * Clear all expired caches
		 */
		public function clear_expired_caches() {
			$caches_slug = 'cache';
			$caches = self::get_caches();
			foreach ( $caches as $k => $cache ) {
				if ( isset( $cache ) && isset( $cache[ 'expired' ] ) && $cache[ 'expired' ] < time() ) {
					$caches[ $k ] = null;
				}
			}
			$this->update_option( $caches_slug, $caches );
		}


	}

	function ms_helper_plugin() {
		return MSHelperPlugin::instance();
	}
	add_action( 'plugins_loaded', 'ms_helper_plugin' );

}


