<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'WordPress_Radio_Taxonomy' ) ) :

class WordPress_Radio_Taxonomy {

	/**
	* @var string - taxonomy name
	* @since 1.0.0
	*/
	public $taxonomy = null;

	/**
	* @var object - the taxonomy object
	* @since 1.6.0
	*/
	public $tax_obj = null;

	/**
	* @var boolean - whether to filter get_terms() or not
	* @since 1.7.0
	*/
	private $set = true;

	/**
	* @var boolean - whether to print Nonce or not
	* @since 1.7.0
	*/
	private $printNonce = true;

	/**
	* Constructor
	* 
	* @since 1.0.0
	*/
	public function __construct( $taxonomy ) {

		$this->taxonomy = $taxonomy;

		// Get the taxonomy object - need to get it after init but before admin_menu.
		$this->tax_obj = get_taxonomy( $taxonomy );

		// Replace new taxonomy meta box.
		add_action( 'add_meta_boxes', array( $this, 'replace_meta_box' ) );

		// Change checkboxes to radios & trigger get_terms() filter.
		add_filter( 'wp_terms_checklist_args', array( $this, 'filter_terms_checklist_args' ) );

		// Add ajax callback for adding a non-hierarchical term
		if( Radio_Buttons_for_Taxonomies()->is_wp_version_gte('4.4.0') ) {
			add_action( 'wp_ajax_add-' . $taxonomy, array( $this, 'add_non_hierarchical_term' ), 5 );	
		}

		// Never save more than 1 term.
		add_action( 'save_post', array( $this, 'save_single_term' ) );
		add_action( 'edit_attachment', array( $this, 'save_single_term' ) );

		// Hack global taxonomy to switch all radio taxonomies to hierarchical on edit screen.
		add_action( 'load-edit.php', array( $this, 'make_hierarchical' ) );
		add_action( 'wp_ajax_inline-save', array( $this, 'make_hierarchical' ), 0 );
		
	}


	/**
	 * Remove old metabox and add new customized metabox
	 *
	 * @since 2.3.0
	 *
	 * @param  string $post_type - The post type we are fetching metaboxes for. Used by Tabify Edit Screen.
	 */
	public function replace_meta_box( $post_type = '' ) {

		if ( ! $post_type ) {
			$screen = get_current_screen();
			$post_type = $screen instanceof WP_Screen ? $screen->post_type : '';	
		}
		
		if( '' !== $post_type && ! is_wp_error( $this->tax_obj ) && isset( $this->tax_obj->object_type ) ) {

			// Get posttypes this taxonomy is connected to.
			$posttypes = $this->tax_obj->object_type;
			
			// Do not iterate over post types in tax object. Only check against current screen.
			if( ! function_exists( 'use_block_editor_for_post_type' ) || ! use_block_editor_for_post_type( $post_type ) ) {
				// Check if needed.
				if (
					empty( $this->taxonomy ) || empty( $post_type )
					|| ! isset( $posttypes)
					|| ! isset( $post_type )
					|| ! in_array( $post_type,  $posttypes )
				) {
					return;
				}

				// Metaboxes to remove.
				$remove_id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'tagsdiv-'. $this->taxonomy : $this->taxonomy .'div' ;

				remove_meta_box( $remove_id, $post_type, 'side' );

				// Metaboxes to add.
				$add_id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'radio-tagsdiv-' . $this->taxonomy : 'radio-' . $this->taxonomy . 'div' ;
				
				add_meta_box( $add_id, $this->tax_obj->labels->name, array( $this,'metabox' ), $post_type , 'side', 'core', array( 'taxonomy'=> $this->taxonomy ) );
			}
		}
	}


