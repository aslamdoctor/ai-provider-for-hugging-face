=== AI Provider for Hugging Face ===
Contributors: aslamdoctor
Tags: ai, hugging-face, ai-client, connectors, text-generation
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Provider for Hugging Face for the WordPress AI Client. Use open-source models like Mistral, Llama, and Qwen for text and image generation through the standard WordPress AI API.

== Description ==

This plugin registers Hugging Face as an AI provider for the WordPress AI Client introduced in WordPress 7.0. It enables text and image generation through Hugging Face's Inference API using popular open-source models.

**Features:**

* Registers Hugging Face on the Settings > Connectors admin screen
* Supports API key management (environment variable, PHP constant, or database)
* Provides text generation via `wp_ai_client_prompt()`
* Image generation via text-to-image models
* Includes curated default text models: Mistral 7B, Llama 3.1 8B, Qwen 2.5 7B, Phi-3 Mini, Zephyr 7B
* Includes curated default image models: FLUX.1 Schnell, FLUX.1 Dev, SDXL Lightning, Stable Diffusion XL
* Extensible model list via the `hugging_face_ai_provider_models` and `hugging_face_ai_provider_image_models` filters
* Configurable base URL for self-hosted TGI instances

**Usage:**

    $result = wp_ai_client_prompt( 'Summarize the benefits of caching.' )
        ->using_temperature( 0.7 )
        ->generate_text();

    // Generate an image
    $image = wp_ai_client_prompt( 'A futuristic WordPress logo in neon colors' )
        ->generate_image();

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/ai-provider-for-hugging-face` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > Connectors and enter your Hugging Face API key.
4. Get your API key from [Hugging Face Tokens](https://huggingface.co/settings/tokens).

You can also set the API key via environment variable or PHP constant:

    // Environment variable
    HUGGING_FACE_API_KEY=hf_****

    // PHP constant in wp-config.php
    define( 'HUGGING_FACE_API_KEY', 'hf_****' );

== Frequently Asked Questions ==

= Which models are supported? =

The plugin ships with these default models:

* Mistral 7B Instruct v0.3
* Llama 3.1 8B Instruct
* Qwen 2.5 7B Instruct
* Phi-3 Mini 4K Instruct
* Zephyr 7B Beta

For image generation:

* FLUX.1 Schnell
* FLUX.1 Dev
* SDXL Lightning
* Stable Diffusion XL

You can add any HuggingFace-hosted chat model using the `hugging_face_ai_provider_models` filter, or image model using the `hugging_face_ai_provider_image_models` filter.

= Can I use a self-hosted model? =

Yes. Use the `hugging_face_ai_provider_base_url` filter to point to your own Text Generation Inference (TGI) instance.

= What capabilities are supported? =

Text generation and image generation. Video generation support may be added in future versions.

== Changelog ==

= 1.1.0 =
* Image generation via text-to-image models.
* Default image models: FLUX.1 Schnell, FLUX.1 Dev, SDXL Lightning, Stable Diffusion XL.
* Extensible image model list via `hugging_face_ai_provider_image_models` filter.

= 1.0.0 =
* Initial release.
* Connector registration with API key management.
* Text generation via Hugging Face Inference API.
* Curated model list with extensibility filter.
