# AI Provider for Hugging Face

AI Provider for Hugging Face for the WordPress AI Client. Use open-source models like Mistral, Llama, Qwen, and thousands more for text and image generation through the standard WordPress AI API.

## Features

- Registers Hugging Face on the **Settings > Connectors** admin screen
- API key management via environment variable, PHP constant, or the admin UI
- Text generation through `wp_ai_client_prompt()`
- Image generation through `wp_ai_client_prompt()->generate_image()`
- Curated default text models (Mistral 7B, Llama 3.1 8B, Qwen 2.5 7B, Phi-3 Mini, Zephyr 7B)
- Curated default image models (FLUX.1 Schnell, FLUX.1 Dev, SDXL Lightning, Stable Diffusion XL)
- Add custom models from the **Settings > Hugging Face** admin page
- Extensible model list via the `hugging_face_ai_provider_models` and `hugging_face_ai_provider_image_models` filters
- Configurable base URL for self-hosted TGI instances

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

### Default Model

Go to **Settings > Hugging Face** to select a default model or add custom models.

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

## Extensibility

### Add custom models via filter

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
add_filter( 'hugging_face_ai_provider_image_url', function (): string {
    return 'http://localhost:8080/v1';
} );
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and guidelines.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
