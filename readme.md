# Radio Buttons for Taxonomies #
**Contributors:** [helgatheviking](https://profiles.wordpress.org/helgatheviking)  
**Donate link:** https://www.youcaring.com/wnt-residency  
**Tags:** taxonomy, admin, interface, ui, post, radio, terms, metabox  
**Requires at least:** 4.5.0  
**Tested up to:** 5.1.1  
**Stable tag:** 2.0.1  
**License:** GPLv3 or later  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html  

## Description ##

Replace the default taxonomy boxes with a custom metabox that uses radio buttons... effectively limiting each post to a single term in that taxonomy. 

A plugin options page allows the user can select which taxonomies she'd like to switch to using the custom radio-button style metabox.

Originally based on the the [class by Stephen Harris](https://github.com/stephenh1988/Radio-Buttons-for-Taxonomies)

### Support ###

Support is handled in the [WordPress forums](http://wordpress.org/support/plugin/radio-buttons-for-taxonomies). Please note that support is limited and does not cover any custom implementation of the plugin. Before posting a question, read the [FAQ](http://wordpress.org/plugins/nav-menu-roles/faq/) and confirm that the problem still exists with a default theme and with all other plugins disabled.

Please report any bugs, errors, warnings, code problems to [Github](https://github.com/helgatheviking/Radio-Buttons-for-Taxonomies/issues)

## Installation ##

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings > Radio Buttons for Taxonomies and use the checkboxes to indicate which taxonomies you'd like to use radio buttons

## Screenshots ##

1. The settings page where you determine which taxonomies will get radio buttons.
2. This is what the metabox will look like on the post editor screen.

## Frequently Asked Questions ##

### Why do the metaboxes have a "No {$taxonomy}" ( ex: No Genre ) radio button!? ###

This was a feature added in version 1.4, but due to some faulty logic on my part probably wasn't showing it everywhere that I intended.

It has come to my attention that not everybody likes this feature, so I have enabled an easy way to *disable* it for taxonomies that you wish to make mandatory.  Simply add the following to your theme's functions.php or your site's custom functions plugin.


	add_filter( "radio_buttons_for_taxonomies_no_term_{$taxonomy}", "__return_FALSE" );


So for example, to disabled the "No term" option on a taxonomy called "genre" you'd do the following:


	add_filter( 'radio_buttons_for_taxonomies_no_term_genre', '__return_FALSE' );

