<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

/**
 * ImageUploadField - Advanced WordPress image upload field.
 *
 * Extends MediaUploadField with image-specific features and validation.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class ImageUploadField extends MediaUploadField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'image_upload';
	}

	/**
	 * Render the input element.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The input HTML.
	 */
	protected function renderInput( array $config, mixed $value, string $context ): string {
		$id         = $config['id'] ?? '';
		$name       = $config['name'] ?? $id;
		$buttonText = $config['button_text'] ?? __( 'Choose Image', 'wp-jarvis' );
		$removeText = $config['remove_text'] ?? __( 'Remove Image', 'wp-jarvis' );

		$attachmentId = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value ?? 0 );
		$hasFile      = $attachmentId && wp_attachment_is_image( $attachmentId );
		$fileUrl      = $hasFile ? wp_get_attachment_url( $attachmentId ) : '';
		$fileName     = $hasFile ? basename( get_attached_file( $attachmentId ) ) : '';

		$html = '<div class="' . self::CSS_PREFIX . '__upload-container" data-media-type="image">';

		// Hidden input
		$html .= sprintf(
			'<input type="hidden" id="%s" name="%s" value="%s" class="%s__upload-value" />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $attachmentId ?: '' ),
			self::CSS_PREFIX
		);

		// Preview area
		$previewClass = self::CSS_PREFIX . '__upload-preview' . ( $hasFile ? ' has-file' : '' );
		$html         .= '<div class="' . $previewClass . '" id="' . esc_attr( $id ) . '-preview">';

		if ( $hasFile ) {
			// Image preview
			$html .= wp_get_attachment_image( $attachmentId, 'medium', false, [ 'class' => self::CSS_PREFIX . '__upload-image' ] );

			// File info
			$html .= '<div class="' . self::CSS_PREFIX . '__upload-file-info">';
			$html .= '<span class="' . self::CSS_PREFIX . '__upload-file-icon dashicons dashicons-format-image"></span>';
			$html .= '<span class="' . self::CSS_PREFIX . '__upload-file-name">' . esc_html( $fileName ) . '</span>';
			$html .= '</div>';

			// File path
			$html .= '<div class="' . self::CSS_PREFIX . '__upload-file-path">';
			$html .= sprintf(
				'<input type="text" value="%s" readonly id="%s-path" />',
				esc_attr( $fileUrl ),
				esc_attr( $id )
			);
			$html .= sprintf(
				'<button type="button" class="button button-small %s__upload-copy-btn" data-target="%s-path">%s</button>',
				self::CSS_PREFIX,
				esc_attr( $id ),
				esc_html__( 'Copy', 'wp-jarvis' )
			);
			$html .= '</div>';

			// Actions
			$html .= '<div class="' . self::CSS_PREFIX . '__upload-actions">';
			$html .= sprintf(
				'<button type="button" class="button button-link-delete %s__upload-remove" data-target="%s">%s</button>',
				self::CSS_PREFIX,
				esc_attr( $id ),
				esc_html( $removeText )
			);
			$html .= '</div>';
		}

		$html .= '</div>';

		// Upload dropzone
		$html .= '<div class="' . self::CSS_PREFIX . '__upload-dropzone" id="' . esc_attr( $id ) . '-dropzone"' . ( $hasFile ? ' style="display:none;"' : '' ) . '>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-icon dashicons dashicons-format-image"></span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-text">' . esc_html( $buttonText ) . '</span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-hint">' . esc_html__( 'or drag and drop', 'wp-jarvis' ) . '</span>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result.
	 */
	public function validate( mixed $value, array $config ): array {
		$result = parent::validate( $value, $config );

		if ( ! empty( $value ) ) {
			$attachmentId = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value );
			if ( $attachmentId && ! wp_attachment_is_image( $attachmentId ) ) {
				$result['errors']['image'] = __( 'Please upload a valid image file.', 'wp-jarvis' );
				$result['valid']           = false;
			}
		}

		return $result;
	}
}
