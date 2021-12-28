<?php
/**
 * Extension Compatibilty
 *
 * @package  Radio Buttons for Taxonomies/Compatibility
 * @since    2.4.0
 * @version  2.4.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RB4T_Compatibility Class.
 *
 * Load classes for making plugin compatible with other plugins.
 */
class RB4T_Compatibility {

	/**
	 * Init compatibility classes.
	 */
	public static function init() {

		$module_paths = array();

		// WP SEO fixes.
		if ( class_exists( 'WPSEO_Primary_Term_Admin' ) ) {
			$module_paths['wpseo'] = 'modules/class-rb4t-wpseo-compatibility.php';
		}

		// Multilingualpress support.
		if ( class_exists( 'Multilingual_Press' ) ) {
			$module_paths['mlp'] = 'modules/class-rb4t-multilingualpress-compatibility.php';
		}

		/**
		 * 'rb4t_compatibility_modules' filter.
		 *
		 * Use this to filter the required compatibility modules.
		 *
		 * @param  array $module_paths
		 */
		$module_paths = apply_filters( 'rb4t_compatibility_modules', $module_paths );
		foreach ( $module_paths as $name => $path ) {
			require_once $path;
		}

	}

}
add_action( 'plugins_loaded', array( 'RB4T_Compatibility', 'init' ), 20 );