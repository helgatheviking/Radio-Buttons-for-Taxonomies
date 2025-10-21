/* global RB4Tl18n */

/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RadioTermSelector from './radio-term-selector';

function CustomizeTaxonomySelector( OriginalComponent ) {
	return function ( props ) {
		// props.slug is the taxonomy (slug).
		if ( RB4Tl18n.radio_taxonomies.indexOf( props.slug ) >= 0 ) {
			return createElement( RadioTermSelector, props );
		}

		return createElement( OriginalComponent, props );
	};
}

wp.hooks.addFilter(
	'editor.PostTaxonomyType',
	'RB4T',
	CustomizeTaxonomySelector
);