	/**
	 * Callback to set up the metabox
	 * Mimicks the traditional hierarchical term metabox, but modified with our nonces 
	 *
	 * @since 1.0.0
	 * 	 
	 * @param  object $post
	 * @param  array $args
	 */
	public function metabox( $post, $box ) {

		$defaults = array( 'taxonomy' => 'category' );
		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) ) {
			$args = array();
		} else {
			$args = $box['args'];
		}

		$r = wp_parse_args( $args, $defaults );
		$tax_name = esc_attr( $r['taxonomy'] );
		$taxonomy = get_taxonomy( $r['taxonomy'] );

		// Get current terms.
		$checked_terms = isset( $post->ID ) ? get_the_terms( $post->ID, $tax_name ) : array();

		// Get first term, a single term.
		$single_term = ! empty( $checked_terms ) && ! is_wp_error( $checked_terms ) ? array_pop( $checked_terms ) : false;
		$single_term_id = $single_term ? (int) $single_term->term_id : 0;

		?>
		<div id="taxonomy-<?php echo $tax_name; ?>" class="radio-buttons-for-taxonomies categorydiv">
			<ul id="<?php echo $tax_name; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $tax_name; ?>-all"><?php echo $taxonomy->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $tax_name; ?>-pop"><?php echo esc_html( $taxonomy->labels->most_used ); ?></a></li>
			</ul>
		
			<div id="<?php echo $tax_name; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $tax_name; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php 

						$popular_terms = get_terms( $tax_name, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );
						$popular_ids = array(); 

						foreach( $popular_terms as $term ) {

							$popular_ids[] = $term->term_id;
							$value = is_taxonomy_hierarchical( $tax_name ) ? $term->term_id : $term->slug;
							$id = 'popular-' . $tax_name . '-' . $term->term_id;
							$checked = checked( $single_term_id, $term->term_id, false );
							?>

							<li id="<?php echo $id; ?>" class="popular-category">
								<label class="selectit">
									<input id="in-<?php echo $id; ?>" type="radio" <?php echo $checked; ?> value="<?php echo (int) $term->term_id; ?>" <?php disabled( ! current_user_can( $taxonomy->cap->assign_terms ) ); ?> />
										<?php
										/** This filter is documented in wp-includes/category-template.php */
										echo esc_html( apply_filters( 'the_category', $term->name, '', '' ) );
										?>
								</label>
							</li>

					<?php } ?>
				</ul>
			</div>

			<div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
				<ul id="<?php echo $tax_name; ?>checklist" data-wp-lists="list:<?php echo $tax_name; ?>" class="categorychecklist form-no-clear">
					<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $tax_name, 'popular_cats' => $popular_ids, 'selected_cats' => array( $single_term_id ) ) ); ?>
				</ul>
			</div>
			
		<?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
			<div id="<?php echo $tax_name; ?>-adder" class="wp-hidden-children">
				<a id="<?php echo $tax_name; ?>-add-toggle" href="#<?php echo $tax_name; ?>-add" class="hide-if-no-js taxonomy-add-new">
					<?php
						/* translators: %s: add new taxonomy label */
						printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
					?>
				</a>
				<p id="<?php echo $tax_name; ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo $tax_name; ?>" id="new<?php echo $tax_name; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo $tax_name; ?>_parent">
						<?php echo $taxonomy->labels->parent_item_colon; ?>
					</label>
					<?php

					// Only add parent option for hierarchical taxonomies.
					if( is_taxonomy_hierarchical( $tax_name ) ) {
						$parent_dropdown_args = array(
							'taxonomy'         => $tax_name,
							'hide_empty'       => 0,
							'name'             => 'new' . $tax_name . '_parent',
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
						);

						/**
						 * Filters the arguments for the taxonomy parent dropdown on the Post Edit page.
						 *
						 * @since 4.4.0
						 *
						 * @param array $parent_dropdown_args {
						 *     Optional. Array of arguments to generate parent dropdown.
						 *
						 *     @type string   $taxonomy         Name of the taxonomy to retrieve.
						 *     @type bool     $hide_if_empty    True to skip generating markup if no
						 *                                      categories are found. Default 0.
						 *     @type string   $name             Value for the 'name' attribute
						 *                                      of the select element.
						 *                                      Default "new{$tax_name}_parent".
						 *     @type string   $orderby          Which column to use for ordering
						 *                                      terms. Default 'name'.
						 *     @type bool|int $hierarchical     Whether to traverse the taxonomy
						 *                                      hierarchy. Default 1.
						 *     @type string   $show_option_none Text to display for the "none" option.
						 *                                      Default "&mdash; {$parent} &mdash;",
						 *                                      where `$parent` is 'parent_item'
						 *                                      taxonomy label.
						 * }
						 */
						$parent_dropdown_args = apply_filters( 'post_edit_category_parent_dropdown_args', $parent_dropdown_args );

						wp_dropdown_categories( $parent_dropdown_args );

					}
					?>
					<input type="button" id="<?php echo $tax_name; ?>-add-submit" data-wp-lists="add:<?php echo $tax_name; ?>checklist:<?php echo $tax_name; ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
					<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
					<span id="<?php echo $tax_name; ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>	
		</div>
	<?php
	}


	/**
	 * Tell checklist function to use our new Walker
	 *
	 * @since 1.1.0
	 * 
	 * @param  array $args
	 * @return array
	 */
	public function filter_terms_checklist_args( $args ) {

		// Define our custom Walker.
		if( isset( $args['taxonomy']) && $this->taxonomy == $args['taxonomy'] ) {

			// Add a filter to get_terms() but only for radio lists.
			$this->set_terms_filter( true );
			add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );

			$args['walker'] = new Walker_Category_Radio;
		}
		return $args;
	}


	/**
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @since 1.7.0
	 * 
	 * @param  bool $_set
	 * @return bool
	 * 
	 */
	private function switch_terms_filter( $_set = NULL ) {
		_deprecated_function( __FUNCTION__, '1.8.0', 'WordPress_Radio_Taxonomy::set_terms_filter() or WordPress_Radio_Taxonomy::get_terms_filter()' );

		if ( ! is_null( $_set ) ) $this->set = $_set;

		// Give users a chance to disable the no term feature.
		return apply_filters( 'radio-buttons-for-taxonomies-no-term-' . $this->taxonomy, $this->set );
	}

	/**
	 * Turn on/off the terms filter.
	 * 
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @since 1.7.0
	 * 
	 * @param  bool $_set
	 * @return bool
	 */
	private function set_terms_filter( $_set = true ) {
		$this->set = (bool) $_set;
	}

	/**
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @since 1.7.0
	 * 
	 * @param  bool $_set
	 * @return bool
	 */
	private function get_terms_filter() {
		// Give users a chance to disable the no term feature.
		return apply_filters( 'radio_buttons_for_taxonomies_no_term_' . $this->taxonomy, $this->set );
	}


	/**
	 * Add new 0 or null term in metabox and quickedit
	 * this will allow users to "undo" a term if the taxonomy is not required
	 *
	 * @since 1.4.0
	 *
     * @param array         $terms      Array of found terms.
     * @param array         $taxonomies An array of taxonomies.
     * @param array         $args       An array of get_terms() arguments.
	 * @return array
	 */
	function get_terms( $terms, $taxonomies, $args ) {

		// Only filter terms for radio taxes (except category) and only in the checkbox - need to check $args b/c get_terms() is called multiple times in wp_terms_checklist()
		if( in_array( $this->taxonomy, ( array ) $taxonomies ) && isset( $args['fields'] ) && $args['fields'] == 'all' ) {

			$default_term = intval( get_option( 'default_' . $this->taxonomy, 0 ) );
			
			if ( ! $default_term && $this->get_terms_filter() ) {

				// Remove filter after 1st run.
				remove_filter( current_filter(), __FUNCTION__, 10, 3 );

				// Turn the switch OFF.
				$this->set_terms_filter( false ); 

				$no_term = sprintf( __( 'No %s', 'radio-buttons-for-taxonomies' ), $this->tax_obj->labels->singular_name );
				$no_term = apply_filters( 'radio_buttons_for_taxonomies_no_term_selected_text', $no_term, $this->tax_obj->labels->singular_name );

				$uncategorized = (object) array( 'term_id' => '0', 'slug' => '0', 'name' => $no_term, 'parent' => '0' );

				array_push( $terms, $uncategorized );

			}

		}

		return $terms;
	}


	/**
	 * Add new term from metabox
	 * Mimics _wp_ajax_add_hierarchical_term() but modified for non-hierarchical terms
	 *
	 * @since 1.7.0
	 * 
	 * @return data for WP_Lists script
	 */
	public function add_non_hierarchical_term() {
		$action = $_POST[ 'action' ];
		$tax_name = substr( $action, 4 );

		// If Hierarchical, pass-through to core callback.
		if( is_taxonomy_hierarchical( $tax_name ) ) {
			return false;
		}

		// Non-Hierarchical "Terms".
		$taxonomy = get_taxonomy( $tax_name );
		check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );
		if ( ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			wp_die( -1 );
		}
		$names = explode( ',', $_POST['new'.$taxonomy->name] );

		foreach ( $names as $cat_name ) {
			$cat_name = trim($cat_name);
			$category_nicename = sanitize_title($cat_name);
			if ( '' === $category_nicename ) {
				continue;
			}

			if ( ! $cat_id = term_exists( $cat_name, $taxonomy->name ) ) {
				$cat_id = wp_insert_term( $cat_name, $taxonomy->name );
			}
				
			if ( is_wp_error( $cat_id ) ) {
				continue;
			}
			else if ( is_array( $cat_id ) ) {
				$cat_id = $cat_id['term_id'];
			}

			$data = sprintf( '<li id="%1$s-%2$s"><label class="selectit"><input id="in-%1$s-%2$s" type="radio" name="radio_tax_input[%1$s][]" value="%2$s" checked="checked"> %3$s</label></li>',
				esc_attr( $taxonomy->name ),
				intval( $cat_id ),
				esc_html( $cat_name )
			);

			$add = array(
				'what' => $taxonomy->name,
				'id' => $cat_id,
				'data' => str_replace( array("\n", "\t"), '', $data),
				'position' => -1
			);
		}

		$x = new WP_Ajax_Response( $add );
		$x->send();
	}



	/**
	 * Only ever save a single term
	 *
	 * @since 1.1.0
	 *
	 * @param  int $post_id
	 * @return int
	 */
	function save_single_term( $post_id ) {

		// Verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Prevent weirdness with multisite.
		if( function_exists( 'ms_is_switched' ) && ms_is_switched() ) {
			return $post_id;
		}

		// Make sure we're on a supported post type.
		if ( is_array( $this->tax_obj->object_type ) && isset( $_REQUEST['post_type'] ) && ! in_array ( $_REQUEST['post_type'], $this->tax_obj->object_type ) ) {
			return $post_id;
		}

		// Check capabilities.
		if ( ! current_user_can( $this->tax_obj->cap->assign_terms ) ) {
			return $post_id;
		}

		// If posts are being bulk edited, and no term is selected, do nothing.
		if ( ! empty( $_GET[ 'bulk_edit' ] ) && empty ( $_REQUEST[ 'radio_tax_input' ][ "{$this->taxonomy}" ] ) ) {
			return $post_id;
		}

		// Verify nonce.
		if ( ! isset( $_REQUEST["_radio_nonce-{$this->taxonomy}"]) || ! wp_verify_nonce( $_REQUEST["_radio_nonce-{$this->taxonomy}"], "radio_nonce-{$this->taxonomy}" ) ) {
			return $post_id;
		}

		// OK, we must be authenticated by now: we need to make sure we're only saving 1 term.
		if ( ! empty ( $_REQUEST["radio_tax_input"]["{$this->taxonomy}"] ) ) {
			$terms = (array) $_REQUEST["radio_tax_input"]["{$this->taxonomy}"];
			$single_term = intval( array_shift( $terms ) );
		} else {
			// If not saving any terms, set to default.
			$single_term = intval( get_option( 'default_' . $this->taxonomy, 0 ) );
		}

		// Set the single terms.
		wp_set_object_terms( $post_id, $single_term, $this->taxonomy );

		return $post_id;
	}


	/**
	 * Use this action to switch all radio taxonomies to hierarchical on edit.php
	 * at the moment, there is no filter, so we have to hack the global variable
	 *
	 * @since 1.7.0
	 * 
	 * @param  array $columns
	 * @return array
	 */
	public function make_hierarchical() {
		global $wp_taxonomies;
		$wp_taxonomies[$this->taxonomy]->hierarchical = TRUE;
	}

		
	/**
	 * Add nonces to quick edit and bulk edit
	 *
	 * @since 1.7.0
	 * @deprecated 2.2.0
	 */
	public function quick_edit_nonce() {

		_deprecated_function( __FUNCTION__, '2.2.0', 'Nonce is displayed automatically by the custom Walker.' );

		if ( $this->printNonce ) {
			$this->printNonce = FALSE;
			wp_nonce_field( 'radio_nonce-' . $this->taxonomy, '_radio_nonce-' . $this->taxonomy );
		}
		
	}

	/**
	 * Remove the default metabox
	 *
	 * @since 1.0.0
	 * @deprecated 2.3.0
	 */
	public function remove_meta_box() {
		_deprecated_function( __METHOD__ . '()', '2.3.0', 'WordPress_Radio_Taxonomy::replace_meta_box()' );
		return $this->replace_meta_box();
	}

	/**
	 * Add our new customized metabox
	 * 
	 * @since 1.0.0
	 * @deprecated 2.3.0
	 */
	public function add_meta_box() {
		_deprecated_function( __METHOD__ . '()', '2.3.0', 'WordPress_Radio_Taxonomy::replace_meta_box()' );
		return $this->replace_meta_box();
	}

} // End class - do NOT remove or else
endif;
