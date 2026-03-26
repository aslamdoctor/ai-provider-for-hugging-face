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
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Image generation model using Hugging Face's native text-to-image Inference API.
 *
 * Unlike the OpenAI-compatible chat endpoint, the text-to-image API accepts a
 * simple JSON body with an "inputs" field and returns raw image bytes directly.
 */
class HuggingFaceImageGenerationModel extends AbstractApiBasedModel implements ImageGenerationModelInterface {

	/**
	 * Default MIME type when the response does not include a Content-Type header.
	 *
	 * @var string
	 */
	private const DEFAULT_MIME_TYPE = 'image/png';

	/**
	 * Generate an image from the given prompt messages.
	 *
	 * @param list<Message> $prompt Array of messages containing the image generation prompt.
	 * @return GenerativeAiResult Result containing the generated image.
	 */
	public function generateImageResult( array $prompt ): GenerativeAiResult {
		$prompt_text = $this->extractPromptText( $prompt );

		$url = $this->getImageApiUrl();

		$request = new Request(
			HttpMethodEnum::POST(),
			$url,
			array(
				'Content-Type' => 'application/json',
				'Accept'       => 'image/*',
			),
			array( 'inputs' => $prompt_text ),
			$this->getRequestOptions()
		);

		$request  = $this->getRequestAuthentication()->authenticateRequest( $request );
		$response = $this->getHttpTransporter()->send( $request );

		ResponseUtil::throwIfNotSuccessful( $response );

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
	 * Build the text-to-image API URL for the current model.
	 *
	 * @return string The full endpoint URL.
	 */
	private function getImageApiUrl(): string {
		$model_id = $this->metadata()->getId();
		$url      = 'https://router.huggingface.co/hf-inference/models/' . $model_id;

		/**
		 * Filters the Hugging Face image generation API URL.
		 *
		 * Useful for pointing to a self-hosted inference instance.
		 *
		 * @param string $url      The image generation API URL.
		 * @param string $model_id The model identifier.
		 */
		return apply_filters( 'hugging_face_ai_provider_image_url', $url, $model_id );
	}

	/**
	 * Extract the prompt text from an array of Message objects.
	 *
	 * Concatenates all text parts from all messages into a single string.
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

		// Strip parameters (e.g. "image/png; charset=utf-8" -> "image/png").
		$parts = explode( ';', $content_type );
		$mime  = trim( $parts[0] );

		return '' !== $mime ? $mime : self::DEFAULT_MIME_TYPE;
	}
}
