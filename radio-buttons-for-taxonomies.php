<?php
/*
 * Plugin Name: 	  Radio Buttons for Taxonomies
 * Plugin URI: 		  http://www.kathyisawesome.com/441/radio-buttons-for-taxonomies
 * Description: 	  Use radio buttons for any taxonomy so users can only select 1 term at a time
 * Version:           2.4.7
 * Author:            helgatheviking
 * Author URI:        https://www.kathyisawesome.com
 * Requires at least: 4.5.0
 * Tested up to:      6.1.0
 *
 * Text Domain:       radio-buttons-for-taxonomies
 * Domain Path:       /languages/
 *
 * @package           Radio Buttons for Taxonomies
 * @author            Kathy Darling
 * @copyright         Copyright (c) 2019, Kathy Darling
 * @license           http://opensource.org/licenses/gpl-3.0.php GNU Public License
 *
 * Props to by Stephen Harris http://profiles.wordpress.org/stephenh1988/
 * For his wp.tuts+ tutorial: http://wp.tutsplus.com/tutorials/creative-coding/how-to-use-radio-buttons-with-taxonomies/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class.
 *
 * @class    Radio_Buttons_for_Taxonomies
 */
class Radio_Buttons_For_Taxonomies {

	/**
	 * Donation URL.
	 *
	* @constant string donate url
	* @since 1.7.8
	*/
	const DONATE_URL = 'https://www.paypal.me/kathyisawesome';

	/* @var obj $instance The single instance of Radio_Buttons_for_Taxonomies.*/
	protected static $_instance = null;

	/* @var str $version */
	public static $version = '2.4.7';

	/* @var array $options - The plugin's options. */
	public $options = array();

	/* @var WordPress_Radio_Taxonomy[] - Array of WordPress_Radio_Taxonomy instances as an array, keyed on taxonomy name. */
	public $taxonomies = array();

	/**
	 * Main Radio_Buttons_for_Taxonomies Instance
	 *
	 * Ensures only one instance of Radio_Buttons_for_Taxonomies is loaded or can be loaded.
	 *
	 * @since 1.6.0
	 * @static
	 * @see Radio_Buttons_for_Taxonomies()
	 * @return Radio_Buttons_for_Taxonomies - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.6.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning this object is forbidden.', 'radio-buttons-for-taxonomies' ), '1.6' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.6.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'radio-buttons-for-taxonomies' ), '1.6' );
	}

	/**
	 * Radio_Buttons_for_Taxonomies Constructor.
	 * @access public
	 * @return Radio_Buttons_for_Taxonomies
	 * @since  1.0
	 */
	public function __construct() {

		// Include required files.
		include_once 'inc/class-wordpress-radio-taxonomy.php';

		// Include taxonomy walker.
		include_once 'inc/class-walker-category-radio.php';

		// Load compatibility modules.
		include_once 'inc/class-rb4t-compatibility.php';

		// Set-up Action and Filter Hooks.
		register_uninstall_hook( __FILE__, array( __CLASS__, 'delete_plugin_options' ) );

		// load plugin text domain for translations.
		add_action( 'init', array( $this, 'load_text_domain' ) );

		// Launch each taxonomy class.
		add_action( 'wp_loaded', array( $this, 'launch' ) );

		// register admin settings.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// add plugin options page.
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );

		// Load admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );

		// Load Gutenberg sidebar scripts.
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_assets' ), 99 );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ), 10, 2 );

		// Add Donate link to plugin.
		add_filter( 'plugin_row_meta', array( $this, 'add_meta_links' ), 10, 2 );

		// Add "no term" to taxonomy rest result for Gutenberg sidebar.
		add_action( 'rest_api_init', array( $this, 'register_rest_field' ) );

