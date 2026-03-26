const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		connectors: './src/js/connectors.js',
	},
	output: {
		...defaultConfig.output,
		path: __dirname + '/build',
	},
};
