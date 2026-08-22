import wordpress from '@wordpress/eslint-plugin';
import globals from 'globals';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
		rules: {
			'jsdoc/no-undefined-types': 'off',
		},
	},
];
