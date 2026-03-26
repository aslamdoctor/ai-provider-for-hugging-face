=== AI Provider for Hugging Face ===
Contributors: aslamdoctor
Tags: ai, hugging-face, ai-client, connectors, image-generation
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Provider for Hugging Face for the WordPress AI Client. Use open-source models for text and image generation through the standard WordPress AI API.

== Description ==

This plugin registers Hugging Face as an AI provider for the WordPress AI Client introduced in WordPress 7.0. It enables text and image generation through Hugging Face's Inference API using popular open-source models.

**Features:**

* Registers Hugging Face on the Settings > Connectors admin screen
* Supports API key management (environment variable, PHP constant, or database)
* Text generation via `wp_ai_client_prompt()`
* Image generation via `wp_ai_client_prompt()->generate_image()`
* Dynamically fetches top 20 popular models from HuggingFace (updated every 12 hours)
* Multi-provider support — automatically routes to the best available inference provider (hf-inference, fal-ai, replicate, together, nscale, wavespeed)
* Add custom models from the Settings > Hugging Face admin page
* Extensible model lists via `hugging_face_ai_provider_models` and `hugging_face_ai_provider_image_models` filters
* Configurable base URL for self-hosted TGI instances
* Clear, actionable error messages for common API issues

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

The plugin dynamically fetches the top 20 most popular text and image generation models from HuggingFace, updated every 12 hours. You can also add any model via the Custom Models field in Settings > Hugging Face, or using the `hugging_face_ai_provider_models` and `hugging_face_ai_provider_image_models` filters.

= Do I need to pay for HuggingFace? =

Models on the free "hf-inference" provider work without credits. Other providers (fal-ai, replicate, etc.) require pre-paid HuggingFace credits. The plugin automatically selects the best available provider, preferring the free tier.

= Can I use a self-hosted model? =

Yes. Use the `hugging_face_ai_provider_base_url` filter for text generation or `hugging_face_ai_provider_image_url` filter for image generation to point to your own inference instance.

= What capabilities are supported? =

Text generation and image generation. Video generation support may be added in future versions.

== Screenshots ==

1. Settings > Connectors screen showing Hugging Face connected with API key.
2. Settings > Hugging Face admin page with text and image model configuration.

== External Services ==

This plugin connects to the Hugging Face Inference API to provide AI-powered text and image generation capabilities within WordPress. The Hugging Face Inference API is a third-party service operated by Hugging Face, Inc.

= What data is sent and when =

* Your Hugging Face API key is sent with every request for authentication.
* When fetching the model list, a request is sent to the Hugging Face API to retrieve available models.
* When generating text, your prompt and any conversation context are sent to the Hugging Face Inference API.
* When generating images, your text prompt is sent to the Hugging Face Inference API.
* When resolving inference providers, a request is sent to the Hugging Face API to determine available providers for the selected model.

Data is only sent when the plugin is actively used to generate text or images, or when loading the model list on the admin settings page.

= Service links =

* [Hugging Face Website](https://huggingface.co/)
* [Hugging Face Inference API Documentation](https://huggingface.co/docs/api-inference/)
* [Hugging Face Terms of Service](https://huggingface.co/terms-of-service)
* [Hugging Face Privacy Policy](https://huggingface.co/privacy)
* [Hugging Face Content Policy](https://huggingface.co/content-guidelines)

All API communication is routed through `https://router.huggingface.co` and `https://huggingface.co/api`.

== Changelog ==

= 1.0.0 =
* Initial release.
* Connector registration with API key management.
* Text generation via Hugging Face Inference API.
* Image generation via text-to-image models with multi-provider support.
* Dynamic model lists fetched from HuggingFace API.
* Admin page with custom model management (add/remove with instant dropdown sync).
* Clear error messages for 400, 401, 402, 404, 410, 422, 429, 500, 503 API responses.
