<?php
/**
 * Multilingual Press Compatibility
 *
 * @package  Radio Buttons for Taxonomies/Compatibility/Modules
 * @since    2.4.0
 * @version  2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RB4T_MultilingualPress_Compatibility Class.
 */
class RB4T_MultilingualPress_Compatibility {

	public static function init() {

		// Primary term taxonomies.
		add_filter( 'mlp_mutually_exclusive_taxonomies', array( __CLASS__, 'multilingualpress_support' ) );

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
	public static function multilingualpress_support( Array $taxonomies ) {

		$remote_options = get_option( 'radio_button_for_taxonomies_options', array() );

		if ( empty( $remote_options['taxonomies'] ) ) {
			return $taxonomies;
		}

		$all_taxonomies = array_merge( (array) $remote_options['taxonomies'], $taxonomies );

		return array_unique( $all_taxonomies );
	}

}

RB4T_MultilingualPress_Compatibility::init();
