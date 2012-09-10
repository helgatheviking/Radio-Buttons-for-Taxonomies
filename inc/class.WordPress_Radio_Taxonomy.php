<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists( 'WordPress_Radio_Taxonomy' )) :

class WordPress_Radio_Taxonomy {

	static $taxonomy = null;
	static $tax_obj = null;

	public function __construct( $taxonomy ){

		$this->taxonomy = $taxonomy;

		//get the taxonomy object - need to get it after init but before admin_menu
		add_action( 'wp_loaded', array( &$this, 'get_taxonomy' ) );  

		//disable the UI for non-hierarchical taxonomies that are using radio buttons on EDIT screen
		add_action( 'load-edit.php', array( &$this, 'disable_ui' ) );

		//Remove old taxonomy meta box  
		add_action( 'admin_menu', array( &$this, 'remove_meta_box') );  

		//Add new taxonomy meta box  
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box') );  

		//change checkboxes to radios
		add_filter( 'wp_terms_checklist_args', array( &$this, 'filter_terms_checklist_args' ) );

		//Load admin scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_script' ) );

		//Ajax callback for adding a non-hierarchical term
		add_action( 'wp_ajax_radio_tax_add_taxterm', array( &$this,'ajax_add_term' ) );

		//add columns to the edit screen
		//add_filter( 'wp_loaded', array( &$this,'add_columns_init' ) );
 
		//add_action( 'bulk_edit_custom_box',  array( &$this, 'add_to_bulk_quick_edit_custom_box'), 10, 2 );
		//add_action( 'quick_edit_custom_box',  array( &$this, 'add_to_bulk_quick_edit_custom_box'), 10, 2 );


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
	 * Disable the UI for non-hierarchical radio taxonomies, but only on EDIT screen
	 * which prevents them from appearing in quick edit
	 *
	 * @since 1.1
	 */
	public function disable_ui(){

		if( ! is_taxonomy_hierarchical( $this->taxonomy ) ) {
		    global $wp_taxonomies;
		    $wp_taxonomies[$this->taxonomy]->show_ui = FALSE;
		}
	}

	public function remove_meta_box() {  
		
		if( ! is_wp_error( $this->tax_obj ) && isset($this->tax_obj->object_type) ) foreach ( $this->tax_obj->object_type as $post_type ):
			$id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'tagsdiv-'.$this->taxonomy : $this->taxonomy .'div' ;
	   		remove_meta_box( $id, $post_type, 'side' );  
	   	endforeach; 
	} 

	public function add_meta_box() { 

		if( ! is_wp_error( $this->tax_obj ) && isset($this->tax_obj->object_type ) ) foreach ( $this->tax_obj->object_type as $post_type ):
			$label = $this->tax_obj->labels->name;
			$id = ! is_taxonomy_hierarchical( $this->taxonomy ) ? 'radio-tagsdiv-'.$this->taxonomy : 'radio-' .$this->taxonomy .'div' ;
			add_meta_box( $id, $label ,array( &$this,'metabox' ), $post_type ,'side','core', array( 'taxonomy'=>$this->taxonomy ) ); 
		endforeach; 
	}  
        

	//Callback to set up the metabox  
	public function metabox( $post, $box ) {  
		$defaults = array('taxonomy' => 'category');
		if ( !isset($box['args']) || !is_array($box['args']) )
			$args = array();
		else
			$args = $box['args'];
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );
		$tax = get_taxonomy($taxonomy);

		//get current terms
		$checked_terms = $post->ID ? wp_get_object_terms($post->ID, $taxonomy) : array();
		//get first term object
       	$current = ! empty($checked_terms) && !is_wp_error($checked_terms) ? array_pop($checked_terms) : false;  

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

			<div id="<?php echo $taxonomy; ?>-pop" class="wp-tab-panel tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="<?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'categorychecklist'; } else { echo 'tagchecklist';} ?> form-no-clear" >
					<?php $popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );  

						$popular_ids = array() ?>

						<?php foreach($popular as $term){ 
							$popular_ids[] = $term->term_id;

					        $value = is_taxonomy_hierarchical( $taxonomy ) ? $term->term_id : $term->slug;
					        $id = 'popular-'.$taxonomy.'-'.$term->term_id;

					        echo "<li id='$id'><label class='selectit'>";
					        echo "<input type='radio' id='in-{$id}'" . checked($current->term_id, $term->term_id, false) . " value='{$value}' {$disabled} />&nbsp;{$term->name}<br />";
					        
					        echo "</label></li>";
						}?>
				</ul>
			</div>

			<div id="<?php echo $taxonomy; ?>-all" class="wp-tab-panel tabs-panel">
				<?php
	            $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
	            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
	            ?>
				<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> <?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'categorychecklist'; } else { echo 'tagchecklist';} ?> form-no-clear">
					<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
				</ul>
			</div>
		<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
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
						<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
						<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
							<?php echo $tax->labels->parent_item_colon; ?>
						</label>
						<?php if( is_taxonomy_hierarchical($taxonomy) ) { 
							wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); 
						} ?>
						<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button <?php if ( is_taxonomy_hierarchical ( $taxonomy ) ) { echo 'category-add-submit'; } else { echo 'radio-add-submit';} ?>" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
						<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
						<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php
}

	function filter_terms_checklist_args( $args ) {
		//tell checklist function to use our new Walker
	    if( isset($args['taxonomy']) && $this->taxonomy == $args['taxonomy'] ) { 
	    	$args['walker'] = new Walker_Category_Radio;
	    	$args['checked_ontop'] = false;
	    }
	    return $args;
	}

	public function admin_script(){  
		wp_enqueue_script( 'radiotax', plugins_url('js/radiotax.js', __FILE__), array('jquery'), null, true ); // We specify true here to tell WordPress this script needs to be loaded in the footer  
	}


	public function ajax_add_term(){  

		$taxonomy = ! empty( $_POST['taxonomy'] ) ? $_POST['taxonomy'] : '';
		$term = ! empty( $_POST['term'] ) ? $_POST['term'] : '';
		$tax = $this->tax_obj;

		check_ajax_referer( 'add-'.$taxonomy, '_wpnonce_radio-add-tag' );

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
		$name = 'tax_input[' . $taxonomy . ']';

		$html ='<li id="'.$id.'"><label class="selectit"><input type="radio" id="in-'.$id.'" name="'.$name.'" value="' . $tag->slug .'" checked="checked"/>&nbsp;'. $tag->name.'</label></li>';

		echo json_encode( array( 
			'term'=>$tag->term_id,
			'html'=>$html 
			) );
		exit();
	}

} //end class - do NOT remove

endif;
?>
