<?php
/*
Plugin Name: Radio Buttons for Taxonomies
Plugin URI: http://www.kathyisawesome.com/441/radio-buttons-for-taxonomies
Description: Use radio buttons for any taxonomy
Version: 1.5.2
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


if ( ! class_exists( "Radio_Buttons_for_Taxonomies" ) ) :

class Radio_Buttons_for_Taxonomies {

  	function __construct(){

	    // Include required files
	    include_once( 'inc/class.WordPress_Radio_Taxonomy.php' );
      include_once( 'inc/class.Walker_Category_Radio.php' );

	    // Set-up Action and Filter Hooks
	    register_uninstall_hook( __FILE__, array( __CLASS__, 'delete_plugin_options' ) );

	    // load plugin text domain for translations
	    add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

      // launch each taxonomy class on a hook
      add_action( 'init', array( $this, 'launch' ) );

	    // register admin settings
	    add_action( 'admin_init', array( $this, 'admin_init' ));

	    // add plugin options page
	    add_action( 'admin_menu', array( $this, 'add_options_page' ) );

      // Load admin scripts
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );

	    // add settings link to plugins page
	    add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

  }


  // --------------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: register_uninstall_hook(__FILE__,  array($this,'delete_plugin_options'))
  // --------------------------------------------------------------------------------------

  // Delete options table entries ONLY when plugin deactivated AND deleted
  function delete_plugin_options() {
    $options = get_option( 'radio_button_for_taxonomies_options', true );
    if( isset( $options['delete'] ) && $options['delete'] ) delete_option( 'radio_button_for_taxonomies_options' );
  }

  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: add_action('plugins_loaded', array($this,'load_text_domain' ))
  // ------------------------------------------------------------------------------

  function load_text_domain() {
      load_plugin_textdomain( "radio-buttons-for-taxonomies", false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: add_action('init', array( $this, 'launch' ) )
  // ------------------------------------------------------------------------------

  function launch(){
      //create a class property for each taxonomy that we are converting to radio buttons
      //for example: $this->categories
      $options = get_option( 'radio_button_for_taxonomies_options', true );

      if( isset( $options['taxonomies'] ) ) foreach( $options['taxonomies'] as $taxonomy ) {
         $this->{$taxonomy} = new WordPress_Radio_Taxonomy( $taxonomy );
      }
  }

  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: add_action('admin_init', 'admin_init' )
  // ------------------------------------------------------------------------------

  // Init plugin options to white list our options
  function admin_init(){
    register_setting( 'radio_button_for_taxonomies_options', 'radio_button_for_taxonomies_options', array( $this,'validate_options' ) );
  }


  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: add_action('admin_menu', 'add_options_page');
  // ------------------------------------------------------------------------------

  // Add menu page
  function add_options_page() {
    add_options_page(__( 'Radio Buttons for Taxonomies Options Page',"radio-buttons-for-taxonomies" ), __( 'Radio Buttons for Taxonomies', "radio-buttons-for-taxonomies" ), 'manage_options', 'radio-buttons-for-taxonomies', array( $this,'render_form' ) );
  }


  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION SPECIFIED IN: add_options_page()
  // ------------------------------------------------------------------------------

  // Render the Plugin options form
  function render_form(){
    include( 'inc/plugin-options.php' );
  }

  // Sanitize and validate input. Accepts an array, return a sanitized array.
  function validate_options( $input ){

    $clean = array();

    //probably overkill, but make sure that the taxonomy actually exists and is one we're cool with modifying
    $args=array(
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

  // ------------------------------------------------------------------------------
  // CALLBACK FUNCTION FOR: add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
  // ------------------------------------------------------------------------------

    public function admin_script(){

      $options = get_option( 'radio_button_for_taxonomies_options', true );

      if( ! isset( $options['taxonomies'] ) ) return;

      if ( function_exists( 'get_current_screen' ) ){

        $screen = get_current_screen();

        if ( ! is_wp_error( $screen ) && in_array( $screen->base, array( 'edit', 'post' ) ) )

          wp_enqueue_script( 'radiotax', plugins_url( 'js/radiotax.js', __FILE__ ), array( 'jquery' ), null, true );

      }

    }

  // Display a Settings link on the main Plugins page
  function add_action_links( $links, $file ) {

    if ( $file == plugin_basename( __FILE__ ) ) {
      $plugin_link = '<a href="'.admin_url( 'options-general.php?page=radio-buttons-for-taxonomies' ) . '">' . __( 'Settings' ) . '</a>';
      // make the 'Settings' link appear first
      array_unshift( $links, $plugin_link );
    }

    return $links;
  }

} // end class
endif;

/**
* Launch the whole plugin
*/
global $Radio_Buttons_for_Taxonomies;
$Radio_Buttons_for_Taxonomies = new Radio_Buttons_for_Taxonomies();