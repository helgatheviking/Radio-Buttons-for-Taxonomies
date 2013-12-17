<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'WordPress_Radio_Taxonomy' ) ) :

class WordPress_Radio_Taxonomy {

	public $taxonomy = null;
	public $tax_obj = null;

	public function __construct( $taxonomy ){

		$this->taxonomy = $taxonomy;

		//get the taxonomy object - need to get it after init but before admin_menu
		add_action( 'wp_loaded', array( $this, 'get_taxonomy' ) );

		//Remove old taxonomy meta box
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		//Add new taxonomy meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		//change checkboxes to radios
		add_filter( 'wp_terms_checklist_args', array( $this, 'filter_terms_checklist_args' ) );

		// add a null term to metaboxes so users can unset term
		add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );

		//Ajax callback for adding a non-hierarchical term
		add_action( 'wp_ajax_radio_tax_add_taxterm', array( $this, 'ajax_add_term' ) );

		//disable the UI for non-hierarchical taxonomies that are using radio buttons on EDIT screen - irrelevant in 3.4.2
		add_action( 'load-edit.php', array( $this, 'disable_ui' ) );

		//add columns to the edit screen
		add_filter( 'admin_init', array( $this, 'add_columns_init' ), 20 );

		//never save more than 1 term ( possibly overkill )
		add_action( 'save_post', array( $this, 'save_taxonomy_term' ) );

