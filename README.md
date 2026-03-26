# AI Provider for Hugging Face

AI Provider for Hugging Face for the WordPress AI Client. Use open-source models for text and image generation through the standard WordPress AI API.

## Features

- Registers Hugging Face on the **Settings > Connectors** admin screen
- API key management via environment variable, PHP constant, or the admin UI
- Text generation through `wp_ai_client_prompt()`
- Image generation through `wp_ai_client_prompt()->generate_image()`
- **Dynamic model lists** — fetches top 20 popular models from HuggingFace, refreshed every 12 hours
- **Multi-provider routing** — automatically selects the best inference provider (hf-inference, fal-ai, replicate, together, nscale, wavespeed), preferring the free tier
- Add custom models from the **Settings > Hugging Face** admin page with instant dropdown sync
- Extensible via `hugging_face_ai_provider_models` and `hugging_face_ai_provider_image_models` filters
- Configurable base URL for self-hosted TGI instances
- Clear, actionable error messages for all common API error codes

## Requirements

- WordPress 7.0+
- PHP 7.4+
- A [Hugging Face API key](https://huggingface.co/settings/tokens)

## Installation

### From WordPress.org

1. Go to **Plugins > Add New** in your WordPress admin.
2. Search for "AI Provider for Hugging Face".
3. Click **Install Now**, then **Activate**.

### Manual Installation

1. Download the latest release from [GitHub](https://github.com/aslamdoctor/ai-provider-for-hugging-face/releases).
2. Upload to `/wp-content/plugins/ai-provider-for-hugging-face/`.
3. Activate through the **Plugins** menu.

## Configuration

### API Key

Set your API key using any of these methods (checked in order):

1. **Environment variable:** `HUGGING_FACE_API_KEY=hf_****`
2. **PHP constant** in `wp-config.php`:
   ```php
   define( 'HUGGING_FACE_API_KEY', 'hf_****' );
   ```
3. **Admin UI:** Go to **Settings > Connectors** and enter your key.

### Model Settings

Go to **Settings > Hugging Face** to:
- Select a default text generation model
- Select a default image generation model
- Add custom models (type a model ID and click Add)

The dropdowns show the top 20 most popular models, refreshed automatically. Custom models you add appear instantly in the dropdown.

## Usage

```php
// Simple text generation
$result = wp_ai_client_prompt( 'Summarize the benefits of caching.' )
    ->using_temperature( 0.7 )
    ->generate_text();

// With model preference
$result = wp_ai_client_prompt( 'Explain quantum computing.' )
    ->using_model_preference( 'meta-llama/Llama-3.1-8B-Instruct' )
    ->generate_text();

// Structured JSON output
$schema = array(
    'type'       => 'object',
    'properties' => array(
        'summary' => array( 'type' => 'string' ),
        'tags'    => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
    ),
);

$json = wp_ai_client_prompt( 'Analyze this text.' )
    ->as_json_response( $schema )
    ->generate_text();

// Generate an image
$image = wp_ai_client_prompt( 'A futuristic WordPress logo in neon colors' )
    ->generate_image();

if ( ! is_wp_error( $image ) ) {
    echo '<img src="' . esc_url( $image->getDataUri() ) . '" alt="">';
}
```

## Pricing

Models on the free **hf-inference** provider work without credits. Other providers (fal-ai, replicate, together, etc.) require [pre-paid HuggingFace credits](https://huggingface.co/settings/billing). The plugin automatically selects the best available provider, preferring the free tier.

## Extensibility

### Add custom text models via filter

```php
add_filter( 'hugging_face_ai_provider_models', function ( array $models ): array {
    $models[] = array(
        'id'   => 'google/gemma-2-9b-it',
        'name' => 'Gemma 2 9B IT',
    );
    return $models;
} );
```

### Add custom image models via filter

```php
add_filter( 'hugging_face_ai_provider_image_models', function ( array $models ): array {
    $models[] = array(
        'id'   => 'CompVis/stable-diffusion-v1-4',
        'name' => 'Stable Diffusion v1.4',
    );
    return $models;
} );
```

### Use a self-hosted TGI instance

```php
add_filter( 'hugging_face_ai_provider_base_url', function (): string {
    return 'http://localhost:8080/v1';
} );
```

### Custom image generation endpoint

```php
add_filter( 'hugging_face_ai_provider_image_url', function ( string $url, string $model_id ): string {
    return 'http://localhost:8080/models/' . $model_id;
}, 10, 2 );
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and guidelines.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
