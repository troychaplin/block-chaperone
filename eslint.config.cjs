const defaultConfig = require('@wordpress/scripts/config/eslint.config.cjs');
const prettierConfig = require('eslint-config-prettier');

module.exports = [
	{
		ignores: ['build/**', 'node_modules/**', 'vendor/**', '_examples/**'],
	},
	...defaultConfig,
	// Disable ESLint rules that conflict with Prettier.
	prettierConfig,
	{
		rules: {
			'@wordpress/no-global-active-element': 'warn',
			'@wordpress/no-unsafe-wp-apis': 'off',
		},
	},
];
