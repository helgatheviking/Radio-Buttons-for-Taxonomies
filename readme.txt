=== Radio Buttons for Taxonomies ===
Contributors: helgatheviking
Donate link: https://www.paypal.me/kathyisawesome
Tags: taxonomy, admin, interface, ui, post, radio, terms, metabox
Requires at least: 4.5.0
Tested up to: 6.1.0
Stable tag: 2.4.7
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Replace the default taxonomy boxes with a custom metabox that uses radio buttons... effectively limiting each post to a single term in that taxonomy. 

A plugin options page allows the user can select which taxonomies she'd like to switch to using the custom radio-button style metabox.

Originally based on the the [class by Stephen Harris](https://github.com/stephenh1988/Radio-Buttons-for-Taxonomies)

= Support =

Support is handled in the [WordPress forums](http://wordpress.org/support/plugin/radio-buttons-for-taxonomies). Please note that support is limited and does not cover any custom implementation of the plugin. Before posting a question, read the [FAQ](https://wordpress.org/plugins/radio-buttons-for-taxonomies/#faq) and confirm that the problem still exists with a default theme and with all other plugins disabled.

Please report any bugs, errors, warnings, code problems to [Github](https://github.com/helgatheviking/Radio-Buttons-for-Taxonomies/issues)

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings > Radio Buttons for Taxonomies and use the checkboxes to indicate which taxonomies you'd like to use radio buttons

== Screenshots ==

1. The settings page where you determine which taxonomies will get radio buttons.
2. This is what the metabox will look like on the post editor screen.

== Frequently Asked Questions ==

= Why do the metaboxes have a "No {$taxonomy}" ( ex: No Genre ) radio button!? =

This was a feature added in version 1.4, but due to some faulty logic on my part probably wasn't showing it everywhere that I intended.

It has come to my attention that not everybody likes this feature, so I have enabled an easy way to *disable* it for taxonomies that you wish to make mandatory.  Simply add the following to your theme's functions.php or your site's custom functions plugin.

`
add_filter( "radio_buttons_for_taxonomies_no_term_{$taxonomy}", "__return_FALSE" );
`

So for example, to disabled the "No term" option on a taxonomy called "genre" you'd do the following:

`
add_filter( 'radio_buttons_for_taxonomies_no_term_genre', '__return_FALSE' );
`

== Changelog ==

= 2023.03.18 - Version 2.4.7 =
* Manual update to ensure SVN version is the same as GitHub version.

= 2023.02.15 - Version 2.4.6 =
* Fix: Typo from 2.4.5 patch. Closes #111.

= 2023.02.13 - Version 2.4.5 =
* Fix: Reload non-hierarchical terms as forced-hierarchical after quick edit save. Closes #109.

= 2022.06.28 - Version 2.4.4 =
* Fix: Bulk edit removes terms (set to default) when not editing the term. Closes #105.

= 2022.06.15 - Version 2.4.3 =
 * Fix: Fatal error when deleting plugin. uninstall callback must be static. Closes #104.

= 2022.01.06 - Version 2.4.2 =
* Fix: Conflict with default term, multiple checked terms (term plus default term). Closes #103.

= 2021.12.27 - Version 2.4.1 =
* Fix: Fatal error from compatibility module includes.

= 2021.12.27 - Version 2.4.0 =
* Update: Drop support for WordPress < 4.4
* New: Compatibility with WP SEO's primary term (disable for Radio terms)
* New: Save default term if it exists and no term is selected.
* Fix: Default term is checked by default.
* Fix: "No term" is not displayed when taxonomy has a default term option.
* Fix: Missing translations in block editor. Props @ocean90.

= 2.3.1 =
* Fix: Optimize compatibility for Tabify Edit Screen. Props @remcokalf.

Removed 'tabify_add_meta_boxes' action, as it is doubled by do_action( 'add_meta_boxes')in settings-posttype.php of Tabify Edit Screen.
= 2.3.0 =
* New: Optimize to load taxonomy class hooks/callbacks only on radio-enabled taxonomies. Props @remcokalf.
* New: Compatibility for Tabify Edit Screen

= 2.2.3 =
* Fix: Add term for non-hierarchical custom taxonomy in custom post type

= 2.2.2 =
* Fix: Add term for custom taxonomy in custom post type
* Fix: remove jQuery 3 deprecated handlers
* Fix: Don't load the metabox script in the block editor 

= 2.2.1 =
* Fix: Define schema for new REST field added in 2.2.0

= 2.2.0 =
* New: Conditionally show a "no term" radio button in Gutenberg sidebar. props @tomjn!

= 2.1.1 =
* Fix: Missing NONCE preventing bulk/quick edit from saving correctly.
* Fix: Use <RadioControl> in Gutenberg sidebar instead of <input>

= 2.1.0 =
* Fix: Refactor sidebar script to remove unneeded higher order components. Props @torounit!
* Fix: Add core class to radio buttons in sidebar. Props @torounit!

= 2.0.8 =
* Fix: Missing index.asset.php file.

= 2.0.7 =
* Fix: "No {$term}" not translatable.

= 2.0.6 =
* Fix: Vulnerable nonce in save routine.

= 2.0.5 =
* Fix: Admin settings- Get all taxonomies with show_ui=true.

= 2.0.4 =
* Fix: Settings link on plugin page.

= 2.0.3 =
* Fix: Stop breaking quick edit on Taxonomy pages.

= 2.0.2 =
* Update from [Gutenberg source](https://github.com/WordPress/gutenberg/pull/14786)

= 2.0.1 =
* Fix: Fatal error use_block_editor_for_post_type() undefined in WP 4.x

= 2.0.0 =
* Gutenberg compatibility! Props @5ally and @leymannx

= 1.8.3 =
* Escape all the inputs. Props @tomjn and @rtCamp

= 1.8.2 =
* Friendlier error messages

= 1.8.1 =
* Fix: fatal error for undefined boolval in PHP < 5.5

= 1.8.0 =
* Compatibility with WordPress 4.9.2
* Update donation link
* Add radio_buttons_for_taxonomies_no_term_selected_text filter to allow for custom "No Genre" texts. props @hokoo
* Convert switch_terms_filter() to get and setter
* Rename radio-buttons-for-taxonomies-no-term-$taxonomy filter to radio_buttons_for_taxonomies_no_term_$taxonomy to disable the "No term" feature

= 1.7.8 =
* Compatibility with WordPress 4.8.3
* Update donation link

= 1.7.7 =
* Compatibility with WordPress 4.4

= 1.7.6 =
* Validate options against `get_all_taxonomies()` which allows for private taxonomies to be saved in options if they've been added via filter

= 1.7.5 =
* Add Dutch translation. props @maarten00
* Add Persian translation. props @Ghaem

= 1.7.4 =
* Add German translation. props @carstingaxion

= 1.7.3 =
* Use "singular" label for metabox. props @lchski

= 1.7.2 =
* Fixes popular terms

= 1.7.1 =
* add minified script to svn repo

= 1.7.0 =
* Add support for bulk-edit
* more quick edit fixes
* save tax terms for attachments
* switch ajax callback for adding non-hierarchical terms
* use default JS scripts on post.php page
* remove filtering of columns via `manage_{$post_type}_posts_custom_column`, etc
* switch all taxonomies to "hierarchical" on edit.php so quick edit is automatically switched to radio buttons

= 1.6.1 =
* Bug-fix for quick-edit

= 1.6 =
* Use later priority (99) to launch the WordPress_Radio_Taxonomy class instances, resolves bug with custom taxonomies
* Switch to class instance initialization instead of global
* filtering `"manage_taxonomies_for_{$post_type}_columns"` doesn't do anything to quickedit, so removed
* removed `disable_ui()` method in favor of adding to `manage_{$post_type}_posts_custom_column` hook
** this lets us keep the taxonomy columns in their original places, versus adding to end
** currently no way to remove quick edit without disabling UI in global `$wp_taxonomies` variable
* better docbloc

= 1.5.6 =
* fix PHP notice in class.WordPress_Radio_Taxonomy.php

= 1.5.5 =
* verify WP 3.8 compatibility

= 1.5.4 =
* Fix PHP warnings in class.Walker_Category_Radio.php

= 1.5.3 =
* Fix error on edit screen if taxonomy is deleted

= 1.5.2 =
* Fix untranslatable string
* Add Arabic translation thanks to @hassanhamm

= 1.5.1 =
* Load admin scripts only where needed, fixes conflict on edit-terms screens

= 1.5 =
* Move launch of WordPress_Radio_Taxonomy class to init hook
* Move no-term filter inside get_terms() method which should make the proposed FAQ solution for disabling the "No term" work now

= 1.4.5 =
* Enabled "No {$taxonomy}" in quick edit
* Changed column headers to use the singular taxonomy label
* Respect the `show_admin_column` argument when registering taxonomy
* Automatically unset default taxonomy column (if conventionally named) to prevent duplicate columns

= 1.4.4 =
* Change generic "No term" to "No {$taxonomy}", ex: "No Genre"

= 1.4.3 =
* Fix PHP warning in metabox related to "No term"
* Fix conditional logic for "No term" option
* Added filter to disabled "No term"
* Fixed "Add new" term WPLists markup

= 1.4.2 =
* Fix fatal error on settings update

= 1.4.1 =
* Fix "No term" option showing in non-radio taxonomies

= 1.4 =
* Add "No term" option to taxonomy metaboxes

= 1.3 =
* fix problem with adding new terms, #7

= 1.2.5 =
* fix markdown for changelog

= 1.2.4 =
* return changelog to readme.txt

= 1.2.3 =
* fix PHP notice https://github.com/helgatheviking/Radio-Buttons-for-Taxonomies/issues/5
* fix popular/all clicking for WP 3.5.1
* move changelog to own file

= 1.2.2 =
* Still fixing SVN

= 1.2.1 =
* Hopeful fix of SVN failure to include class.Walker_Category_Radio.php in v1.2 - SVN Is not my strong suit. Sorry for any inconvenience!

= 1.2 =
* change donation URL
* fixed save bug for users without the manage_categories

= 1.1.4 =
* Correct plugin URL
* fixed quick edit bug
* fixed undefined $post variable warning

= 1.1.3 =
* Code cleanup

= 1.1.2 =
* Removed unneeded localize_script object
* Fix fatal error on multisite ( caused by using an anonymous function when not supported until PHP 5.3)
* Fixed quick edit refresh ( second click on quick edit for same item and the value still reflected the original)

= 1.1.1 =
* Fix notice in popular terms tab
* Attempted fix fatal error on multisite

= 1.1 =
* Added columns to edit screen for every radio taxonomy
* Add quick edit for all radio taxonomies
* Enforce limit of single terms via save_post
* fixed error with taxonomy object property not being loaded on the right hook
* fixed uninstall hook
* fixed saving of 'delete' option

= 1.0.3 =
* updated donate link

= 1.0.2 =
* fixed incorrect plugin settings link
* fixed variable scope in javascript that was preventing plugin from working on multiple metaboxes

= 1.0.1 =
* bug fix for when no taxonomies are selected

= 1.0 =
* Initial release.
