<?php 
//
// Category Radio Lists
//

/**
 * Walker to output an unordered list of category radio <input> elements.
 * Mimics Walker_Category_Checklist excerpt for the radio input
 *
 * @see Walker
 * @see wp_category_checklist()
 * @see wp_terms_checklist()
 * @since 2.5.1
 */
class Walker_Category_Radio extends Walker {
    var $tree_type = 'category';
    var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent<ul class='children'>\n";
    }

    function end_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }

    function start_el( &$output, $term, $depth, $args, $id = 0 ) {
        extract($args);
        if ( empty($taxonomy) )
            $taxonomy = 'category';

        $name = 'radio_tax_input['.$taxonomy.']';

        $checked_terms = $post->ID ? get_the_terms( $post->ID, $taxonomy) : array();
        //get first term object
        $current = ! empty( $checked_terms ) && ! is_wp_error( $checked_terms ) ? array_pop( $checked_terms ) : false;  


        //small tweak so that it works for both hierarchical and non-hierarchical tax
        $value = is_taxonomy_hierarchical($taxonomy) ? $term->term_id : $term->slug;

        $class = in_array( $term->term_id, $popular_cats ) ? ' class="popular-category"' : '';
        $output .= "\n<li id='{$taxonomy}-{$value}' $class>" . '<label class="selectit"><input value="' . $value . '" type="radio" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $term->term_id . '"' . checked( $term->term_id, $current->term_id, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $term->name )) . '</label>';
    }

    function end_el( &$output, $term, $depth = 0, $args = array() ) {
        $output .= "</li>\n";
    }
}
