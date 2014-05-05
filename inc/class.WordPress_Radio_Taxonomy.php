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
	public $set;

	/**
	* @var boolean - whether to print Nonce or not
	* @since 1.7.0
	*/
	public $printNonce = true;

	/**
	* Constructor
	* @access public
	* @since 1.0.0
	*/
	public function __construct( $taxonomy ){

		$this->taxonomy = $taxonomy;

		// get the taxonomy object - need to get it after init but before admin_menu
		$this->tax_obj = get_taxonomy( $taxonomy );

		// Remove old taxonomy meta box
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		// Add new taxonomy meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		// change checkboxes to radios & trigger get_terms() filter
		add_filter( 'wp_terms_checklist_args', array( $this, 'filter_terms_checklist_args' ) );

		// Switch ajax callback for adding a non-hierarchical term
		remove_action( 'wp_ajax_add-' . $taxonomy, '_wp_ajax_add_hierarchical_term' );
		add_action( 'wp_ajax_add-' . $taxonomy, array( $this, 'add_non_hierarchical_term' ) );

		// never save more than 1 term
		add_action( 'save_post', array( $this, 'save_single_term' ) );
		add_action( 'edit_attachment', array( $this, 'save_single_term' ) );

		// hack global taxonomy to switch all radio taxonomies to hierarchical on edit screen
		add_action( 'load-edit.php', array( $this, 'make_hierarchical' ) );

		// add nonce to quick edit/bulk edit
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_nonce' ) );	

	}


	/**
	 * Remove the default metabox
	 *
	 * @access public
	 * @return  void
	 * @since 1.0.0
	 */
	public function remove_meta_box() {
		if( ! is_wp_error( $this->tax_obj ) && isset($this->tax_obj->object_type) ) foreach ( $this->tax_obj->object_type as $post_type ):
			$id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'tagsdiv-'.$this->taxonomy : $this->taxonomy .'div' ;
	   		remove_meta_box( $id, $post_type, 'side' );
	   	endforeach;
	}

	/**
	 * Add our new customized metabox
	 *
	 * @access public
	 * @return  void
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		if( ! is_wp_error( $this->tax_obj ) && isset($this->tax_obj->object_type ) ) foreach ( $this->tax_obj->object_type as $post_type ):
			$label = $this->tax_obj->labels->name;
			$id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'radio-tagsdiv-'.$this->taxonomy : 'radio-' .$this->taxonomy .'div' ;
			add_meta_box( $id, $label ,array( $this,'metabox' ), $post_type ,'side','core', array( 'taxonomy'=>$this->taxonomy ) );
		endforeach;
	}


	/**
	 * Callback to set up the metabox
	 * Mimims the traditional hierarchical term metabox, but modified with our nonces 
	 *
	 * @access public
	 * @param  object $post
	 * @param  array $args
	 * @return  print HTML
	 * @since 1.0.0
	 */
	public function metabox( $post, $box ) {
		$defaults = array('taxonomy' => 'category');
		if ( !isset($box['args']) || !is_array($box['args']) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );

		//get current terms
		$checked_terms = $post->ID ? get_the_terms( $post->ID, $taxonomy ) : array();

		//get first term object
		$single_term = ! empty( $checked_terms ) && ! is_wp_error( $checked_terms ) ? array_pop( $checked_terms ) : false;
		$single_term_id = $single_term ? (int) $single_term->term_id : 0;

		?>
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="radio-buttons-for-taxonomies categorydiv form-no-clear">
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $this->tax_obj->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
			</ul>

			<?php wp_nonce_field( 'radio_nonce-' . $taxonomy, '_radio_nonce-' . $taxonomy ); ?>

			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php $popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

						if ( ! current_user_can($this->tax_obj->cap->assign_terms) )
							$disabled = 'disabled="disabled"';
						else
							$disabled = '';

						$popular_ids = array(); ?>

						<?php foreach( $popular as $term ){

							$popular_ids[] = $term->term_id;

							$value = is_taxonomy_hierarchical( $taxonomy ) ? $term->term_id : $term->slug;
							$id = 'popular-'.$taxonomy.'-'.$term->term_id;

							echo "<li id='$id'><label class='selectit'>";
							echo "<input type='radio' id='in-{$id}'" . checked( $single_term_id, $term->term_id, false ) . " value='{$value}' {$disabled} />&nbsp;{$term->name}<br />";

							echo "</label></li>";
						} ?>
				</ul>
			</div>

			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy?>" class="categorychecklist form-no-clear">
					<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
				</ul>
			</div>
		<?php if ( current_user_can( $this->tax_obj->cap->edit_terms ) ) : ?>
				<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
					<h4>
						<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js">
							<?php
								/* translators: %s: add new taxonomy label */
								printf( __( '+ %s' , 'radio-buttons-for-taxonomies' ), $this->tax_obj->labels->add_new_item );
							?>
						</a>
					</h4>
					<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $this->tax_obj->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required" value="<?php echo esc_attr( $this->tax_obj->labels->new_item_name ); ?>" aria-required="true"/>
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
							<?php echo $this->tax_obj->labels->parent_item_colon; ?>
						</label>
						<?php if( is_taxonomy_hierarchical( $taxonomy) ) {
							wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $this->tax_obj->labels->parent_item . ' &mdash;' ) );
						} ?>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $this->tax_obj->labels->add_new_item ); ?>" tabindex="3" />
						<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php
	}


	/**
	 * Tell checklist function to use our new Walker
	 *
	 * @access public
	 * @param  array $args
	 * @return array
	 * @since 1.1.0
	 */
	public function filter_terms_checklist_args( $args ) {

		// define our custom Walker
		if( isset( $args['taxonomy']) && $this->taxonomy == $args['taxonomy'] ) {

			// add a filter to get_terms() but only for radio lists
			$this->switch_terms_filter(1);
			add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );

			$args['walker'] = new Walker_Category_Radio;
			$args['checked_ontop'] = false;
		}
		return $args;
	}


	/**
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @access public
	 * @param  array $args
	 * @return array
	 * @since 1.7.0
	 */
	private function switch_terms_filter( $_set = NULL ) {
		if ( ! is_null( $_set ) ) $this->set = $_set;

		// give users a chance to disable the no term feature
		return apply_filters( 'radio-buttons-for-taxonomies-no-term-' . $this->taxonomy, $this->set );
	}


	/**
	 * Add new 0 or null term in metabox and quickedit
	 * this will allow users to "undo" a term if the taxonomy is not required
	 *
	 * @param  array $terms
	 * @param  array $taxonomies
	 * @param  array $args
	 * @return array
	 * @since 1.4
	 */
	function get_terms( $terms, $taxonomies, $args ){

		// only filter terms for radio taxes (except category) and only in the checkbox - need to check $args b/c get_terms() is called multiple times in wp_terms_checklist()
		if( in_array( $this->taxonomy, ( array ) $taxonomies ) && ! in_array( 'category', $taxonomies ) 
			&& isset( $args['fields'] ) && $args['fields'] == 'all' && $this->switch_terms_filter() === 1 ){

			// remove filter after 1st run
			remove_filter( current_filter(), __FUNCTION__, 10, 3 );

			// turn the switch OFF
			$this->switch_terms_filter(0); 

			$no_term = sprintf( __( 'No %s', 'radio-buttons-for-taxonomies' ), $this->tax_obj->labels->singular_name );

			$uncategorized = (object) array( 'term_id' => '0', 'slug' => '0', 'name' => $no_term, 'parent' => '0' );

			array_push( $terms, $uncategorized );

		}

		return $terms;
	}


	/**
	 * Add new term from metabox
	 * Mimics _wp_ajax_add_hierarchical_term() but modified for non-hierarchical terms
	 *
	 * @return data for WP_Lists script
	 * @since 1.7.0
	 */
	public function add_non_hierarchical_term(){
		$action = $_POST['action'];
		$taxonomy = get_taxonomy(substr($action, 4));
		check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );
		if ( !current_user_can( $taxonomy->cap->edit_terms ) )
			wp_die( -1 );
		$names = explode(',', $_POST['new'.$taxonomy->name]);

		foreach ( $names as $cat_name ) {
			$cat_name = trim($cat_name);
			$category_nicename = sanitize_title($cat_name);
			if ( '' === $category_nicename )
				continue;

			if ( ! $cat_id = term_exists( $cat_name, $taxonomy->name ) )
				$cat_id = wp_insert_term( $cat_name, $taxonomy->name );
				
			if ( is_wp_error( $cat_id ) )
				continue;
			else if ( is_array( $cat_id ) )
				$cat_id = $cat_id['term_id'];

			$data = sprintf( '<li id="%1$s-%2$s"><label class="selectit"><input id="in-%1$s-%2$s" type="radio" name="radio_tax_input[%1$s][]" value="%2$s" checked="checked"> %3$s</label></li>', $taxonomy->name, $cat_id, $cat_name );

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
	 * @param  int $post_id
	 * @return int
	 * @since 1.1.0
	 */
	function save_single_term( $post_id ) {

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;

		// prevent weirdness with multisite
		if( function_exists( 'ms_is_switched' ) && ms_is_switched() )
			return $post_id;

		// make sure we're on a supported post type
		if ( is_array( $this->tax_obj->object_type ) && isset( $_REQUEST['post_type'] ) && ! in_array ( $_REQUEST['post_type'], $this->tax_obj->object_type ) ) 
			return $post_id;

		// verify nonce
		if ( isset( $_POST["_radio_nonce-{$this->taxonomy}"]) && ! wp_verify_nonce( $_REQUEST["_radio_nonce-{$this->taxonomy}"], "radio_nonce-{$this->taxonomy}" ) ) 
			return $post_id;

		// OK, we must be authenticated by now: we need to find and save the data
		if ( isset( $_REQUEST["radio_tax_input"]["{$this->taxonomy}"] ) ){

			$terms = (array) $_REQUEST["radio_tax_input"]["{$this->taxonomy}"]; 

			// if category and not saving any terms, set to default
			if ( 'category' == $this->taxonomy && empty ( $terms ) ) {
				$single_term = intval( get_option( 'default_category' ) );
			}

			// make sure we're only saving 1 term
			$single_term = intval( array_shift( $terms ) );

			// set the single terms
			if ( current_user_can( $this->tax_obj->cap->assign_terms ) ) 
				wp_set_object_terms( $post_id, $single_term, $this->taxonomy );

		}		

		return $post_id;
	}


	/**
	 * Use this action to switch all radio taxonomies to hierarchical on edit.php
	 * at the moment, there is no filter, so we have to hack the global variable
	 *
	 * @param  array $columns
	 * @return array
	 * @since 1.7.0
	 */
	public function make_hierarchical() {
		global $wp_taxonomies;
		$wp_taxonomies[$this->taxonomy]->hierarchical = TRUE;
	}

		
	/**
	 * Add nonces to quick edit and bulk edit
	 *
	 * @return HTML
	 * @since 1.7.0
	 */
	public function quick_edit_nonce() {
		if ( $this->printNonce ) {
			$this->printNonce = FALSE;
			wp_nonce_field( 'radio_nonce-' . $this->taxonomy, '_radio_nonce-' . $this->taxonomy );
		}
		
	}


} //end class - do NOT remove or else
endif;