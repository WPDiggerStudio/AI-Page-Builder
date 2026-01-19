<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

/**
 * FileUploadField - Advanced WordPress file upload field.
 *
 * Extends MediaUploadField for any file type with preview/path/copy/remove.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class FileUploadField extends MediaUploadField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'file_upload';
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
		$removeText = $config['remove_text'] ?? __( 'Remove File', 'wp-jarvis' );

		$attachmentId = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value ?? 0 );
		$hasFile      = $attachmentId && wp_get_attachment_url( $attachmentId );
		$fileUrl      = $hasFile ? wp_get_attachment_url( $attachmentId ) : '';
		$fileName     = $hasFile ? basename( get_attached_file( $attachmentId ) ) : '';
		$isImage      = $hasFile && wp_attachment_is_image( $attachmentId );

		$html = '<div class="' . self::CSS_PREFIX . '__upload-container" data-media-type="">';

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
			// Show image preview if it's an image
			if ( $isImage ) {
				$html .= wp_get_attachment_image( $attachmentId, 'thumbnail', false, [ 'class' => self::CSS_PREFIX . '__upload-image' ] );
			}

			// File info
			$html .= '<div class="' . self::CSS_PREFIX . '__upload-file-info">';
			$html .= '<span class="' . self::CSS_PREFIX . '__upload-file-icon dashicons dashicons-media-default"></span>';
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
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-icon dashicons dashicons-media-default"></span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-text">' . esc_html( $buttonText ) . '</span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__upload-dropzone-hint">' . esc_html__( 'or drag and drop', 'wp-jarvis' ) . '</span>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}
