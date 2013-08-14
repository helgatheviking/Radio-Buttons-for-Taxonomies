<div class="wrap">
  <div id="tabs">

  <style>
    #nav-tabs { overflow: hidden; margin: 0 0 -1px 0;}
    #nav-tabs li { float: left; margin-bottom: 0;}
    .ui-tabs-nav a { color: #aaa;}
    #nav-tabs li.ui-state-active a { border-bottom: 2px solid white; color: #464646; }
    h2.nav-tab-wrapper { margin-bottom: 1em;}
  </style>

  <!-- Display Plugin Icon, Header, and Description -->
  <?php screen_icon(); ?>

  <h2><?php _e('Radio Buttons for Taxonomies', 'radio-buttons-for-taxonomies' );?></h2>

  <!-- Beginning of the Plugin Options Form -->
  <form method="post" action="<?php echo admin_url( 'options.php' );?>">
    <?php settings_fields('radio_button_for_taxonomies_options'); ?>
    <?php $options = get_option('radio_button_for_taxonomies_options');?>

    <div id="general">
        <fieldset>
              <table class="form-table">
                    <tr>
                      <th scope="row"><?php _e( 'Select Taxonomies',  'radio-buttons-for-taxonomies' );?></th>
                      <td>

                        <?php

                        $args=array(
                            'public'   => true,
                            'show_ui' => true,
                            '_builtin' => true
                          );

                        $defaults = get_taxonomies( $args, 'objects' );
                        ksort( $defaults );


                        $args=array(
                            'public'   => true,
                            'show_ui' => true,
                            '_builtin' => false
                          );

                        $custom = get_taxonomies( $args, 'objects' );
                        ksort( $custom );


                        $taxonomies = array_merge( $defaults , $custom );

                        if( ! is_wp_error( $taxonomies ) ) {

                          foreach ($taxonomies as $i=>$taxonomy)  { ?>
                            <input type="checkbox" name="radio_button_for_taxonomies_options[taxonomies][]" value="<?php echo $i;?>" <?php checked( isset($options['taxonomies']) && is_array($options['taxonomies']) && in_array($i, $options['taxonomies']), 1 ); ?> /> <?php echo $taxonomy->labels->name; ?><br/>

                          <?php
                              }

                        } ?>

                      </td>
                    </tr>
                    <tr>
                      <th scope="row"><?php _e('Completely remove options on plugin removal', 'radio-buttons-for-taxonomies' );?></th>
                      <td>
                        <input type="checkbox" name="radio_button_for_taxonomies_options[delete]" value="1" <?php checked( isset( $options['delete'] ) && $options['delete'], 1 );?> />
                      </td>
                    </tr>
                  </table>
          </fieldset>
      </div>

          <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'radio-buttons-for-taxonomies' ) ?>" />
          </p>
    </form>
  </div>
</div>