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

	const OPTION_DEFAULT_MODEL  = 'hugging_face_default_model';
	const OPTION_CUSTOM_MODELS  = 'hugging_face_custom_models';
	const OPTION_GROUP          = 'hugging_face_settings';
	const PAGE_SLUG             = 'hugging-face-settings';

	/**
	 * Register hooks for the admin page.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
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

		add_settings_section(
			'hugging_face_model_section',
			__( 'Model Settings', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPTION_CUSTOM_MODELS,
			__( 'Custom Models', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_custom_models_field' ),
			self::PAGE_SLUG,
			'hugging_face_model_section'
		);

		add_settings_field(
			self::OPTION_DEFAULT_MODEL,
			__( 'Default Model', 'ai-provider-for-hugging-face' ),
			array( __CLASS__, 'render_model_field' ),
			self::PAGE_SLUG,
			'hugging_face_model_section'
		);
	}

	/**
	 * Render the section description.
	 */
	public static function render_section_description(): void {
		echo '<p>' . esc_html__( 'Add custom Hugging Face models and choose which model to use by default for text generation.', 'ai-provider-for-hugging-face' ) . '</p>';
	}

	/**
	 * Render the custom models textarea field.
	 */
	public static function render_custom_models_field(): void {
		$value = get_option( self::OPTION_CUSTOM_MODELS, '' );

		printf(
			'<textarea name="%s" id="%s" rows="4" cols="60" class="large-text code" placeholder="%s">%s</textarea>',
			esc_attr( self::OPTION_CUSTOM_MODELS ),
			esc_attr( self::OPTION_CUSTOM_MODELS ),
			esc_attr( "google/gemma-2-9b-it\ndeepseek-ai/DeepSeek-R1" ),
			esc_textarea( $value )
		);

		echo '<p class="description">';
		echo esc_html__( 'Enter one model ID per line. Use the full HuggingFace model ID (e.g., google/gemma-2-9b-it).', 'ai-provider-for-hugging-face' );
		echo ' <a href="https://huggingface.co/models?inference=warm&pipeline_tag=text-generation&sort=trending" target="_blank">';
		echo esc_html__( 'Browse models on HuggingFace', 'ai-provider-for-hugging-face' );
		echo '</a>';
		echo '</p>';
	}

	/**
	 * Render the model selection dropdown.
	 */
	public static function render_model_field(): void {
		$current = get_option( self::OPTION_DEFAULT_MODEL, '' );
		$models  = self::get_all_models();

		echo '<select name="' . esc_attr( self::OPTION_DEFAULT_MODEL ) . '" id="' . esc_attr( self::OPTION_DEFAULT_MODEL ) . '">';
		echo '<option value="">' . esc_html__( '— First available (default) —', 'ai-provider-for-hugging-face' ) . '</option>';

		$has_custom = false;
		foreach ( $models as $model ) {
			if ( ! empty( $model['custom'] ) && ! $has_custom ) {
				echo '<optgroup label="' . esc_attr__( 'Custom Models', 'ai-provider-for-hugging-face' ) . '">';
				$has_custom = true;
			}
			if ( empty( $model['custom'] ) && $has_custom ) {
				echo '</optgroup>';
				$has_custom = false;
			}

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $model['id'] ),
				selected( $current, $model['id'], false ),
				esc_html( $model['name'] . ' (' . $model['id'] . ')' )
			);
		}

		if ( $has_custom ) {
			echo '</optgroup>';
		}

		echo '</select>';
		echo '<p class="description">' . esc_html__( 'This model will be used when no specific model preference is set.', 'ai-provider-for-hugging-face' ) . '</p>';
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
	 * Sanitize the custom models textarea value.
	 *
	 * @param mixed $value The raw value.
	 * @return string Cleaned model IDs, one per line.
	 */
	public static function sanitize_custom_models( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$lines  = explode( "\n", $value );
		$clean  = array();

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
	 * @return string Empty string if no default is set.
	 */
	public static function get_default_model(): string {
		return (string) get_option( self::OPTION_DEFAULT_MODEL, '' );
	}

	/**
	 * Get parsed custom models from the setting.
	 *
	 * @return array[] Array of model definition arrays with 'id', 'name', 'custom' keys.
	 */
	public static function get_custom_models(): array {
		$value = (string) get_option( self::OPTION_CUSTOM_MODELS, '' );

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

			// Use the part after the last slash as the display name.
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
	 * Get all models (built-in + custom) for the dropdown.
	 *
	 * @return array[]
	 */
	private static function get_all_models(): array {
		$directory = new HuggingFaceModelMetadataDirectory();
		$builtin   = $directory->getDefaultModels();

		$custom = self::get_custom_models();

		// Put custom models first so the optgroup renders correctly.
		return array_merge( $custom, $builtin );
	}
}
