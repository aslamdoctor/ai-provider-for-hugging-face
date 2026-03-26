<?php
/**
 * Connector settings for Hugging Face.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Manages the Hugging Face API key setting for the Connectors admin page.
 *
 * WordPress core handles API key masking and display in the Connectors UI.
 * This class only needs to register the setting with a label and description.
 */
class ConnectorSettings {

	/**
	 * Option name following the convention: connectors_ai_{provider_id}_api_key.
	 */
	const OPTION_API_KEY = 'connectors_ai_hugging_face_api_key';

	/**
	 * Register the API key setting with WordPress.
	 */
	public static function register(): void {
		register_setting(
			'connectors',
			self::OPTION_API_KEY,
			array(
				'type'              => 'string',
				'label'             => __( 'Hugging Face API Key', 'ai-provider-for-hugging-face' ),
				'description'       => __( 'API key for the Hugging Face AI provider.', 'ai-provider-for-hugging-face' ),
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}
}