		//add to quick edit - irrelevant for wp 3.4.2
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2);

	}

	/**
	 * Set up the taxonomy object
	 * need to do this after all custom taxonomies are registered
	 *
	 * @since 1.1
	 */
	public function get_taxonomy(){
		$this->tax_obj = get_taxonomy( $this->taxonomy );
	}

	/**
	 * Remove the default metabox
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 *
	 * @since 1.0
	 */
	public function metabox( $post, $box ) {
		$defaults = array('taxonomy' => 'category');
		if ( !isset($box['args']) || !is_array($box['args']) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );

		$tax = get_taxonomy($taxonomy);

		//get current terms
		$checked_terms = $post->ID ? get_the_terms( $post->ID, $taxonomy) : array();

		//get first term object
      $current_term = ! empty( $checked_terms ) && ! is_wp_error( $checked_terms ) ? array_pop( $checked_terms ) : false;
      $current_id = ( $current_term ) ? $current_term->term_id : '';

		?>
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="radio-buttons-for-taxonomies">
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
			</ul>

			<style>
				.radio-buttons-for-taxonomies ul.categorychecklist, .radio-buttons-for-taxonomies ul.tagchecklist { margin: 0; }
				.radio-buttons-for-taxonomies ul.children { margin-left: 18px; }
			</style>

			<?php wp_nonce_field( 'radio_nonce-' . $taxonomy, '_radio_nonce-' . $taxonomy ); ?>

			<div id="<?php echo $taxonomy; ?>-pop" class="wp-tab-panel tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="<?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'categorychecklist'; } else { echo 'tagchecklist';} ?> form-no-clear" >
					<?php $popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

						if ( ! current_user_can($tax->cap->assign_terms) )
							$disabled = 'disabled="disabled"';
						else
							$disabled = '';

						$popular_ids = array(); ?>

						<?php foreach( $popular as $term ){

							$popular_ids[] = $term->term_id;

					      $value = is_taxonomy_hierarchical( $taxonomy ) ? $term->term_id : $term->slug;
					      $id = 'popular-'.$taxonomy.'-'.$term->term_id;

					      echo "<li id='$id'><label class='selectit'>";
					      echo "<input type='radio' id='in-{$id}'" . checked( $current_id, $term->term_id, false ) . " value='{$value}' {$disabled} />&nbsp;{$term->name}<br />";

					      echo "</label></li>";
						} ?>
				</ul>
			</div>

			<div id="<?php echo $taxonomy; ?>-all" class="wp-tab-panel tabs-panel">
				<?php
	            $name = 'radio_tax_input[' . $taxonomy . ']';
	            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
	            ?>
				<ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy?>" class="<?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'categorychecklist'; } else { echo 'tagchecklist';} ?> form-no-clear">
					<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
				</ul>
			</div>
		<?php if ( current_user_can( $tax->cap->edit_terms ) ) : ?>
				<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
					<h4>
						<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
							<?php
								/* translators: %s: add new taxonomy label */
								printf( __( '+ %s' ), $tax->labels->add_new_item );
							?>
						</a>
					</h4>
					<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" aria-required="true"/>
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
							<?php echo $tax->labels->parent_item_colon; ?>
						</label>
						<?php if( is_taxonomy_hierarchical($taxonomy) ) {
							wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) );
						} ?>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button <?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'category-add-submit'; } else { echo 'radio-add-submit';} ?>" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
						<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php
	}

	/**
	 * tell checklist function to use our new Walker
	 *
	 * @since 1.1
	 */
	function filter_terms_checklist_args( $args ) {
	    if( isset($args['taxonomy']) && $this->taxonomy == $args['taxonomy'] ) {
	    	$args['walker'] = new Walker_Category_Radio;
	    	$args['checked_ontop'] = false;
	    }
	    return $args;
	}


	/**
	 * Only ever save a single term
	 *
	 * @since 1.1
	 */
	function save_taxonomy_term ( $post_id ) {

		// make sure we're on a supported post type
	    if ( is_array( $this->tax_obj->object_type ) && isset( $_POST['post_type'] ) && ! in_array ( $_POST['post_type'], $this->tax_obj->object_type ) ) return;

    	// verify this came from our plugin - one of our nonces must be set
	 	if ( ! isset( $_POST["_radio_nonce-{$this->taxonomy}"]) && ! isset( $_POST["_ajax_nonce-add-{$this->taxonomy}"]) ) return;

	 	// verify the nonce if this is an ajax "add term" action
	 	if ( isset( $_POST["_ajax_nonce-add-{$this->taxonomy}"]) && ! wp_verify_nonce( $_POST["_ajax_nonce-add-{$this->taxonomy}"], "add-{$this->taxonomy}" ) ) return;

	 	// verify the nonce if we're just saving the post normally
	 	if ( isset( $_POST["_radio_nonce-{$this->taxonomy}"]) && ! wp_verify_nonce( $_POST["_radio_nonce-{$this->taxonomy}"], "radio_nonce-{$this->taxonomy}" ) ) return;

	  	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	  	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
	    	return $post_id;

	  	// Check permissions
	  	if ( 'page' == $_POST['post_type'] ) {
	    	if ( ! current_user_can( 'edit_page', $post_id ) ) return;
	  	} else {
	    	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	  	}

	 	$terms = null;

	  	// OK, we're authenticated: we need to find and save the data
	  	if ( isset ( $_POST["radio_tax_input"]["{$this->taxonomy}"] ) )
	  		$terms = $_POST["radio_tax_input"]["{$this->taxonomy}"];


	  	// should always be an array because WP saves a hidden 0 term

	  if ( is_array( $terms ) ) {

	  		// magically removes "0" terms
	  		$terms = array_filter( $terms );

	  		// make sure we're only saving 1 term
	  		$terms = ( array ) array_shift( $terms );

	  		// if hierarchical we need to ensure integers!
	  		if ( is_taxonomy_hierarchical( $this->taxonomy ) ) { $terms = array_map( 'intval', $terms ); }

	  	} else {

	  		// if somehow user is saving string of tags, split string and grab first
	  		$terms = explode( ',' , $terms ) ;
		   $terms = array_map( array( $this, 'array_map'), $terms );
		   $terms = $terms[0];

	  	}

	  	// if category and not saving any terms, set to default
	  	if ( 'category' == $this->taxonomy && empty ( $terms ) ) {
	  		$terms = intval( get_option( 'default_category' ) );
	  	}

	  	// set the single term
		wp_set_object_terms( $post_id, $terms, $this->taxonomy );

		return $post_id;
	}


	/**
	 * Callback for array_map
	 * since anonymous function isn't supported until PHP 5.3
	 *
	 * @since 1.1.2
	 */
	private function array_map ( $n ) {
		return trim( $n );
	}


	/**
	 * Add new 0 or null term in metabox and quickedit
	 * this will allow users to "undo" a term if the taxonomy is not required
	 *
	 * @since 1.4
	 */
	function get_terms ( $terms, $taxonomies, $args ){

		// give users a chance to disable the no term feature
		if( ! apply_filters( 'radio-buttons-for-taxonomies-no-term-' . $this->taxonomy, TRUE ) )
			return $terms;

		if ( is_admin() && function_exists( 'get_current_screen' ) && ! is_wp_error( $screen = get_current_screen() ) && is_object( $screen ) && in_array( $screen->base, array( 'post', 'edit-post', 'edit' ) ) ) {

			if( in_array( $this->taxonomy, ( array ) $taxonomies ) && ! in_array( 'category', $taxonomies ) ) {

				$no_term = sprintf( __( 'No %s', 'radio-buttons-for-taxonomies' ), $this->tax_obj->labels->singular_name );

				$uncategorized = (object) array( 'term_id' => '0', 'slug' => '0', 'name' => $no_term, 'parent' => '0' );

				$terms['null'] = $uncategorized;
			}
		}
		return $terms;
	}

	/**
	 * Add new term from metabox
	 *
	 * @since 1.0
	 */
	public function ajax_add_term(){

		$taxonomy = ! empty( $_POST['taxonomy'] ) ? $_POST['taxonomy'] : '';
		$term = ! empty( $_POST['term'] ) ? $_POST['term'] : '';
		$tax = $this->tax_obj;

		check_ajax_referer( 'add-'.$taxonomy, 'wpnonce_radio-add-term' );

		//term already exists
		if ( $tag = term_exists( $term, $taxonomy ) ) {
			echo json_encode( array(
								'hasterm'=> $tag['term_id'], 'term' => $term, 'taxonomy'=>$taxonomy )
							);
			exit();
		}

		//ok at this point we can add the new term
		$tag = wp_insert_term( $term, $taxonomy );

		//in theory, by now we shouldn't have any errors, but just in case
		if ( ! $tag || is_wp_error( $tag ) || ( ! $tag = get_term( $tag['term_id'], $taxonomy ) ) ) {
			echo json_encode( array(
								'error'=> $tag->get_error_message()
								) );
			exit();
		}

		//if all is successful, build the new radio button to send back
		$id = $taxonomy.'-'.$tag->term_id;
		$name = 'radio_tax_input[' . $taxonomy . ']';

		$html ='<li id="'.$id.'"><label class="selectit"><input type="radio" id="in-'.$id.'" name="'.$name.'" value="' . $tag->slug .'" checked="checked"/>&nbsp;'. $tag->name.'</label></li>';

		echo json_encode( array(
			'term'=>$tag->term_id,
			'html'=>$html
			) );
		exit();
	}

	/**
	 * Disable the UI for radio taxonomies, but only on EDIT screen
	 * which prevents them from appearing in quick edit
	 *
	 * @since 1.1
	 */
	public function disable_ui(){
		global $wp_taxonomies;
		$wp_taxonomies[$this->taxonomy]->show_ui = FALSE;
	}


	/**
	 * Add extra columns for radio taxonomies on the edit screen
	 *
	 * @since 1.1
	 */
	function add_columns_init() {

		// don't add the column for any taxonomy that has specifically
		// disabled showing the admin column when registering the taxonomy
		if( ! isset( $this->tax_obj->show_admin_column ) || ! $this->tax_obj->show_admin_column )
				return;

		// also grab all the post types the tax is registered to
		if( isset( $this->tax_obj->object_type ) && is_array( $this->tax_obj->object_type ) ) foreach ( $this->tax_obj->object_type as $post_type ){

			//remove taxonomy columns - does not exist in 3.4.2
			//add_filter( "manage_taxonomies_for_{$post_type}_columns", array($this,'remove_tax_columns'), 10, 2 );

			//add some hidden data that we'll need for the quickedit
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_tax_columns' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'custom_tax_columns' ), 99, 2);

		}

	}

	/**
	 * Remove the existing columns
	 *
	 * @since 1.1
	 * Waiting for manage_taxonomies_for filter in WP 3.5
	 */
	function remove_tax_columns( $taxonomies, $post_type ) {
		unset( $taxonomies[$this->taxonomy] );
		return $taxonomies;
	}

	/**
	 * Add New Custom Columns
	 *
	 * @since 1.1
	 */
	function add_tax_columns( $columns ) {

		/* keep trickery for post_tag and category' replacement until WP 3.5 */
		switch ( $this->taxonomy ) {
			case 'post_tag' :
				$json = str_replace("tags", "radio-{$this->taxonomy}" , json_encode($columns));
   				$columns = json_decode($json, true);
				break;
			case 'category' :
				$json = str_replace("categories", "radio-{$this->taxonomy}" , json_encode($columns));
    			$columns = json_decode($json, true);
    			break;
    		default:
    			// until the manage_taxonomies filter is added, unset from $columns
    			// only works on columns that are added with default naming convention
				if( isset( $columns["taxonomy-{$this->taxonomy}"] ) )
					unset( $columns["taxonomy-{$this->taxonomy}"] );

				$columns["radio-{$this->taxonomy}"] = $this->tax_obj->labels->singular_name;
				break;
		}
		return $columns;
	}

	/**
	 * New Custom Column content
	 *
	 * @since 1.1
	 */
	function custom_tax_columns( $column, $post_id ) {
		global $post;

		switch ( $column ) {
			case "radio-{$this->taxonomy}":
				if ( $terms = wp_get_object_terms( $post_id, $this->taxonomy ) ) {
						$out = array();
						$hidden = array();
						foreach ( $terms as $t ) {
							if ( count ( $out ) == 1 ) break; //break out of this foreach at 1 term only (is filtering get_the_terms better?
							$posts_in_term_qv = array();
							if ( 'post' != $post->post_type )
								$posts_in_term_qv['post_type'] = $post->post_type;
							if ( $this->tax_obj->query_var ) {
								$posts_in_term_qv[ $this->tax_obj->query_var ] = $t->slug;
							} else {
								$posts_in_term_qv['taxonomy'] = $taxonomy;
								$posts_in_term_qv['term'] = $t->slug;
							}

							$out[] = sprintf( '<a href="%s">%s</a>',
								esc_url( add_query_arg( $posts_in_term_qv, 'edit.php' ) ),
								esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $this->taxonomy, 'display' ) )
							);

							$hidden[] = is_taxonomy_hierarchical( $this->taxonomy ) ? $t->term_id : $t->slug;
						}
						/* translators: used between list items, there is a space after the comma */
						echo join( __( ', ' ), $out );
						//redo this when wp 3.5 is available
						echo '<div id="' . $this->taxonomy . '-' . $post_id.'" class="hidden radio-value '. $this->taxonomy . '">' . join( __( ', ' ), $hidden ) . '</div>';
					} else {
						/* translators: No 'terms' where %s is the taxonomy singular name */
						printf( __( 'No %s', 'radio-buttons-for-taxonomies' ) , $this->tax_obj->labels->singular_name );
					}
				break;
		}

	}

	/**
	 * Quick edit form
	 *
	 * @since 1.1
	 */
	function quick_edit_custom_box( $column_name, $screen ) {

		if ( ! is_array( $this->tax_obj->object_type ) || ! in_array ( $screen, $this->tax_obj->object_type ) || $column_name != 'radio-' . $this->taxonomy )
			return false;

	    //needs the same name as metabox nonce
	    wp_nonce_field( "add-{$this->taxonomy}", "_ajax_nonce-add-{$this->taxonomy}", false );

	    ?>

		<fieldset class="inline-edit-col-left inline-edit-categories">
			<div class="inline-edit-col">
				<span class="title inline-edit-categories-label"><?php echo esc_html( $this->tax_obj->labels->name ) ?>
					<span class="catshow"><?php _e( '[more]' ); ?></span>
					<span class="cathide" style="display:none;"><?php _e( '[less]' ); ?></span>
				</span>
				<input type="hidden" name="<?php echo 'radio_tax_input[' . esc_attr( $this->taxonomy ) . '][]'; ?>" value="0" />
				<ul id="<?php echo $this->taxonomy ?>" class="radio-checklist cat-checklist <?php echo esc_attr( $this->tax_obj->labels->name )?>-checklist">
					<?php wp_terms_checklist( null, array( 'taxonomy' => $this->taxonomy ) ) ?>
				</ul>
			</div>
		</fieldset>
		<?php
	}

} //end class - do NOT remove or else
endif;