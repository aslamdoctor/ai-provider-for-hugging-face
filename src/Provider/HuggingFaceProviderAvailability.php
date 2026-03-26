<?php
/**
 * Hugging Face provider availability check.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Provider;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Checks whether the Hugging Face provider is configured and ready to use.
 *
 * Since our model metadata directory uses a static list (no API call),
 * the default ListModelsApiBasedProviderAvailability always returns true.
 * This class actually verifies that an API key has been provided.
 */
class HuggingFaceProviderAvailability implements ProviderAvailabilityInterface {

	/**
	 * Check if the provider is configured with valid credentials.
	 *
	 * Checks for the API key in the same order as the WordPress connector system:
	 * 1. Environment variable (HUGGING_FACE_API_KEY)
	 * 2. PHP constant (HUGGING_FACE_API_KEY)
	 * 3. Database option (connectors_ai_hugging_face_api_key)
	 *
	 * @return bool True if an API key is available.
	 */
	public function isConfigured(): bool {
		// Check environment variable.
		$env_key = getenv( 'HUGGING_FACE_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return true;
		}

		// Check PHP constant.
		if ( defined( 'HUGGING_FACE_API_KEY' ) && ! empty( constant( 'HUGGING_FACE_API_KEY' ) ) ) {
			return true;
		}

		// Check database option.
		$db_key = get_option( 'connectors_ai_hugging_face_api_key', '' );
		if ( ! empty( $db_key ) ) {
			return true;
		}

		return false;
	}
}
