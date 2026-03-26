<?php
/**
 * Hugging Face image generation model.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Models;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\ServerException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Image generation model using Hugging Face's native text-to-image Inference API.
 *
 * Supports multiple inference providers (hf-inference, fal-ai, replicate, etc.)
 * by resolving the best available provider for each model. Free tier (hf-inference)
 * is preferred when available; paid providers are used as fallback.
 */
class HuggingFaceImageGenerationModel extends AbstractApiBasedModel implements ImageGenerationModelInterface {

	/**
	 * Default MIME type when the response does not include a Content-Type header.
	 *
	 * @var string
	 */
	private const DEFAULT_MIME_TYPE = 'image/png';

	/**
	 * Preferred provider order. hf-inference (free) is tried first.
	 *
	 * @var string[]
	 */
	private const PROVIDER_PRIORITY = array(
		'hf-inference',
		'fal-ai',
		'replicate',
		'together',
		'nscale',
		'wavespeed',
	);

	/**
	 * Generate an image from the given prompt messages.
	 *
	 * @param list<Message> $prompt Array of messages containing the image generation prompt.
	 * @return GenerativeAiResult Result containing the generated image.
	 */
	public function generateImageResult( array $prompt ): GenerativeAiResult {
		$prompt_text = $this->extractPromptText( $prompt );
		$model_id    = $this->metadata()->getId();

		// Resolve the best provider and build the URL.
		$provider_info = $this->resolveProvider( $model_id );
		$url           = 'https://router.huggingface.co/' . $provider_info['provider'] . '/models/' . $provider_info['providerId'];

		/**
		 * Filters the Hugging Face image generation API URL.
		 *
		 * @param string $url      The image generation API URL.
		 * @param string $model_id The model identifier.
		 */
		$url = apply_filters( 'hugging_face_ai_provider_image_url', $url, $model_id );

		$request = new Request(
			HttpMethodEnum::POST(),
			$url,
			array(
				'Content-Type' => 'application/json',
				'Accept'       => 'image/png',
			),
			array( 'inputs' => $prompt_text ),
			$this->getRequestOptions()
		);

		$request  = $this->getRequestAuthentication()->authenticateRequest( $request );
		$response = $this->getHttpTransporter()->send( $request );

		$this->throwIfNotSuccessfulWithContext( $response );

		$image_bytes = $response->getBody();
		$mime_type   = $this->detectMimeType( $response );
		$base64      = base64_encode( (string) $image_bytes );

		$file      = new File( $base64, $mime_type );
		$part      = new MessagePart( $file );
		$message   = new Message( MessageRoleEnum::model(), array( $part ) );
		$candidate = new Candidate( $message, FinishReasonEnum::stop() );

		return new GenerativeAiResult(
			wp_generate_uuid4(),
			array( $candidate ),
			new TokenUsage( 0, 0, 0 ),
			$this->providerMetadata(),
			$this->metadata()
		);
	}