		// Limit return to first term... just in case.
		add_filter( 'get_the_terms', array( $this, 'restrict_terms' ), 10, 3 );

	}


	/**
	 * Delete options table entries ONLY when plugin deactivated AND deleted.
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public static function delete_plugin_options() {
		$options = get_option( 'radio_button_for_taxonomies_options', true );
		if ( isset( $options['delete'] ) && $options['delete'] ) delete_option( 'radio_button_for_taxonomies_options' );
	}

	/**
	 * Make plugin translation-ready
	 *
	 * @access public
	 * @since  1.0
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'radio-buttons-for-taxonomies', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * For each taxonomy that we are converting to radio buttons, store in taxonomies class property, ex: $this->taxonomies[categories]
	 *
	 * @access public
	 * @return object
	 * @since  1.0
	 */
	public function launch() {
		// Run only for taxonomies we need.
		$radiotaxonomies = $this->get_options( 'taxonomies' );

		// Loop through selected taxonomies.
		foreach ( $radiotaxonomies as $radiotaxonomy ) {
			if ( taxonomy_exists( $radiotaxonomy ) ) {
				$this->taxonomies[$radiotaxonomy] = new WordPress_Radio_Taxonomy( $radiotaxonomy  );
			}
		}
	}

	// ------------------------------------------------------------------------------
	// Admin options
	// ------------------------------------------------------------------------------

	/**
	 * Whitelist plugin options
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_init() {
		register_setting( 'radio_button_for_taxonomies_options', 'radio_button_for_taxonomies_options', array( $this, 'validate_options' ) );
	}


	/**
	 * Add plugin's options page
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function add_options_page() {
		add_options_page(__( 'Radio Buttons for Taxonomies Options Page', 'radio-buttons-for-taxonomies' ), __( 'Radio Buttons for Taxonomies', 'radio-buttons-for-taxonomies' ), 'manage_options', 'radio-buttons-for-taxonomies', array( $this,'render_form' ) );
	}

	/**
	 * Render the Plugin options form
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function render_form() {
		include 'inc/plugin-options.php';
	}

	/**
	 * Sanitize and validate options
	 *
	 * @access public
	 * @param  array $input
	 * @return array
	 * @since  1.0
	 */
	public function validate_options( $input ) {

		$clean = array();

		// Probably overkill, but make sure that the taxonomy actually exists and is one we're cool with modifying.
		$taxonomies = $this->get_all_taxonomies();

		if ( isset( $input['taxonomies'] ) ) {
			foreach ( $input['taxonomies'] as $tax ) {
				if ( array_key_exists( $tax, $taxonomies ) ) {
					$clean['taxonomies'][] = $tax;
				}
			}
		}

		$clean['delete'] =  isset( $input['delete'] ) && $input['delete'] ? 1 : 0 ;  // Checkbox.

		return $clean;
	}

	/**
	 * Enqueue Scripts
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_script( $hook ) {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( 'radiotax', plugins_url( 'js/radiotax' . $suffix . '.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), self::$version, true );

		// Get admin screen id.
		$screen           = get_current_screen();
		$screen_base      = $screen ? $screen->base : '';
		$post_type        = $screen ? $screen->post_type : '';
		$has_block_editor = is_callable( array( $screen, 'is_block_editor' ) ) && $screen->is_block_editor();

		/**
		 * Enqueue scripts.
		 */
		if ( in_array( $screen_base, array( 'post', 'edit' ) ) && ! $has_block_editor ) {

			// If the post type has a radio taxonomy.
			if ( $post_type && array_intersect( $this->options['taxonomies'], get_object_taxonomies( $post_type, 'names' ) ) ) {
				wp_enqueue_script( 'radiotax' );
			}

		}
	}

	/**
	 * Load Gutenberg Sidebar Scripts
	 *
	 * @access public
	 * @return void
	 * @since  2.0
	 */
	public function block_editor_assets() {

		// Automatically load dependencies and version.
		$asset_file = include( plugin_dir_path( __FILE__ ) . 'js/dist/index.asset.php');

		wp_enqueue_script(
			'radiotax-gutenberg-sidebar',
			plugins_url( 'js/dist/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'radiotax-gutenberg-sidebar', 'radio-buttons-for-taxonomies' );

		$i18n = array( 'radio_taxonomies' => (array) $this->get_options( 'taxonomies' ) );
		wp_localize_script( 'radiotax-gutenberg-sidebar', 'RB4Tl18n', $i18n );
	}

	/**
	 * Display a Settings link on the main Plugins page
	 *
	 * @access public
	 * @param  array $links
	 * @param  string $file
	 * @return array
	 * @since  1.0
	 */
	public function add_action_links( $links, $file ) {

		$plugin_link = '<a href="' . admin_url( 'options-general.php?page=radio-buttons-for-taxonomies' ) . '">' . esc_html__( 'Settings', 'radio-buttons-for-taxonomies' ) . '</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $plugin_link );

		return $links;
	}


	/**
	 * Add donation link
	 *
	 * @param array $plugin_meta - The  plugin's meta data.
	 * @param string $plugin_file - This base file.
	 * @since 1.7.8
	 */
	public function add_meta_links( $plugin_meta, $plugin_file ) {
		if ( $plugin_file == plugin_basename(__FILE__) ) {
			$plugin_meta[] = '<a class="dashicons-before dashicons-awards" href="' . self::DONATE_URL . '" target="_blank">' . __( 'Donate', 'radio-buttons-for-taxonomies' ) . '</a>';
		}
		return $plugin_meta;
	}


	/**
	 * Rest terms query. Tell front-end to include a "no-terms" option
	 *
	 * @since 2.2.0
	 */
	public function register_rest_field() {

		register_rest_field(
			'taxonomy',
			'radio_no_term',
			array(
				'get_callback' => function ( $params ) {
					$taxonomy = $params['slug'];

					// Always false if not a radio taxonomy.
					if ( ! $this->is_radio_tax( $taxonomy ) ) {
						return false;
					}

					// If it is a radio, then show the "no term" if the tax has no default.
					$has_default = 'category' === $taxonomy || get_option( 'default_term_' . $taxonomy );
					
					return apply_filters( 'radio_buttons_for_taxonomies_no_term_' . $taxonomy, ! $has_default );

			    },
				'schema' => array(
					'description' => __( 'Radio taxonomy should show no term option.', 'radio-buttons-for-taxonomies' ),
					'type'        => 'bool'
				),
			)
		);

		register_rest_field(
			'taxonomy',
			'default_term',
			array(
				'get_callback' => function ( $params ) {
					return intval( get_option( 'default_' . $params['slug'], 0 ) );
			    },
				'schema' => array(
					'description' => __( 'Taxonomy default term ID.', 'radio-buttons-for-taxonomies' ),
					'type'        => 'int'
				),
			)
		);

	}



	/**
	 * Filters the list of terms attached to the given post, limit response to single term.
	 *
	 * @since 2.4.0
	 *
	 * @param WP_Term[]|WP_Error $terms    Array of attached terms, or WP_Error on failure.
	 * @param int                $post_id  Post ID.
	 * @param string             $taxonomy Name of the taxonomy.
	 * @return WP_Term[]|WP_Error
	 */
	function restrict_terms( $terms, $post_id, $taxonomy ) {
		if ( ! is_wp_error( $terms ) && $this->is_radio_tax( $taxonomy ) && count( $terms) > 1 ) {
			$terms = array_slice( $terms, 0, 1 );
		}
		return $terms;
	}

	// ------------------------------------------------------------------------------
	// Helper Functions
	// ------------------------------------------------------------------------------

	/**
	 * Get all taxonomies - for plugin options checklist
	 * @access public
	 * @return array
	 * @since  1.7
	 */
	function get_all_taxonomies() {

		$args = array (
			'show_ui'  => true
		);

		$taxonomies = apply_filters( 'radio_buttons_for_taxonomies_taxonomies', get_taxonomies( $args, 'objects' ) );

		ksort( $taxonomies );

		return $taxonomies;
	}

	/**
	 * Test WordPress current version
	 *
	 * @deprecated
	 *
	 * @param array $version
	 * @return bool
	 */
	public function is_version( $version = '4.4.0' ) {
		_deprecated_function( __FUNCTION__, '2.0.0', 'Radio_Buttons_for_Taxonomies::is_wp_version_gte()' );
		return ! $this->is_wp_version_gte( $version );
	}

	/**
	 * Test WordPress current version
	 *
	 * @wp-hook mlp_mutually_exclusive_taxonomies
	 * @param array $version
	 * @return bool
	 */
	public function is_wp_version_gte( $version = '4.4.0' ) {
		global $wp_version;
		return version_compare( $wp_version, $version, '>=' );
	}

	/**
	 * Get the plugin options
	 *
	 * @since 2.0.3
	 *
	 * @param string $option - A specific plugin option to retrieve.
	 * @param mixed
	 * @return bool
	 */
	public function get_options( $option = false) {
		if ( ! $this->options ) {

			$defaults = array(
				'taxonomies' => array(),
				'delete'     => 0,
			);
			$this->options = wp_parse_args( get_option( 'radio_button_for_taxonomies_options', true ), $defaults );

		}

		if ( $option && isset( $this->options[ $option ] ) ) {
			return $this->options[ $option ];
		} else {
			return $this->options;
		}
	}


	/**
	 * Is this a radio taxonomy?
	 *
	 * @since 2.2.0
	 *
	 * @param string $taxonomy - the taxonomy name.
	 * @return bool
	 */
	public function is_radio_tax( $taxonomy ) {
		return in_array( $taxonomy, (array) $this->get_options( 'taxonomies' ) );
	}


	// ------------------------------------------------------------------------------
	// Compatibility
	// ------------------------------------------------------------------------------

	/**
	 * Make sure Multilingual Press shows the correct user interface.
	 *
	 * @deprecated 2.4.0 - Moved to separate compat class module.
	 * @see: RB4T_MultilingualPress_Compatibility::multilingualpress_support()
	 */
	public function multilingualpress_support( Array $taxonomies ) {
		_deprecated_function( __METHOD__ . '()', '2.4.0', 'RB4T_MultilingualPress_Compatibility::multilingualpress_support()' );
		RB4T_MultilingualPress_Compatibility::multilingualpress_support( $taxonomies );
	}

} // End class.


/**
 * Launch the whole plugin
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  1.6
 * @return Radio_Buttons_for_Taxonomies
 */
function radio_buttons_for_taxonomies() {
	return Radio_Buttons_for_Taxonomies::instance();
}

// Global for backwards compatibility.
$GLOBALS['Radio_Buttons_for_Taxonomies'] = radio_buttons_for_taxonomies();
