const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		editor: path.resolve(__dirname, 'src/editor/index.ts'),
		settings: path.resolve(__dirname, 'src/settings/index.tsx'),
	},
	output: {
		path: path.resolve(__dirname, 'build'),
		filename: '[name].js',
	},
};
