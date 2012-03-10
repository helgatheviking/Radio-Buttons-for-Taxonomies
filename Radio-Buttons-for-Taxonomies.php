<?php
/*
Author: Stephen Harris http://profiles.wordpress.org/stephenh1988/
Github: https://github.com/stephenh1988

This is a class implementation of the wp.tuts+ tutorial: http://wp.tutsplus.com/tutorials/creative-coding/how-to-use-radio-buttons-with-taxonomies/

To use it, just add to your functions.php and add the javascript file to your theme’s js folder (call it radiotax.js). 

Better still, make make a plug-in out of it, including the javascript file., and being sure to point the wp_register_script to radiotax.js in your plug-in folder.

The class constants are 
  - taxonomy: the taxonomy slug
  - taxonomy_metabox_id: the ID of the original taxonomy metabox
  - post type - the post type the metabox appears on
*/
class WordPress_Radio_Taxonomy {
	static $taxonomy = 'mytaxonomy';
	static $taxonomy_metabox_id = 'emytaxonomydiv';
	static $post_type= 'post';

	function load(){
		//Remove old taxonomy meta box  
		add_action( 'admin_menu', 	array(__CLASS__,'remove_meta_box'));  

		//Add new taxonomy meta box  
		add_action( 'add_meta_boxes', array(__CLASS__,'add_meta_box'));  

		//Load admin scripts
		add_action('admin_enqueue_scripts',array(__CLASS__,'admin_script'));
	}


	static function remove_meta_box(){  
   		remove_meta_box(static::$taxonomy_metabox_id, static::$post_type, 'normal');  
	} 


	function add_meta_box() {  
		add_meta_box( 'mytaxonomy_id', 'My Radio Taxonomy',array(__CLASS__,'metabox'), static::$post_type ,'side','core');  
	}  
        

	//Callback to set up the metabox  
	static function metabox( $post ) {  
		//Get taxonomy and terms  
       	 $taxonomy = self::$taxonomy;
      
       	 //Set up the taxonomy object and get terms  
       	 $tax = get_taxonomy($taxonomy);  
       	 $terms = get_terms($taxonomy,array('hide_empty' => 0));  
      
       	 //Name of the form  
       	 $name = 'tax_input[' . $taxonomy . ']';  
      
       	 //Get current and popular terms  
       	 $popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );  
       	 $postterms = get_the_terms( $post->ID,$taxonomy );  
       	 $current = ($postterms ? array_pop($postterms) : false);  
       	 $current = ($current ? $current->term_id : 0);  
       	 ?>  
      
       	 <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">  
      
       	     <!-- Display tabs-->  
       	     <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">  
       	         <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>  
       	         <li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>  
       	     </ul>  
      
       	     <!-- Display taxonomy terms -->  
       	     <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">  
       	         <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">  
       	             <?php   foreach($terms as $term){  
       	                 $id = $taxonomy.'-'.$term->term_id;  
       	                 echo "<li id='$id'><label class='selectit'>";  
       	                 echo "<input type='radio' id='in-$id' name='{$name}'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";  
       	                echo "</label></li>";  
       	             }?>  
       	        </ul>  
       	     </div>  
      
       	     <!-- Display popular taxonomy terms -->  
       	     <div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">  
       	         <ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >  
       	             <?php   foreach($popular as $term){  
       	                 $id = 'popular-'.$taxonomy.'-'.$term->term_id;  
       	                 echo "<li id='$id'><label class='selectit'>";  
       	                 echo "<input type='radio' id='in-$id'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";  
       	                 echo "</label></li>";  
	                    }?>         
       		 </ul>  
       	    </div>  
      
       	 </div>  
        <?php  
    }

	  function admin_script(){  
		wp_register_script( 'radiotax', get_template_directory_uri() . '/js/radiotax.js', array('jquery'), null, true ); // We specify true here to tell WordPress this script needs to be loaded in the footer  
		wp_localize_script( 'radiotax', 'radio_tax', array('slug'=>self::$taxonomy));
		wp_enqueue_script( 'radiotax' );  
	}
}
WordPress_Radio_Taxonomy::load();
?>
