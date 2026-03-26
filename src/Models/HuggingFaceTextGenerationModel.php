<?php
/**
 * Hugging Face text generation model.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Models;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\HuggingFaceAiProvider\Provider\HuggingFaceProvider;

/**
 * Text generation model using Hugging Face's OpenAI-compatible Inference API.
 *
 * The base URL (https://router.huggingface.co/v1) combined with the path
 * (chat/completions) produces the correct endpoint. The model ID is sent
 * in the request body, matching HuggingFace's router API format.
 */
class HuggingFaceTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * Create a request for the HuggingFace Inference API.
	 *
	 * @param HttpMethodEnum $method  The HTTP method.
	 * @param string         $path    The API path (e.g., 'chat/completions').
	 * @param array          $headers Additional request headers.
	 * @param mixed          $data    The request body data.
	 * @return Request
	 */
	protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		// HuggingFace doesn't support n > 1. Remove it to avoid 422 errors.
		if ( is_array( $data ) && isset( $data['n'] ) ) {
			unset( $data['n'] );
		}

		return new Request(
			$method,
			HuggingFaceProvider::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions()
		);
	}
}
