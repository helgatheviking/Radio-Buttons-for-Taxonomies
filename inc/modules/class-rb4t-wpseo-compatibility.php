<?php
/**
 * Yoast WordPress SEO Compatibility
 *
 * @package  Radio Buttons for Taxonomies/Compatibility/Modules
 * @since    2.4.0
 * @version  2.4.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RB4T_WPSEO_Compatibility Class.
 *
 * Disables Yoast's "primary" term when using radio buttons for that taxonomy.
 */
class RB4T_WPSEO_Compatibility {

	public static function init() {

		// Primary term taxonomies.
		add_filter( 'wpseo_primary_term_taxonomies', array( __CLASS__, 'remove_radio_taxonomies'), 10, 3 );

	}

	/**
	 * Remove any radio button taxonomies from the array.
	 *
	 * @param WP_Taxonomy[]  $taxonomies     An array of taxonomy objects that are primary_term enabled.
	 *
	 * @param string $post_type      The post type for which to filter the taxonomies.
	 * @param array  $all_taxonomies All taxonomies for this post types, even ones that don't have primary term
	 *                               enabled.
	 * @return array
	 */
	public static function remove_radio_taxonomies( $taxonomies, $post_type, $all_taxonomies ) {

		$radio_taxonomies = (array) radio_buttons_for_taxonomies()->get_options( 'taxonomies' );

		if ( ! empty( $radio_taxonomies ) ) {
			$taxonomies = array_diff_key( $taxonomies, array_flip( $radio_taxonomies ) );
		}
		return $taxonomies;
	}

}

RB4T_WPSEO_Compatibility::init();
