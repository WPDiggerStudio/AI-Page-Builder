<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * MediaUploadField - Advanced WordPress media library upload field.
 *
 * Features: preview, file path display, copy path, remove functionality.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class MediaUploadField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'media_upload';
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
		$buttonText = $config['button_text'] ?? __( 'Choose File', 'wp-jarvis' );
		$removeText = $config['remove_text'] ?? __( 'Remove', 'wp-jarvis' );
		$mediaType  = $config['media_type'] ?? '';

		$attachmentId = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value ?? 0 );
		$hasFile      = $attachmentId && wp_get_attachment_url( $attachmentId );
		$fileUrl      = $hasFile ? wp_get_attachment_url( $attachmentId ) : '';
		$fileName     = $hasFile ? basename( get_attached_file( $attachmentId ) ) : '';
		$isImage      = $hasFile && wp_attachment_is_image( $attachmentId );

		$html = '<div class="' . self::CSS_PREFIX . '__upload-container" data-media-type="' . esc_attr( $mediaType ) . '">';

		// Hidden input for attachment ID
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
			if ( $isImage ) {
				$html .= wp_get_attachment_image( $attachmentId, 'medium', false, [ 'class' => self::CSS_PREFIX . '__upload-image' ] );
			}

			// File info
			$html .= '<div class="' . self::CSS_PREFIX . '__upload-file-info">';
			$html .= '<span class="' . self::CSS_PREFIX . '__upload-file-icon dashicons dashicons-media-default"></span>';
			$html .= '<span class="' . self::CSS_PREFIX . '__upload-file-name">' . esc_html( $fileName ) . '</span>';
			$html .= '</div>';

			// File path with the copy button
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

		// Upload dropzone/button
		$html .= '<div class="' . self::CSS_PREFIX . '__upload-dropzone" id="' . esc_attr( $id ) . '-dropzone"' . ( $hasFile ? ' style="display:none;"' : '' ) . '>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-icon dashicons dashicons-upload"></span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-text">' . esc_html( $buttonText ) . '</span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-hint">' . esc_html__( 'or drag and drop', 'wp-jarvis' ) . '</span>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return int Sanitized attachment ID.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		if ( is_array( $value ) ) {
			return absint( $value['id'] ?? 0 );
		}

		return absint( $value );
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
			if ( $attachmentId && ! wp_get_attachment_url( $attachmentId ) ) {
				$result['errors']['invalid'] = __( 'Invalid media file.', 'wp-jarvis' );
				$result['valid']             = false;
			}
		}

		return $result;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? 0;
	}

	/**
	 * Get the field's assets.
	 *
	 * @return array{scripts: array, styles: array} Assets to enqueue.
	 */
	public function getAssets(): array {
		return [
			'scripts' => [ 'media-upload', 'thickbox' ],
			'styles'  => [ 'thickbox' ],
		];
	}
}
