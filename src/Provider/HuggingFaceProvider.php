<?php
/**
 * Hugging Face AI Provider.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\HuggingFaceAiProvider\Provider\HuggingFaceProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\HuggingFaceAiProvider\Metadata\HuggingFaceModelMetadataDirectory;
use WordPress\HuggingFaceAiProvider\Models\HuggingFaceImageGenerationModel;
use WordPress\HuggingFaceAiProvider\Models\HuggingFaceTextGenerationModel;

/**
 * Provider class for Hugging Face.
 */
class HuggingFaceProvider extends AbstractApiProvider {

	/**
	 * Get the base URL for the Hugging Face Inference API.
	 *
	 * @return string
	 */
	protected static function baseUrl(): string {
		$url = 'https://router.huggingface.co/v1';

		/**
		 * Filters the Hugging Face API base URL.
		 *
		 * Useful for pointing to a self-hosted Text Generation Inference (TGI) instance.
		 *
		 * @param string $url The base URL.
		 */
		return apply_filters( 'hugging_face_ai_provider_base_url', $url );
	}

	/**
	 * Create a model instance from metadata.
	 *
	 * @param ModelMetadata    $model_metadata    The model metadata.
	 * @param ProviderMetadata $provider_metadata The provider metadata.
	 * @return ModelInterface
	 * @throws RuntimeException If the model capabilities are unsupported.
	 */
	protected static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		$capabilities = $model_metadata->getSupportedCapabilities();

		foreach ( $capabilities as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new HuggingFaceTextGenerationModel( $model_metadata, $provider_metadata );
			}

			if ( $capability->isImageGeneration() ) {
				return new HuggingFaceImageGenerationModel( $model_metadata, $provider_metadata );
			}
		}

		throw new RuntimeException(
			'Unsupported model capabilities: ' . esc_html( implode( ', ', $capabilities ) )
		);
	}

	/**
	 * Create provider metadata.
	 *
	 * @return ProviderMetadata
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$args = array(
			'hugging_face',
			'Hugging Face',
			ProviderTypeEnum::cloud(),
			'https://huggingface.co/settings/tokens',
			RequestAuthenticationMethod::apiKey(),
		);

		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			$args[] = __( 'Text and image generation with open-source models via Hugging Face Inference API.', 'ai-provider-for-hugging-face' );
		}

		// Provider logo support was added in 1.3.0.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			$args[] = plugin_dir_path( HUGGING_FACE_PROVIDER_FILE ) . 'images/hugging-face-logo.svg';
		}

		return new ProviderMetadata( ...$args );
	}

	/**
	 * Create a provider availability instance.
	 *
	 * @return ProviderAvailabilityInterface
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new HuggingFaceProviderAvailability();
	}

	/**
	 * Create the model metadata directory.
	 *
	 * @return ModelMetadataDirectoryInterface
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new HuggingFaceModelMetadataDirectory();
	}

}
