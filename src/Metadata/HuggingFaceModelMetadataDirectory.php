<?php
/**
 * Hugging Face model metadata directory.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Metadata;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\HuggingFaceAiProvider\Settings\AdminPage;

/**
 * Curated directory of Hugging Face models available through the Inference API.
 *
 * Uses a static model list (with a filter for extensibility) rather than dynamic
 * API discovery, since HuggingFace hosts thousands of models and there's no
 * scoped listing endpoint for chat-compatible models.
 */
class HuggingFaceModelMetadataDirectory implements ModelMetadataDirectoryInterface {

	/**
	 * Cached model metadata instances.
	 *
	 * @var ModelMetadata[]|null
	 */
	private ?array $cached_models = null;

	/**
	 * List all available model metadata.
	 *
	 * @return ModelMetadata[]
	 */
	public function listModelMetadata(): array {
		if ( null !== $this->cached_models ) {
			return $this->cached_models;
		}

		$models = $this->getDefaultModels();

		// Merge in custom models from the admin settings page.
		$custom_models = AdminPage::get_custom_models();
		foreach ( $custom_models as $custom ) {
			// Avoid duplicates.
			$exists = false;
			foreach ( $models as $model ) {
				if ( $model['id'] === $custom['id'] ) {
					$exists = true;
					break;
				}
			}
			if ( ! $exists ) {
				$models[] = $custom;
			}
		}

		/**
		 * Filters the list of available Hugging Face models.
		 *
		 * @param array $models Array of model definition arrays with keys:
		 *                      'id' (string) - HuggingFace model identifier,
		 *                      'name' (string) - Human-readable display name.
		 */
		$filtered_models = apply_filters( 'hugging_face_ai_provider_models', $models );
		if ( is_array( $filtered_models ) ) {
			$models = $filtered_models;
		}

		// Move the user's chosen default model to the front of the list.
		$default_model_id = AdminPage::get_default_model();
		if ( '' !== $default_model_id ) {
			$default_index = null;
			foreach ( $models as $index => $model ) {
				if ( $model['id'] === $default_model_id ) {
					$default_index = $index;
					break;
				}
			}
			if ( null !== $default_index ) {
				$default = $models[ $default_index ];
				unset( $models[ $default_index ] );
				array_unshift( $models, $default );
				$models = array_values( $models );
			}
		}

		$capabilities      = array(
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		);
		$supported_options = $this->textGenerationOptions();

		$this->cached_models = array_map(
			static function ( array $model ) use ( $capabilities, $supported_options ): ModelMetadata {
				return new ModelMetadata(
					$model['id'],
					$model['name'],
					$capabilities,
					$supported_options
				);
			},
			$models
		);

		// Build image generation model list.
		$image_models = $this->getDefaultImageModels();

		// Merge in custom image models from the admin settings page.
		if ( method_exists( AdminPage::class, 'get_custom_image_models' ) ) {
			$custom_image_models = AdminPage::get_custom_image_models();
			foreach ( $custom_image_models as $custom ) {
				$exists = false;
				foreach ( $image_models as $model ) {
					if ( $model['id'] === $custom['id'] ) {
						$exists = true;
						break;
					}
				}
				if ( ! $exists ) {
					$image_models[] = $custom;
				}
			}
		}

		/**
		 * Filters the list of available Hugging Face image generation models.
		 *
		 * @param array $image_models Array of model definition arrays with keys:
		 *                            'id' (string) - HuggingFace model identifier,
		 *                            'name' (string) - Human-readable display name.
		 */
		$filtered_image_models = apply_filters( 'hugging_face_ai_provider_image_models', $image_models );
		if ( is_array( $filtered_image_models ) ) {
			$image_models = $filtered_image_models;
		}

		// Move the user's chosen default image model to the front of the list.
		if ( method_exists( AdminPage::class, 'get_default_image_model' ) ) {
			$default_image_model_id = AdminPage::get_default_image_model();
			if ( '' !== $default_image_model_id ) {
				$default_image_index = null;
				foreach ( $image_models as $index => $model ) {
					if ( $model['id'] === $default_image_model_id ) {
						$default_image_index = $index;
						break;
					}
				}
				if ( null !== $default_image_index ) {
					$default_image = $image_models[ $default_image_index ];
					unset( $image_models[ $default_image_index ] );
					array_unshift( $image_models, $default_image );
					$image_models = array_values( $image_models );
				}
			}
		}

		$image_capabilities = array( CapabilityEnum::imageGeneration() );
		$image_options      = $this->imageGenerationOptions();

		$image_metadata = array_map(
			static function ( array $model ) use ( $image_capabilities, $image_options ): ModelMetadata {
				return new ModelMetadata(
					$model['id'],
					$model['name'],
					$image_capabilities,
					$image_options
				);
			},
			$image_models
		);

		$this->cached_models = array_merge( $this->cached_models, $image_metadata );

		return $this->cached_models;
	}

