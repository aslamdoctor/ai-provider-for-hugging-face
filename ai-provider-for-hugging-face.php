<?php
/**
 * Plugin Name: AI Provider for Hugging Face
 * Plugin URI: https://github.com/aslamdoctor/ai-provider-for-hugging-face
 * Description: AI Provider for Hugging Face for the WordPress AI Client.
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: Aslam Doctor
 * Author URI: https://aslamdoctor.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-provider-for-hugging-face
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider;

use WordPress\AiClient\AiClient;
use WordPress\HuggingFaceAiProvider\Provider\HuggingFaceProvider;
use WordPress\HuggingFaceAiProvider\Settings\AdminPage;
use WordPress\HuggingFaceAiProvider\Settings\ConnectorSettings;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

define( 'HUGGING_FACE_PROVIDER_VERSION', '1.0.0' );
define( 'HUGGING_FACE_PROVIDER_FILE', __FILE__ );

require_once __DIR__ . '/src/autoload.php';

/**
 * Register the provider with the AI Client.
 */
function register_provider(): void {
	if ( ! class_exists( AiClient::class ) ) {
		return;
	}

	$registry = AiClient::defaultRegistry();

	if ( $registry->hasProvider( HuggingFaceProvider::class ) ) {
		return;
	}

	$registry->registerProvider( HuggingFaceProvider::class );
}
add_action( 'init', __NAMESPACE__ . '\\register_provider', 5 );

/**
 * Register connector settings for the Connectors admin page.
 */
function register_connector_settings(): void {
	ConnectorSettings::register();
}
add_action( 'init', __NAMESPACE__ . '\\register_connector_settings' );

/**
 * Register the admin settings page.
 */
function register_admin_page(): void {
	AdminPage::register();
}
add_action( 'init', __NAMESPACE__ . '\\register_admin_page' );

/**
 * Register the connector JavaScript module.
 */
function register_connector_module(): void {
	wp_register_script_module(
		'ai-provider-for-hugging-face/connectors',
		plugins_url( 'build/connectors.js', HUGGING_FACE_PROVIDER_FILE ),
		array(
			array(
				'id'     => '@wordpress/connectors',
				'import' => 'dynamic',
			),
		),
		HUGGING_FACE_PROVIDER_VERSION
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_connector_module' );

/**
 * Enqueue the connector module on the Connectors admin page.
 */
function enqueue_connector_module(): void {
	$logo_url = plugins_url( 'images/hugging-face-logo.svg', HUGGING_FACE_PROVIDER_FILE );
	wp_add_inline_script(
		'wp-api-fetch',
		sprintf( 'window.huggingFaceProviderData = %s;', wp_json_encode( array( 'logoUrl' => $logo_url ) ) ),
		'before'
	);
	wp_enqueue_script_module( 'ai-provider-for-hugging-face/connectors' );
}
add_action( 'options-connectors-wp-admin_init', __NAMESPACE__ . '\\enqueue_connector_module' );
add_action( 'connectors-wp-admin_init', __NAMESPACE__ . '\\enqueue_connector_module' );
