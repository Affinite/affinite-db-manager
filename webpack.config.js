const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve(__dirname, 'assets/js/src/index.js'),
	},
	output: {
		path: path.resolve(__dirname, 'build/js'),
		filename: '[name].js',
	},
};
