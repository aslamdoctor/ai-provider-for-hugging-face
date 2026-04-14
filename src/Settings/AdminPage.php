<?php
/**
 * Admin settings page for Hugging Face provider.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

declare( strict_types=1 );

namespace WordPress\HuggingFaceAiProvider\Settings;

use WordPress\HuggingFaceAiProvider\Metadata\HuggingFaceModelMetadataDirectory;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Registers and renders the Settings > Hugging Face admin page.
 */
class AdminPage {

	const OPTION_DEFAULT_MODEL        = 'hugging_face_default_model';
	const OPTION_CUSTOM_MODELS        = 'hugging_face_custom_models';
	const OPTION_DEFAULT_IMAGE_MODEL  = 'hugging_face_default_image_model';
	const OPTION_CUSTOM_IMAGE_MODELS  = 'hugging_face_custom_image_models';
	const OPTION_GROUP                = 'hugging_face_settings';
	const PAGE_SLUG                   = 'hugging-face-settings';

	/**
	 * Register hooks for the admin page.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add the submenu page under Settings.
	 */
	public static function add_menu_page(): void {
		add_options_page(
			__( 'Hugging Face', 'ai-provider-for-hugging-face' ),
			__( 'Hugging Face', 'ai-provider-for-hugging-face' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on the plugin settings page only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'aiprfohu-admin-settings',
			plugins_url( 'css/admin-settings.css', HUGGING_FACE_PROVIDER_FILE ),
			array(),
			HUGGING_FACE_PROVIDER_VERSION
		);

		wp_enqueue_script(
			'aiprfohu-admin-settings',
			plugins_url( 'js/admin-settings.js', HUGGING_FACE_PROVIDER_FILE ),
			array(),
			HUGGING_FACE_PROVIDER_VERSION,
			true
		);
	}

	/**
	 * Register the settings and fields.
	 */
	public static function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_DEFAULT_MODEL,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_CUSTOM_MODELS,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_custom_models' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_DEFAULT_IMAGE_MODEL,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_CUSTOM_IMAGE_MODELS,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_custom_models' ),
			)
		);

		// Text generation section.
		add_settings_section(
			'hugging_face_model_section',
			__( 'Text Generation Settings', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPTION_DEFAULT_MODEL,
			__( 'Default Model', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_model_field' ),
			self::PAGE_SLUG,
			'hugging_face_model_section'
		);

		add_settings_field(
			self::OPTION_CUSTOM_MODELS,
			__( 'Custom Models', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_custom_models_field' ),
			self::PAGE_SLUG,
			'hugging_face_model_section'
		);

		// Image generation section.
		add_settings_section(
			'hugging_face_image_model_section',
			__( 'Image Generation Settings', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_image_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPTION_DEFAULT_IMAGE_MODEL,
			__( 'Default Image Model', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_image_model_field' ),
			self::PAGE_SLUG,
			'hugging_face_image_model_section'
		);

		add_settings_field(
			self::OPTION_CUSTOM_IMAGE_MODELS,
			__( 'Custom Image Models', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_custom_image_models_field' ),
			self::PAGE_SLUG,
			'hugging_face_image_model_section'
		);
	}

	/**
	 * Render the text section description.
	 */
	public static function render_section_description(): void {
		echo '<p>' . esc_html__( 'Choose a default text generation model and add custom models.', 'ai-provider-for-hugging-face' ) . '</p>';
	}

	/**
	 * Render the image section description.
	 */
	public static function render_image_section_description(): void {
		echo '<p>' . esc_html__( 'Choose a default image generation model and add custom models.', 'ai-provider-for-hugging-face' ) . '</p>';
	}

	/**
	 * Render a model dropdown with custom model optgroup.
	 *
	 * @param string  $name    The select field name attribute.
	 * @param string  $current The currently selected value.
	 * @param array[] $models  The model list (custom first, then built-in).
	 * @param string  $id      The select field id attribute.
	 */
	private static function render_model_dropdown( string $name, string $current, array $models, string $id ): void {
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';
		echo '<option value="">' . esc_html__( '— First available (default) —', 'ai-provider-for-hugging-face' ) . '</option>';

		$in_custom_group = false;
		foreach ( $models as $model ) {
			if ( ! empty( $model['custom'] ) && ! $in_custom_group ) {
				echo '<optgroup label="' . esc_attr__( 'Custom Models', 'ai-provider-for-hugging-face' ) . '">';
				$in_custom_group = true;
			}
			if ( empty( $model['custom'] ) && $in_custom_group ) {
				echo '</optgroup>';
				$in_custom_group = false;
			}
			$label = $model['name'] . ' (' . $model['id'] . ')';
			if ( ! empty( $model['price_label'] ) ) {
				$label .= ' [' . $model['price_label'] . ']';
			}
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $model['id'] ),
				selected( $current, $model['id'], false ),
				esc_html( $label )
			);
		}
		if ( $in_custom_group ) {
			echo '</optgroup>';
		}
		echo '</select>';
	}

	/**
	 * Render the custom model input + tag list UI.
	 *
	 * @param string $hidden_name The hidden input name for form submission.
	 * @param string $hidden_id   The hidden input id.
	 * @param string $select_id   The associated dropdown id to sync with.
	 * @param string $browse_url  URL to browse models on HuggingFace.
	 * @param string $placeholder Placeholder text for the input field.
	 */
	private static function render_custom_model_input(
		string $hidden_name,
		string $hidden_id,
		string $select_id,
		string $browse_url,
		string $placeholder
	): void {
		$value  = get_option( $hidden_name, '' );
		$models = array_filter( array_map( 'trim', explode( "\n", (string) $value ) ) );
		?>
		<div class="aiprfohu-custom-models" data-hidden-id="<?php echo esc_attr( $hidden_id ); ?>" data-select-id="<?php echo esc_attr( $select_id ); ?>">
			<div class="aiprfohu-custom-models__input-row">
				<input type="text"
					class="aiprfohu-custom-models__input"
					placeholder="<?php echo esc_attr( $placeholder ); ?>" />
				<button type="button" class="button aiprfohu-custom-models__add">
					<?php esc_html_e( 'Add', 'ai-provider-for-hugging-face' ); ?>
				</button>
			</div>
			<div class="aiprfohu-custom-models__tags">
				<?php foreach ( $models as $model_id ) : ?>
					<span class="aiprfohu-custom-models__tag">
						<?php echo esc_html( $model_id ); ?>
						<button type="button" class="aiprfohu-custom-models__remove" data-model="<?php echo esc_attr( $model_id ); ?>">&times;</button>
					</span>
				<?php endforeach; ?>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" id="<?php echo esc_attr( $hidden_id ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<p class="description">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: URL to browse models on HuggingFace */
						__( 'Enter a HuggingFace model ID (e.g., org/model-name) and click Add. <a href="%s" target="_blank">Browse models on HuggingFace</a>', 'ai-provider-for-hugging-face' ),
						esc_url( $browse_url )
					),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the text model dropdown.
	 */
	public static function render_model_field(): void {
		$current = get_option( self::OPTION_DEFAULT_MODEL, '' );
		$models  = self::get_all_models();

		self::render_model_dropdown( self::OPTION_DEFAULT_MODEL, $current, $models, 'aiprfohu-text-model-select' );
		echo '<p class="description">' . esc_html__( 'Showing top 20 most popular models. Add more via Custom Models below.', 'ai-provider-for-hugging-face' ) . '</p>';
	}

	/**
	 * Render the custom text models field.
	 */
	public static function render_custom_models_field(): void {
		self::render_custom_model_input(
			self::OPTION_CUSTOM_MODELS,
			'aiprfohu-custom-text-models',
			'aiprfohu-text-model-select',
			'https://huggingface.co/models?inference=warm&pipeline_tag=text-generation&sort=likes&direction=-1',
			'org/model-name'
		);
	}

	/**
	 * Render the image model dropdown.
	 */
	public static function render_image_model_field(): void {
		$current = get_option( self::OPTION_DEFAULT_IMAGE_MODEL, '' );
		$models  = self::get_all_image_models();

		self::render_model_dropdown( self::OPTION_DEFAULT_IMAGE_MODEL, $current, $models, 'aiprfohu-image-model-select' );
		echo '<p class="description">' . esc_html__( 'Showing top 20 most popular models. Add more via Custom Image Models below.', 'ai-provider-for-hugging-face' ) . '</p>';
	}

	/**
	 * Render the custom image models field.
	 */
	public static function render_custom_image_models_field(): void {
		self::render_custom_model_input(
			self::OPTION_CUSTOM_IMAGE_MODELS,
			'aiprfohu-custom-image-models',
			'aiprfohu-image-model-select',
			'https://huggingface.co/models?inference=warm&pipeline_tag=text-to-image&sort=likes&direction=-1',
			'org/model-name'
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		settings_errors( self::OPTION_DEFAULT_MODEL );

		echo '<form action="options.php" method="post">';
		settings_fields( self::OPTION_GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Sanitize the custom models value.
	 *
	 * @param mixed $value The raw value.
	 * @return string Cleaned model IDs, one per line.
	 */
	public static function sanitize_custom_models( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$lines = explode( "\n", $value );
		$clean = array();

		foreach ( $lines as $line ) {
			$line = trim( sanitize_text_field( $line ) );
			if ( '' !== $line ) {
				$clean[] = $line;
			}
		}

		return implode( "\n", $clean );
	}

	/**
	 * Get the saved default model ID.
	 *
	 * @return string
	 */
	public static function get_default_model(): string {
		return (string) get_option( self::OPTION_DEFAULT_MODEL, '' );
	}

	/**
	 * Get the saved default image model ID.
	 *
	 * @return string
	 */
	public static function get_default_image_model(): string {
		return (string) get_option( self::OPTION_DEFAULT_IMAGE_MODEL, '' );
	}

	/**
	 * Get parsed custom models from the setting.
	 *
	 * @return array[]
	 */
	public static function get_custom_models(): array {
		return self::parse_custom_models( self::OPTION_CUSTOM_MODELS );
	}

	/**
	 * Get parsed custom image models from the setting.
	 *
	 * @return array[]
	 */
	public static function get_custom_image_models(): array {
		return self::parse_custom_models( self::OPTION_CUSTOM_IMAGE_MODELS );
	}

	/**
	 * Parse a custom models option into an array of model definitions.
	 *
	 * @param string $option_name The option name to read.
	 * @return array[]
	 */
	private static function parse_custom_models( string $option_name ): array {
		$value = (string) get_option( $option_name, '' );

		if ( '' === $value ) {
			return array();
		}

		$lines  = explode( "\n", $value );
		$models = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = explode( '/', $line );
			$name  = end( $parts );

			$models[] = array(
				'id'     => $line,
				'name'   => $name,
				'custom' => true,
			);
		}

		return $models;
	}

	/**
	 * Get all text models (custom + built-in) for the dropdown.
	 *
	 * @return array[]
	 */
	private static function get_all_models(): array {
		$directory = new HuggingFaceModelMetadataDirectory();
		$custom    = self::get_custom_models();
		return array_merge( $custom, $directory->getDefaultModels() );
	}

	/**
	 * Get all image models (custom + built-in) for the dropdown.
	 *
	 * @return array[]
	 */
	private static function get_all_image_models(): array {
		$directory = new HuggingFaceModelMetadataDirectory();
		$custom    = self::get_custom_image_models();
		return array_merge( $custom, $directory->getDefaultImageModels() );
	}
}
