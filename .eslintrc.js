module.exports = {
	extends: require.resolve( '@wordpress/scripts/config/.eslintrc.js' ),
	globals: {
		RB4Tl18: 'readonly',
	},
	ignorePatterns: [
		'**/*.min.js',
	],
	rules: {
		// Allow only a specific text domain
		'@wordpress/i18n-text-domain': [ 'error', {
			allowedTextDomain: [ 'radio-buttons-for-taxonomies' ]
		} ]
	},
};
