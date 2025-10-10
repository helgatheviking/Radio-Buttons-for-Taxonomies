/**
 * Copy private utility functions from Gutenberg to avoid adding it as a dependency.
 */

/**
 * @param see https://github.com/WordPress/gutenberg/blob/609cd09f308a2420eb049b38d800e2570a0ea592/packages/components/src/utils/strings.ts#L21 value
 */
export const normalizeTextString = ( value ) => {
	return removeAccents( value )
		.normalize( 'NFKC' )
		.toLocaleLowerCase()
		.replace( ALL_UNICODE_DASH_CHARACTERS, '-' );
};

/**
 * @see: https://github.com/WordPress/gutenberg/blob/trunk/packages/core-data/src/utils/receive-intermediate-results.js
 */
export const RECEIVE_INTERMEDIATE_RESULTS = Symbol(
	'RECEIVE_INTERMEDIATE_RESULTS'
);