	/**
	 * Resolve the best inference provider for a model.
	 *
	 * Queries the HuggingFace API for available providers and returns the
	 * highest-priority one. Results are cached in a transient for 1 hour.
	 *
	 * @param string $model_id The HuggingFace model identifier.
	 * @return array{provider: string, providerId: string} The provider name and its model ID.
	 */
	private function resolveProvider( string $model_id ): array {
		$cache_key = 'hf_img_provider_' . md5( $model_id );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Default to hf-inference if we can't resolve.
		$default = array(
			'provider'   => 'hf-inference',
			'providerId' => $model_id,
		);

		$api_url  = 'https://huggingface.co/api/models/' . $model_id . '?expand[]=inferenceProviderMapping';
		$response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return $default;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['inferenceProviderMapping'] ) ) {
			return $default;
		}

		$mapping = $body['inferenceProviderMapping'];

		// Find the highest-priority provider that is live.
		foreach ( self::PROVIDER_PRIORITY as $provider ) {
			if ( isset( $mapping[ $provider ] ) && 'live' === ( $mapping[ $provider ]['status'] ?? '' ) ) {
				$result = array(
					'provider'   => $provider,
					'providerId' => $mapping[ $provider ]['providerId'] ?? $model_id,
				);
				set_transient( $cache_key, $result, HOUR_IN_SECONDS );
				return $result;
			}
		}

		// Fallback: pick any live provider.
		foreach ( $mapping as $provider => $info ) {
			if ( 'live' === ( $info['status'] ?? '' ) ) {
				$result = array(
					'provider'   => $provider,
					'providerId' => $info['providerId'] ?? $model_id,
				);
				set_transient( $cache_key, $result, HOUR_IN_SECONDS );
				return $result;
			}
		}

		return $default;
	}

	/**
	 * Extract the prompt text from an array of Message objects.
	 *
	 * @param list<Message> $prompt The prompt messages.
	 * @return string The combined prompt text.
	 */
	private function extractPromptText( array $prompt ): string {
		$texts = array();

		foreach ( $prompt as $message ) {
			foreach ( $message->getParts() as $part ) {
				$text = $part->getText();
				if ( null !== $text ) {
					$texts[] = $text;
				}
			}
		}

		return implode( ' ', $texts );
	}

	/**
	 * Check the response for errors and throw descriptive exceptions.
	 *
	 * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response.
	 */
	private function throwIfNotSuccessfulWithContext( $response ): void {
		if ( $response->isSuccessful() ) {
			return;
		}

		$status_code = $response->getStatusCode();
		$model_id    = $this->metadata()->getId();
		$api_message = $this->extractErrorMessage( $response );

		switch ( $status_code ) {
			case 400:
				throw new ClientException(
					sprintf(
						'Bad request for model "%s". The model could not process the request. %s',
						esc_html( $model_id ),
						esc_html( $api_message )
					),
					(int) $status_code
				);

			case 401:
				throw new ClientException(
					sprintf(
						'Authentication failed for model "%s". Please check your HuggingFace API key in Settings > Connectors.',
						esc_html( $model_id )
					),
					(int) $status_code
				);

			case 402:
				throw new ClientException(
					sprintf(
						'Model "%s" requires pre-paid HuggingFace credits. '
						. 'Add credits at https://huggingface.co/settings/billing '
						. 'or choose a model available on the free "hf-inference" provider.',
						esc_html( $model_id )
					),
					(int) $status_code
				);

			case 404:
				throw new ClientException(
					sprintf(
						'Model "%s" is not available on any HuggingFace inference provider. '
						. 'Browse available models at https://huggingface.co/models?inference=warm&pipeline_tag=text-to-image',
						esc_html( $model_id )
					),
					(int) $status_code
				);

			case 410:
				throw new ClientException(
					sprintf(
						'Model "%s" has been deprecated and is no longer available on the HuggingFace Inference API.',
						esc_html( $model_id )
					),
					(int) $status_code
				);

			case 422:
				throw new ClientException(
					sprintf(
						'Model "%s" could not process the request parameters. %s',
						esc_html( $model_id ),
						esc_html( $api_message )
					),
					(int) $status_code
				);

			case 429:
				throw new ClientException(
					sprintf(
						'Rate limit exceeded for model "%s". Please wait a moment before trying again. '
						. 'Consider upgrading your HuggingFace plan for higher rate limits.',
						esc_html( $model_id )
					),
					(int) $status_code
				);

			case 500:
				throw new ServerException(
					sprintf(
						'HuggingFace server error while generating image with model "%s". Please try again later. %s',
						esc_html( $model_id ),
						esc_html( $api_message )
					),
					(int) $status_code
				);

			case 503:
				throw new ServerException(
					sprintf(
						'Model "%s" is currently loading or temporarily unavailable. Please try again in a few moments.',
						esc_html( $model_id )
					),
					(int) $status_code
				);
		}

		// Fallback for any other status codes.
		ResponseUtil::throwIfNotSuccessful( $response );
	}

	/**
	 * Extract a human-readable error message from the API response body.
	 *
	 * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response.
	 * @return string The error message, or empty string if none found.
	 */
	private function extractErrorMessage( $response ): string {
		$body = (string) $response->getBody();
		$data = json_decode( $body, true );

		if ( is_array( $data ) && ! empty( $data['error'] ) ) {
			return is_string( $data['error'] ) ? $data['error'] : '';
		}

		return '';
	}

	/**
	 * Detect the MIME type from the response Content-Type header.
	 *
	 * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response.
	 * @return string The MIME type string.
	 */
	private function detectMimeType( $response ): string {
		$content_type = $response->getHeaderAsString( 'Content-Type' );

		if ( null === $content_type || '' === $content_type ) {
			return self::DEFAULT_MIME_TYPE;
		}

		$parts = explode( ';', $content_type );
		$mime  = trim( $parts[0] );

		return '' !== $mime ? $mime : self::DEFAULT_MIME_TYPE;
	}
}
