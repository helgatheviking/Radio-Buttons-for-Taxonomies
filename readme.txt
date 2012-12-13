=== Radio Buttons for Taxonomies ===
Contributors: helgatheviking
Donate link: http://www.kathyisawesome.com/441/radio-buttons-for-taxonomies/ ‎
Tags: taxonomy, admin, interface, ui, post, radio
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 1.1.4
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

== Changelog ==

- 1.1.4 -
* Correct plugin URL
* fixed quick edit bug
* fixed undefined $post variable warning

- 1.1.3 -
* Code cleanup

- 1.1.2 -
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