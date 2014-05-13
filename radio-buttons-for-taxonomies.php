<?php
/*
Plugin Name: Radio Buttons for Taxonomies
Plugin URI: http://www.kathyisawesome.com/441/radio-buttons-for-taxonomies
Description: Use radio buttons for any taxonomy
Version: 1.7.0
Text Domain: radio-buttons-for-taxonomies
Author: Kathy Darling
Author URI: http://www.kathyisawesome.com
License: GPL2

Copyright 2012  Kathy Darling  (email: kathy.darling@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/*
This is a plugin implementation of the wp.tuts+ tutorial: http://wp.tutsplus.com/tutorials/creative-coding/how-to-use-radio-buttons-with-taxonomies/ by Stephen Harris
Stephen Harris http://profiles.wordpress.org/stephenh1988/

To use this plugin, just activate it and go to the settings page.  Then Check the taxonomies that you'd like to switch to using Radio Buttons and save the settings.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if ( ! class_exists( 'Radio_Buttons_for_Taxonomies' ) ) :

class Radio_Buttons_for_Taxonomies {

	/**
	 * @var Radio_Buttons_for_Taxonomies The single instance of the class
	 * @since 1.6.0
	 */
	protected static $_instance = null;

	/**
	 * @var version
	 * @since 1.7.0
	 */
	static $version = '1.7.0';

	/**
	 * @var plugin options
	 * @since 1.7.0
	 */
	public $options = array();

	/**
	 * @var taxonomies WordPress_Radio_Taxonomy instances as an array, keyed on taxonomy name.
	 * @since 1.7.0
	 */
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' , 'radio-buttons-for-taxonomies' ), '1.6' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.6.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' , 'radio-buttons-for-taxonomies' ), '1.6' );
	}

	/**
	 * Radio_Buttons_for_Taxonomies Constructor.
	 * @access public
	 * @return Radio_Buttons_for_Taxonomies
	 * @since  1.0
	 */
	public function __construct(){

			// Include required files
			include_once( 'inc/class.WordPress_Radio_Taxonomy.php' );
			include_once( 'inc/class.Walker_Category_Radio.php' );

			$this->options = get_option( 'radio_button_for_taxonomies_options', true );

			// Set-up Action and Filter Hooks
			register_uninstall_hook( __FILE__, array( __CLASS__, 'delete_plugin_options' ) );

			// load plugin text domain for translations
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

			// launch each taxonomy class when tax is registered
			add_action( 'registered_taxonomy', array( $this, 'launch' ) );

			// register admin settings
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			// add plugin options page
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );

			// Load admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );

			// add settings link to plugins page
			add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

			add_filter( 'mlp_mutually_exclusive_taxonomies', array( $this, 'multilingualpress_support' ) );
	}


	/**
	 * Delete options table entries ONLY when plugin deactivated AND deleted
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function delete_plugin_options() {
		$options = get_option( 'radio_button_for_taxonomies_options', true );
		if( isset( $options['delete'] ) && $options['delete'] ) delete_option( 'radio_button_for_taxonomies_options' );
	}


	/**
	 * Make plugin translation-ready
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function load_text_domain() {
		load_plugin_textdomain( "radio-buttons-for-taxonomies", false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * For each taxonomy that we are converting to radio buttons, store in taxonomies class property, ex: $this->taxonomies[categories]
	 * @access public
	 * @return object
	 * @since  1.0
	 */
	public function launch( $taxonomy ){
		if( isset( $this->options['taxonomies'] ) && in_array( $taxonomy, (array) $this->options['taxonomies'] ) ) {
			$this->taxonomies[$taxonomy] = new WordPress_Radio_Taxonomy( $taxonomy );
		}
	}

	// ------------------------------------------------------------------------------
	// Admin options
	// ------------------------------------------------------------------------------

	/**
	 * Whitelist plugin options
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_init(){
		register_setting( 'radio_button_for_taxonomies_options', 'radio_button_for_taxonomies_options', array( $this,'validate_options' ) );
	}


	/**
	 * Add plugin's options page
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function add_options_page() {
		add_options_page(__( 'Radio Buttons for Taxonomies Options Page',"radio-buttons-for-taxonomies" ), __( 'Radio Buttons for Taxonomies', "radio-buttons-for-taxonomies" ), 'manage_options', 'radio-buttons-for-taxonomies', array( $this,'render_form' ) );
	}

	/**
	 * Render the Plugin options form
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function render_form(){
		include( 'inc/plugin-options.php' );
	}

	/**
	 * Sanitize and validate options
	 * @access public
	 * @param  array $input
	 * @return array
	 * @since  1.0
	 */
	public function validate_options( $input ){

		$clean = array();

		//probably overkill, but make sure that the taxonomy actually exists and is one we're cool with modifying
		$args = array(
			'public'   => true,
			'show_ui' => true
		);

		$taxonomies = get_taxonomies( $args );

		if( isset( $input['taxonomies'] ) ) foreach ( $input['taxonomies'] as $tax ){
			if( in_array( $tax,$taxonomies ) ) $clean['taxonomies'][] = $tax;
		}

		$clean['delete'] =  isset( $input['delete'] ) && $input['delete'] ? 1 : 0 ;  //checkbox

		return $clean;
	}

	/**
	 * Enqueue Scripts
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_script( $hook ){
		if( 'edit.php' == $hook ){
			$suffix = defined( 'WP_SCRIPT_DEBUG' ) && WP_SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'radiotax', plugins_url( 'js/radiotax' . $suffix . '.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), self::$version, true );
		}
	}

	/**
	 * Display a Settings link on the main Plugins page
	 * @access public
	 * @param  array $links
	 * @param  string $file
	 * @return array
	 * @since  1.0
	 */
	public function add_action_links( $links, $file ) {

		if ( $file == plugin_basename( __FILE__ ) ) {
			$plugin_link = '<a href="'.admin_url( 'options-general.php?page=radio-buttons-for-taxonomies' ) . '">' . __( 'Settings' , 'radio-buttons-for-taxonomies' ) . '</a>';
			// make the 'Settings' link appear first
			array_unshift( $links, $plugin_link );
		}

		return $links;
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
			'public'   => true,
			'show_ui'  => true,
			'_builtin' => true
		);

		$defaults = get_taxonomies( $args, 'objects' );
		ksort( $defaults );

		$args['_builtin'] = false;

		$custom = get_taxonomies( $args, 'objects' );
		ksort( $custom );

		return array_merge( $defaults, $custom );
	}

	/**
	 * Make sure Multilingual Press shows the correct user interface.
	 *
	 * This method is called after switch_to_blog(), so we have to fetch the
	 * options separately.
	 *
	 * @wp-hook mlp_mutually_exclusive_taxonomies
	 * @param array $taxonomies
	 * @return array
	 */
	public function multilingualpress_support( Array $taxonomies ) {

		$remote_options = get_option( 'radio_button_for_taxonomies_options', array() );

		if ( empty ( $remote_options['taxonomies'] ) )
			return $taxonomies;

		$all_taxonomies = array_merge( (array) $remote_options['taxonomies'], $taxonomies );

		return array_unique( $all_taxonomies );
	}


} // end class
endif;


/**
 * Launch the whole plugin
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  1.6
 * @return Radio_Buttons_for_Taxonomies
 */
function Radio_Buttons_for_Taxonomies() {
	return Radio_Buttons_for_Taxonomies::instance();
}

// Global for backwards compatibility.
$GLOBALS['Radio_Buttons_for_Taxonomies'] = Radio_Buttons_for_Taxonomies();