	/**
	 * Check if model metadata exists for a given model ID.
	 *
	 * @param string $model_id The model identifier.
	 * @return bool
	 */
	public function hasModelMetadata( string $model_id ): bool {
		foreach ( $this->listModelMetadata() as $model ) {
			if ( $model->getId() === $model_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get model metadata for a specific model ID.
	 *
	 * @param string $model_id The model identifier.
	 * @return ModelMetadata
	 * @throws InvalidArgumentException If the model ID is not found.
	 */
	public function getModelMetadata( string $model_id ): ModelMetadata {
		foreach ( $this->listModelMetadata() as $model ) {
			if ( $model->getId() === $model_id ) {
				return $model;
			}
		}

		throw new InvalidArgumentException(
			sprintf( 'Model metadata not found for model ID: %s', $model_id )
		);
	}

	/**
	 * Default text generation model list.
	 *
	 * Fetches warm text-generation models from the HuggingFace API and caches
	 * the result for 12 hours. Falls back to a hardcoded list if the API
	 * call fails. Use the 'hugging_face_ai_provider_models' filter to add
	 * custom models.
	 *
	 * @return array[]
	 */
	public function getDefaultModels(): array {
		$cached = get_transient( 'hf_text_models_list' );
		if ( false !== $cached && is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$models = $this->fetchWarmTextModels();
		if ( ! empty( $models ) ) {
			set_transient( 'hf_text_models_list', $models, 12 * HOUR_IN_SECONDS );
			return $models;
		}

		// Fallback if API is unreachable.
		return array(
			array(
				'id'   => 'mistralai/Mistral-7B-Instruct-v0.3',
				'name' => 'Mistral-7B-Instruct-v0.3',
			),
			array(
				'id'   => 'meta-llama/Llama-3.1-8B-Instruct',
				'name' => 'Llama-3.1-8B-Instruct',
			),
			array(
				'id'   => 'Qwen/Qwen2.5-7B-Instruct',
				'name' => 'Qwen2.5-7B-Instruct',
			),
			array(
				'id'   => 'microsoft/Phi-3-mini-4k-instruct',
				'name' => 'Phi-3-mini-4k-instruct',
			),
			array(
				'id'   => 'HuggingFaceH4/zephyr-7b-beta',
				'name' => 'zephyr-7b-beta',
			),
		);
	}

	/**
	 * Fetch warm text-generation models from the HuggingFace API.
	 *
	 * @return array[] Array of model definitions with 'id' and 'name' keys.
	 */
	private function fetchWarmTextModels(): array {
		$response = wp_remote_get(
			'https://huggingface.co/api/models?pipeline_tag=text-generation&inference=warm&sort=likes&direction=-1&limit=20',
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array();
		}

		$models = array();
		foreach ( $body as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}
			$id    = $item['id'];
			$parts = explode( '/', $id );
			$name  = end( $parts );

			$models[] = array(
				'id'   => $id,
				'name' => $name,
			);
		}

		return $models;
	}

	/**
	 * Default image generation model list.
	 *
	 * Fetches warm text-to-image models from the HuggingFace API and caches
	 * the result for 12 hours. Falls back to a hardcoded list if the API
	 * call fails. Use the 'hugging_face_ai_provider_image_models' filter
	 * to add custom models.
	 *
	 * @return array[]
	 */
	public function getDefaultImageModels(): array {
		$cached = get_transient( 'hf_image_models_list' );
		if ( false !== $cached && is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$models = $this->fetchWarmImageModels();
		if ( ! empty( $models ) ) {
			set_transient( 'hf_image_models_list', $models, 12 * HOUR_IN_SECONDS );
			return $models;
		}

		// Fallback if API is unreachable.
		return array(
			array(
				'id'   => 'black-forest-labs/FLUX.1-schnell',
				'name' => 'FLUX.1 Schnell',
			),
			array(
				'id'   => 'stabilityai/stable-diffusion-xl-base-1.0',
				'name' => 'Stable Diffusion XL',
			),
		);
	}

	/**
	 * Fetch warm text-to-image models from the HuggingFace API.
	 *
	 * @return array[] Array of model definitions with 'id' and 'name' keys.
	 */
	private function fetchWarmImageModels(): array {
		$response = wp_remote_get(
			'https://huggingface.co/api/models?pipeline_tag=text-to-image&inference=warm&sort=likes&direction=-1&limit=20',
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array();
		}

		$models = array();
		foreach ( $body as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}
			$id    = $item['id'];
			$parts = explode( '/', $id );
			$name  = end( $parts );

			$models[] = array(
				'id'   => $id,
				'name' => $name,
			);
		}

		return $models;
	}

	/**
	 * Supported options for image generation models.
	 *
	 * inputModalities and outputModalities MUST be declared, or the SDK
	 * rejects models during capability matching.
	 *
	 * @return SupportedOption[]
	 */
	private function imageGenerationOptions(): array {
		return array(
			new SupportedOption(
				OptionEnum::inputModalities(),
				array( array( ModalityEnum::text() ) )
			),
			new SupportedOption(
				OptionEnum::outputModalities(),
				array( array( ModalityEnum::image() ) )
			),
			new SupportedOption(
				OptionEnum::outputMimeType(),
				array( 'image/png', 'image/jpeg', 'image/webp' )
			),
			new SupportedOption(
				OptionEnum::outputFileType(),
				array( FileTypeEnum::inline(), FileTypeEnum::remote() )
			),
			new SupportedOption( OptionEnum::customOptions() ),
		);
	}

	/**
	 * Supported options for text generation models.
	 *
	 * inputModalities and outputModalities MUST be declared, or the SDK
	 * rejects models during capability matching.
	 *
	 * @return SupportedOption[]
	 */
	private function textGenerationOptions(): array {
		return array(
			new SupportedOption(
				OptionEnum::inputModalities(),
				array( array( ModalityEnum::text() ) )
			),
			new SupportedOption(
				OptionEnum::outputModalities(),
				array( array( ModalityEnum::text() ) )
			),
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::candidateCount() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption(
				OptionEnum::outputMimeType(),
				array( 'text/plain', 'application/json' )
			),
			new SupportedOption( OptionEnum::outputSchema() ),
			new SupportedOption( OptionEnum::customOptions() ),
		);
	}
}
