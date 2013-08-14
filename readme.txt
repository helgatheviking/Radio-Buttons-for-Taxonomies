=== Radio Buttons for Taxonomies ===
Contributors: helgatheviking
Donate link: https://inspirepay.com/pay/helgatheviking ‎
Tags: taxonomy, admin, interface, ui, post, radio
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin lets you switch any taxonomy to a radio-button style metabox, so users can only select 1 term at a time.

A plugin options page allows the user can select which taxonomies she'd like to switch to using the custom radio-button style metabox.

Originally based on the the class by Stephen Harris:
https://github.com/stephenh1988/Radio-Buttons-for-Taxonomies

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings>Radio Buttons for Taxonomies and use the checkboxes to indicate which taxonomies you'd like to use radio buttons

== Screenshots ==

1. The settings page where you determine which taxonomies will get radio buttons.
2. This is what the metabox will look like on the post editor screen.

== Bug Reporting ==

Please report any issues at: https://github.com/helgatheviking/Radio-Buttons-for-Taxonomies/issues

== Frequently Asked Questions ==

= Why do the metaboxes have a "No {$taxonomy}" ( ex: No Genre ) radio button!? =

This was a feature added in version 1.4, but due to some faulty logic on my part probably wasn't showing it everywhere that I intended.

It has come to my attention that not everybody likes this feature, so I have enabled an easy way to *disable* it for taxonomies that you wish to make mandatory.  Simply add the following to your theme's functions.php or your site's custom functions plugin.

`
add_filter("radio-buttons-for-taxonomies-no-term-{$taxonomy}", "__return_FALSE" );
`

So for example, to disabled the "No term" option on a taxonomy called "genre" you'd do the following:

`
add_filter('radio-buttons-for-taxonomies-no-term-genre', '__return_FALSE' );
`

== Changelog ==

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